<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Organization;
use App\Models\Professional;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AppointmentService
{
    public function confirm(
        int $organizationId,
        int $patientId,
        int $professionalId,
        int $serviceId,
        string $startLocal,
        bool $depositPaid = false,
    ): array {
        try {
            $org = Organization::findOrFail($organizationId);
            $timezone = $org->timezone;

            $startUtc = Carbon::createFromFormat('Y-m-d H:i', $startLocal, $timezone)
                ->setTimezone('UTC');

            $conflict = Appointment::where('professional_id', $professionalId)
                ->where('status', 'confirmed')
                ->where('start_at', $startUtc)
                ->exists();

            if ($conflict) {
                return ['error' => 'El horario seleccionado ya no está disponible.'];
            }

            $professional = Professional::findOrFail($professionalId);
            $pivot = $professional->services()->where('service_id', $serviceId)->first()?->pivot;
            $durationMinutes = $pivot?->duration_minutes ?? 30;

            $endUtc = $startUtc->copy()->addMinutes($durationMinutes);

            $appointment = Appointment::create([
                'organization_id' => $organizationId,
                'patient_id' => $patientId,
                'professional_id' => $professionalId,
                'service_id' => $serviceId,
                'start_at' => $startUtc,
                'end_at' => $endUtc,
                'status' => 'confirmed',
                'deposit_paid' => $depositPaid,
            ]);

            $appointment->load(['professional', 'service']);

            return [
                'appointment_id' => $appointment->id,
                'start_local' => Carbon::parse($appointment->start_at)->setTimezone($timezone)->format('Y-m-d H:i'),
                'end_local' => Carbon::parse($appointment->end_at)->setTimezone($timezone)->format('Y-m-d H:i'),
                'professional' => $appointment->professional->name,
                'service' => $appointment->service->name,
            ];
        } catch (\Throwable $e) {
            Log::channel('api')->error('confirm_appointment failed', ['error' => $e->getMessage()]);

            return ['error' => 'No se pudo confirmar la cita. Intenta nuevamente.'];
        }
    }

    public function reschedule(
        int $appointmentId,
        int $patientId,
        int $newProfessionalId,
        int $newServiceId,
        string $newStartLocal,
    ): array {
        try {
            $old = Appointment::find($appointmentId);

            if (!$old || $old->status !== 'confirmed') {
                return ['error' => 'Cita no encontrada o ya cancelada.'];
            }

            if ($old->patient_id !== $patientId) {
                return ['error' => 'No tienes permiso para reprogramar esta cita.'];
            }

            $depositPaid = (bool) $old->deposit_paid;
            $organizationId = $old->organization_id;

            // Validate cancellation policy before touching anything
            $org = Organization::findOrFail($organizationId);
            $minHours = $org->cancellation_hours_min ?? 24;

            if ($minHours > 0 && !$depositPaid) {
                $hoursUntil = now()->diffInHours($old->start_at, absolute: false);
                if ($hoursUntil < $minHours) {
                    return [
                        'error' => "Solo se puede reprogramar con al menos {$minHours} horas de anticipación.",
                        'policy_violation' => true,
                    ];
                }
            }

            // Cancel old
            $old->update(['status' => 'cancelled', 'cancel_reason' => 'Reprogramación']);

            // Confirm new — if new slot fails, restore old
            $newResult = $this->confirm(
                organizationId: $organizationId,
                patientId: $patientId,
                professionalId: $newProfessionalId,
                serviceId: $newServiceId,
                startLocal: $newStartLocal,
                depositPaid: $depositPaid,
            );

            if (isset($newResult['error'])) {
                $old->update(['status' => 'confirmed', 'cancel_reason' => null]);
                return $newResult;
            }

            return $newResult;
        } catch (\Throwable $e) {
            Log::channel('api')->error('reschedule failed', ['error' => $e->getMessage()]);
            return ['error' => 'No se pudo reprogramar la cita. Intenta nuevamente.'];
        }
    }

    public function cancel(int $appointmentId, int $patientId, string $reason): array
    {
        try {
            $appointment = Appointment::find($appointmentId);

            if (!$appointment || $appointment->status !== 'confirmed') {
                return ['error' => 'Cita no encontrada o ya cancelada.'];
            }

            if ($appointment->patient_id !== $patientId) {
                return ['error' => 'No tienes permiso para cancelar esta cita.'];
            }

            $org = Organization::findOrFail($appointment->organization_id);
            $minHours = $org->cancellation_hours_min ?? 24;

            if ($minHours > 0 && !$appointment->deposit_paid) {
                $hoursUntil = now()->diffInHours($appointment->start_at, absolute: false);
                if ($hoursUntil < $minHours) {
                    return [
                        'error' => "Solo se puede cancelar con al menos {$minHours} horas de anticipación.",
                        'policy_violation' => true,
                    ];
                }
            }

            $appointment->update([
                'status' => 'cancelled',
                'cancel_reason' => $reason,
            ]);

            return [
                'appointment_id' => $appointment->id,
                'cancelled' => true,
            ];
        } catch (\Throwable $e) {
            Log::channel('api')->error('cancel_appointment failed', ['error' => $e->getMessage()]);

            return ['error' => 'No se pudo cancelar la cita. Intenta nuevamente.'];
        }
    }
}