<?php

namespace Tests\Feature\Services;

use App\Models\Appointment;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Service;
use App\Services\AppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private Patient $patient;
    private Professional $professional;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::create([
            'name' => 'Test Clinic',
            'wa_phone_number' => '593991111111',
            'timezone' => 'America/Guayaquil',
        ]);

        $this->patient = Patient::create([
            'organization_id' => $this->org->id,
            'wa_id' => '593991234567',
            'name' => 'Juan Perez',
        ]);

        $this->professional = Professional::create([
            'organization_id' => $this->org->id,
            'name' => 'Dr. Lopez',
            'active' => true,
        ]);

        $this->service = Service::create([
            'organization_id' => $this->org->id,
            'name' => 'Consulta General',
            'active' => true,
        ]);

        $this->professional->services()->attach($this->service->id, [
            'duration_minutes' => 30,
            'price' => 40.00,
        ]);
    }

    // --- confirm_appointment ---

    public function test_confirm_creates_appointment_and_returns_summary(): void
    {
        $service = new AppointmentService();

        $result = $service->confirm(
            organizationId: $this->org->id,
            patientId: $this->patient->id,
            professionalId: $this->professional->id,
            serviceId: $this->service->id,
            startLocal: '2026-04-01 10:00',
        );

        $this->assertDatabaseCount('appointments', 1);
        $this->assertArrayHasKey('appointment_id', $result);
        $this->assertArrayHasKey('start_local', $result);
        $this->assertArrayHasKey('end_local', $result);
        $this->assertArrayHasKey('professional', $result);
        $this->assertArrayHasKey('service', $result);

        $appointment = Appointment::first();
        $this->assertEquals('confirmed', $appointment->status);
        $this->assertEquals($this->patient->id, $appointment->patient_id);
        $this->assertEquals($this->professional->id, $appointment->professional_id);
        $this->assertEquals($this->service->id, $appointment->service_id);
    }

    public function test_confirm_calculates_end_at_from_duration(): void
    {
        $service = new AppointmentService();

        $service->confirm(
            organizationId: $this->org->id,
            patientId: $this->patient->id,
            professionalId: $this->professional->id,
            serviceId: $this->service->id,
            startLocal: '2026-04-01 10:00',
        );

        $appointment = Appointment::first();
        // duration is 30 minutes, so end = 10:30 local = 15:30 UTC
        $this->assertEquals('2026-04-01 15:00:00', $appointment->start_at->toDateTimeString());
        $this->assertEquals('2026-04-01 15:30:00', $appointment->end_at->toDateTimeString());
    }

    public function test_confirm_fails_when_slot_already_taken(): void
    {
        $service = new AppointmentService();

        $service->confirm(
            organizationId: $this->org->id,
            patientId: $this->patient->id,
            professionalId: $this->professional->id,
            serviceId: $this->service->id,
            startLocal: '2026-04-01 10:00',
        );

        $result = $service->confirm(
            organizationId: $this->org->id,
            patientId: $this->patient->id,
            professionalId: $this->professional->id,
            serviceId: $this->service->id,
            startLocal: '2026-04-01 10:00',
        );

        $this->assertArrayHasKey('error', $result);
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_confirm_allows_same_professional_different_time(): void
    {
        $service = new AppointmentService();

        $service->confirm(
            organizationId: $this->org->id,
            patientId: $this->patient->id,
            professionalId: $this->professional->id,
            serviceId: $this->service->id,
            startLocal: '2026-04-01 10:00',
        );

        $result = $service->confirm(
            organizationId: $this->org->id,
            patientId: $this->patient->id,
            professionalId: $this->professional->id,
            serviceId: $this->service->id,
            startLocal: '2026-04-01 11:00',
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertDatabaseCount('appointments', 2);
    }

    // --- cancel_appointment ---

    public function test_cancel_marks_appointment_as_cancelled(): void
    {
        $appointment = Appointment::create([
            'organization_id' => $this->org->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'start_at' => now()->addDays(3)->setTimezone('UTC'),
            'end_at' => now()->addDays(3)->addMinutes(30)->setTimezone('UTC'),
            'status' => 'confirmed',
        ]);

        $service = new AppointmentService();
        $result = $service->cancel($appointment->id, $this->patient->id, 'Ya no puedo asistir');

        $this->assertArrayNotHasKey('error', $result);
        $this->assertEquals('cancelled', $appointment->fresh()->status);
        $this->assertEquals('Ya no puedo asistir', $appointment->fresh()->cancel_reason);
    }

    public function test_cancel_rejected_when_within_cancellation_policy(): void
    {
        // Org requires 24h notice to cancel
        $this->org->update(['cancellation_hours_min' => 24]);

        $appointment = Appointment::create([
            'organization_id' => $this->org->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'start_at' => now()->addHours(12)->setTimezone('UTC'),
            'end_at' => now()->addHours(12)->addMinutes(30)->setTimezone('UTC'),
            'status' => 'confirmed',
        ]);

        $service = new AppointmentService();
        $result = $service->cancel($appointment->id, $this->patient->id, 'Cambio de planes');

        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('policy_violation', $result);
        $this->assertEquals('confirmed', $appointment->fresh()->status);
    }

    public function test_cancel_rejected_when_appointment_belongs_to_another_patient(): void
    {
        $otherPatient = Patient::create([
            'organization_id' => $this->org->id,
            'wa_id' => '593999999999',
            'name' => 'Otro Paciente',
        ]);

        $appointment = Appointment::create([
            'organization_id' => $this->org->id,
            'patient_id' => $otherPatient->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'start_at' => now()->addDays(3)->setTimezone('UTC'),
            'end_at' => now()->addDays(3)->addMinutes(30)->setTimezone('UTC'),
            'status' => 'confirmed',
        ]);

        $service = new AppointmentService();
        $result = $service->cancel($appointment->id, $this->patient->id, 'Quiero cancelar');

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('confirmed', $appointment->fresh()->status);
    }

    public function test_cancel_allowed_anytime_when_deposit_paid(): void
    {
        // Org requires 24h notice, but patient paid deposit so can cancel anytime
        $this->org->update(['cancellation_hours_min' => 24]);

        $appointment = Appointment::create([
            'organization_id' => $this->org->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'start_at' => now()->addHours(6)->setTimezone('UTC'),
            'end_at' => now()->addHours(6)->addMinutes(30)->setTimezone('UTC'),
            'status' => 'confirmed',
            'deposit_paid' => true,
        ]);

        $service = new AppointmentService();
        $result = $service->cancel($appointment->id, $this->patient->id, 'Emergencia');

        $this->assertArrayNotHasKey('error', $result);
        $this->assertEquals('cancelled', $appointment->fresh()->status);
    }

    public function test_confirm_sets_deposit_paid_flag(): void
    {
        $service = new AppointmentService();

        $result = $service->confirm(
            organizationId: $this->org->id,
            patientId: $this->patient->id,
            professionalId: $this->professional->id,
            serviceId: $this->service->id,
            startLocal: '2026-04-01 10:00',
            depositPaid: true,
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertTrue((bool) Appointment::first()->deposit_paid);
    }

    public function test_default_cancellation_policy_is_24_hours(): void
    {
        // Without explicit policy set, orgs default to 24h
        $appointment = Appointment::create([
            'organization_id' => $this->org->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'start_at' => now()->addHours(12)->setTimezone('UTC'),
            'end_at' => now()->addHours(12)->addMinutes(30)->setTimezone('UTC'),
            'status' => 'confirmed',
        ]);

        $service = new AppointmentService();
        $result = $service->cancel($appointment->id, $this->patient->id, 'Cancelar');

        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('policy_violation', $result);
    }

    // --- reschedule ---

    public function test_reschedule_cancels_old_and_creates_new_appointment(): void
    {
        $old = Appointment::create([
            'organization_id' => $this->org->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'start_at' => now()->addDays(5)->setTimezone('UTC'),
            'end_at' => now()->addDays(5)->addMinutes(30)->setTimezone('UTC'),
            'status' => 'confirmed',
        ]);

        $service = new AppointmentService();
        $result = $service->reschedule(
            appointmentId: $old->id,
            patientId: $this->patient->id,
            newProfessionalId: $this->professional->id,
            newServiceId: $this->service->id,
            newStartLocal: '2026-04-20 11:00',
        );

        $this->assertArrayHasKey('appointment_id', $result);
        $this->assertNotEquals($old->id, $result['appointment_id']);
        $this->assertEquals('cancelled', $old->fresh()->status);
        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_reschedule_transfers_deposit_paid_to_new_appointment(): void
    {
        $old = Appointment::create([
            'organization_id' => $this->org->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'start_at' => now()->addHours(6)->setTimezone('UTC'),
            'end_at' => now()->addHours(6)->addMinutes(30)->setTimezone('UTC'),
            'status' => 'confirmed',
            'deposit_paid' => true,
        ]);

        $service = new AppointmentService();
        $result = $service->reschedule(
            appointmentId: $old->id,
            patientId: $this->patient->id,
            newProfessionalId: $this->professional->id,
            newServiceId: $this->service->id,
            newStartLocal: '2026-04-20 09:00',
        );

        $this->assertArrayNotHasKey('error', $result);
        $newAppointment = Appointment::find($result['appointment_id']);
        $this->assertTrue($newAppointment->deposit_paid);
    }

    public function test_reschedule_blocked_by_cancellation_policy_when_no_deposit(): void
    {
        $this->org->update(['cancellation_hours_min' => 24]);

        $old = Appointment::create([
            'organization_id' => $this->org->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'start_at' => now()->addHours(6)->setTimezone('UTC'),
            'end_at' => now()->addHours(6)->addMinutes(30)->setTimezone('UTC'),
            'status' => 'confirmed',
            'deposit_paid' => false,
        ]);

        $service = new AppointmentService();
        $result = $service->reschedule(
            appointmentId: $old->id,
            patientId: $this->patient->id,
            newProfessionalId: $this->professional->id,
            newServiceId: $this->service->id,
            newStartLocal: '2026-04-20 09:00',
        );

        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('policy_violation', $result);
        $this->assertEquals('confirmed', $old->fresh()->status);
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_reschedule_fails_when_new_slot_already_taken(): void
    {
        $old = Appointment::create([
            'organization_id' => $this->org->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'start_at' => now()->addDays(5)->setTimezone('UTC'),
            'end_at' => now()->addDays(5)->addMinutes(30)->setTimezone('UTC'),
            'status' => 'confirmed',
        ]);

        // Another appointment blocks the target slot
        Appointment::create([
            'organization_id' => $this->org->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'start_at' => '2026-04-20 15:00:00', // 10:00 ECT = 15:00 UTC
            'end_at' => '2026-04-20 15:30:00',
            'status' => 'confirmed',
        ]);

        $service = new AppointmentService();
        $result = $service->reschedule(
            appointmentId: $old->id,
            patientId: $this->patient->id,
            newProfessionalId: $this->professional->id,
            newServiceId: $this->service->id,
            newStartLocal: '2026-04-20 10:00',
        );

        $this->assertArrayHasKey('error', $result);
        // Old appointment should be restored to confirmed
        $this->assertEquals('confirmed', $old->fresh()->status);
    }
}
