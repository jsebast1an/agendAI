<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Professional;
use App\Models\Schedule;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AgendaToolsService
{
    public function getServices(int $orgId): array
    {
        try {
            return Service::where('organization_id', $orgId)
                ->where('active', true)
                ->get(['id', 'name', 'description'])
                ->toArray();
        } catch (\Throwable $e) {
            Log::channel('api')->error('get_services failed', ['org_id' => $orgId, 'error' => $e->getMessage()]);
            return [];
        }
    }

    public function getProfessionals(int $orgId, ?int $serviceId = null): array
    {
        try {
            $query = Professional::where('organization_id', $orgId)
                ->where('active', true);

            if ($serviceId) {
                $query->whereHas('services', fn ($q) => $q->where('services.id', $serviceId));
            }

            return $query->get(['id', 'name', 'specialty'])->toArray();
        } catch (\Throwable $e) {
            Log::channel('api')->error('get_professionals failed', ['org_id' => $orgId, 'error' => $e->getMessage()]);
            return [];
        }
    }

    public function getAvailability(int $professionalId, int $serviceId, string $dateLocal): array
    {
        try {
            $date = Carbon::parse($dateLocal);
            $dayOfWeek = $date->dayOfWeek; // 0=Sunday

            $schedules = Schedule::where('professional_id', $professionalId)
                ->where('day_of_week', $dayOfWeek)
                ->get();

            if ($schedules->isEmpty()) {
                return [];
            }

            $pivot = Professional::find($professionalId)
                ?->services()
                ->where('services.id', $serviceId)
                ->first()
                ?->pivot;

            $duration = $pivot?->duration_minutes ?? 30;

            $booked = Appointment::where('professional_id', $professionalId)
                ->whereDate('start_at', $dateLocal)
                ->where('status', '!=', 'cancelled')
                ->get(['start_at', 'end_at']);

            $slots = [];

            foreach ($schedules as $schedule) {
                $slotStart = Carbon::parse("{$dateLocal} {$schedule->start_time}");
                $blockEnd = Carbon::parse("{$dateLocal} {$schedule->end_time}");

                while ($slotStart->copy()->addMinutes($duration)->lte($blockEnd)) {
                    $slotEnd = $slotStart->copy()->addMinutes($duration);

                    $isBooked = $booked->contains(function ($appt) use ($slotStart, $slotEnd) {
                        return $slotStart < $appt->end_at && $slotEnd > $appt->start_at;
                    });

                    if (!$isBooked) {
                        $slots[] = [
                            'start' => $slotStart->format('H:i'),
                            'end' => $slotEnd->format('H:i'),
                        ];
                    }

                    $slotStart->addMinutes($duration);
                }
            }

            return $slots;
        } catch (\Throwable $e) {
            Log::channel('api')->error('get_availability failed', [
                'professional_id' => $professionalId,
                'date' => $dateLocal,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function listUpcomingAppointments(int $patientId): array
    {
        try {
            return Appointment::where('patient_id', $patientId)
                ->where('start_at', '>', now())
                ->where('status', 'confirmed')
                ->with(['professional:id,name', 'service:id,name'])
                ->orderBy('start_at')
                ->get()
                ->map(fn ($appt) => [
                    'id' => $appt->id,
                    'professional' => $appt->professional->name,
                    'service' => $appt->service->name,
                    'start_at' => $appt->start_at->format('Y-m-d H:i'),
                    'end_at' => $appt->end_at->format('Y-m-d H:i'),
                    'status' => $appt->status,
                ])
                ->toArray();
        } catch (\Throwable $e) {
            Log::channel('api')->error('list_upcoming_appointments failed', [
                'patient_id' => $patientId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
