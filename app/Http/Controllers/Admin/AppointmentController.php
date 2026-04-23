<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Professional;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $orgId = $request->user()->organization_id;

        $query = Appointment::where('organization_id', $orgId)
            ->with('patient:id,name', 'professional:id,name', 'service:id,name');

        if ($request->filled('date')) {
            $date = Carbon::parse($request->date, 'America/Guayaquil');
            $query->whereBetween('start_at', [
                $date->copy()->startOfDay()->utc(),
                $date->copy()->endOfDay()->utc(),
            ]);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('professional_id')) {
            $query->where('professional_id', $request->professional_id);
        }

        $appointments = $query->orderByDesc('start_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($appt) => [
                'id' => $appt->id,
                'patient' => $appt->patient?->name ?? 'Sin nombre',
                'professional' => $appt->professional?->name ?? '-',
                'service' => $appt->service?->name ?? '-',
                'date' => $appt->start_at->setTimezone('America/Guayaquil')->format('d/m/Y'),
                'time' => $appt->start_at->setTimezone('America/Guayaquil')->format('H:i').' - '.$appt->end_at->setTimezone('America/Guayaquil')->format('H:i'),
                'status' => $appt->status,
                'deposit_paid' => $appt->deposit_paid,
            ]);

        return Inertia::render('Admin/Appointments/Index', [
            'appointments' => $appointments,
            'professionals' => Professional::where('organization_id', $orgId)
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
            'filters' => $request->only(['date', 'status', 'professional_id']),
        ]);
    }
}
