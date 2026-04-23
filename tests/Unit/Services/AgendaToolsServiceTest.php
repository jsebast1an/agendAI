<?php

namespace Tests\Unit\Services;

use App\Models\Appointment;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Schedule;
use App\Models\Service;
use App\Services\AgendaToolsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaToolsServiceTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private Professional $professional;

    private Service $service;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::create([
            'name' => 'Consultorio Test',
            'wa_phone_number' => '593991000001',
        ]);

        $this->service = Service::create([
            'organization_id' => $this->org->id,
            'name' => 'Consulta General',
            'description' => 'Consulta médica general',
        ]);

        $this->professional = Professional::create([
            'organization_id' => $this->org->id,
            'name' => 'Dr. López',
            'specialty' => 'Medicina General',
        ]);

        $this->professional->services()->attach($this->service->id, [
            'duration_minutes' => 30,
            'price' => 25.00,
        ]);

        $this->patient = Patient::create([
            'organization_id' => $this->org->id,
            'wa_id' => '593991234567',
            'phone_number' => '593991234567',
        ]);
    }

    // --- get_services ---

    public function test_get_services_returns_active_services_for_org(): void
    {
        Service::create([
            'organization_id' => $this->org->id,
            'name' => 'Servicio Inactivo',
            'active' => false,
        ]);

        $tools = new AgendaToolsService;
        $result = $tools->getServices($this->org->id);

        $this->assertCount(1, $result);
        $this->assertEquals('Consulta General', $result[0]['name']);
    }

    public function test_get_services_returns_empty_for_unknown_org(): void
    {
        $tools = new AgendaToolsService;
        $result = $tools->getServices(9999);

        $this->assertCount(0, $result);
    }

    // --- get_professionals ---

    public function test_get_professionals_returns_active_professionals(): void
    {
        Professional::create([
            'organization_id' => $this->org->id,
            'name' => 'Dr. Inactivo',
            'active' => false,
        ]);

        $tools = new AgendaToolsService;
        $result = $tools->getProfessionals($this->org->id);

        $this->assertCount(1, $result);
        $this->assertEquals('Dr. López', $result[0]['name']);
    }

    public function test_get_professionals_filtered_by_service(): void
    {
        $otherPro = Professional::create([
            'organization_id' => $this->org->id,
            'name' => 'Dra. García',
            'specialty' => 'Dermatología',
        ]);

        $tools = new AgendaToolsService;
        $result = $tools->getProfessionals($this->org->id, $this->service->id);

        $this->assertCount(1, $result);
        $this->assertEquals('Dr. López', $result[0]['name']);
    }

    // --- get_availability ---

    public function test_get_availability_returns_free_slots(): void
    {
        // Monday schedule: 09:00 - 11:00
        Schedule::create([
            'professional_id' => $this->professional->id,
            'day_of_week' => 1, // Monday
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        // Find next Monday
        $monday = now()->next('Monday')->format('Y-m-d');

        $tools = new AgendaToolsService;
        $result = $tools->getAvailability($this->professional->id, $this->service->id, $monday);

        // 09:00-11:00 with 30min slots = 4 slots (09:00, 09:30, 10:00, 10:30)
        $this->assertCount(4, $result);
        $this->assertEquals('09:00', $result[0]['start']);
        $this->assertEquals('09:30', $result[0]['end']);
    }

    public function test_get_availability_excludes_booked_slots(): void
    {
        Schedule::create([
            'professional_id' => $this->professional->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $monday = now()->next('Monday')->format('Y-m-d');

        // Book 09:00 - 09:30
        Appointment::create([
            'organization_id' => $this->org->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'start_at' => "{$monday} 09:00:00",
            'end_at' => "{$monday} 09:30:00",
            'status' => 'confirmed',
        ]);

        $tools = new AgendaToolsService;
        $result = $tools->getAvailability($this->professional->id, $this->service->id, $monday);

        // Should have 3 slots: 09:30, 10:00, 10:30 (09:00 is booked)
        $this->assertCount(3, $result);
        $this->assertEquals('09:30', $result[0]['start']);
    }

    public function test_get_availability_returns_empty_for_no_schedule(): void
    {
        $tuesday = now()->next('Tuesday')->format('Y-m-d');

        $tools = new AgendaToolsService;
        $result = $tools->getAvailability($this->professional->id, $this->service->id, $tuesday);

        $this->assertCount(0, $result);
    }

    // --- list_upcoming_appointments ---

    public function test_list_upcoming_appointments_returns_future_confirmed(): void
    {
        $tomorrow = now()->addDay()->format('Y-m-d');

        Appointment::create([
            'organization_id' => $this->org->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'start_at' => "{$tomorrow} 10:00:00",
            'end_at' => "{$tomorrow} 10:30:00",
            'status' => 'confirmed',
        ]);

        // Past appointment - should NOT appear
        Appointment::create([
            'organization_id' => $this->org->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'start_at' => now()->subDays(2)->format('Y-m-d').' 10:00:00',
            'end_at' => now()->subDays(2)->format('Y-m-d').' 10:30:00',
            'status' => 'confirmed',
        ]);

        // Cancelled - should NOT appear
        Appointment::create([
            'organization_id' => $this->org->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'start_at' => "{$tomorrow} 14:00:00",
            'end_at' => "{$tomorrow} 14:30:00",
            'status' => 'cancelled',
        ]);

        $tools = new AgendaToolsService;
        $result = $tools->listUpcomingAppointments($this->patient->id);

        $this->assertCount(1, $result);
        $this->assertEquals('Dr. López', $result[0]['professional']);
        $this->assertEquals('Consulta General', $result[0]['service']);
    }

    // --- confirm_appointment ---

    public function test_confirm_appointment_delegates_to_appointment_service(): void
    {
        $tools = new AgendaToolsService;
        $result = $tools->confirmAppointment(
            organizationId: $this->org->id,
            patientId: $this->patient->id,
            professionalId: $this->professional->id,
            serviceId: $this->service->id,
            startLocal: '2026-04-10 09:00',
        );

        $this->assertArrayHasKey('appointment_id', $result);
        $this->assertDatabaseCount('appointments', 1);
    }

    // --- cancel_appointment ---

    public function test_cancel_appointment_delegates_to_appointment_service(): void
    {
        $appointment = Appointment::create([
            'organization_id' => $this->org->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'start_at' => now()->addDays(5),
            'end_at' => now()->addDays(5)->addMinutes(30),
            'status' => 'confirmed',
        ]);

        $tools = new AgendaToolsService;
        $result = $tools->cancelAppointment(
            appointmentId: $appointment->id,
            patientId: $this->patient->id,
            reason: 'No puedo ir',
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertEquals('cancelled', $appointment->fresh()->status);
    }

    public function test_reschedule_appointment_delegates_to_appointment_service(): void
    {
        $old = Appointment::create([
            'organization_id' => $this->org->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'start_at' => now()->addDays(5),
            'end_at' => now()->addDays(5)->addMinutes(30),
            'status' => 'confirmed',
        ]);

        $tools = new AgendaToolsService;
        $result = $tools->rescheduleAppointment(
            appointmentId: $old->id,
            patientId: $this->patient->id,
            newProfessionalId: $this->professional->id,
            newServiceId: $this->service->id,
            newStartLocal: '2026-04-25 10:00',
        );

        $this->assertArrayHasKey('appointment_id', $result);
        $this->assertEquals('cancelled', $old->fresh()->status);
    }
}
