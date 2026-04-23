<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ClaudeApiLog;
use App\Models\Conversation;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $orgId = $request->user()->organization_id;
        $todayStart = Carbon::now('America/Guayaquil')->startOfDay()->utc();
        $todayEnd = Carbon::now('America/Guayaquil')->endOfDay()->utc();

        $monthStart = Carbon::now('America/Guayaquil')->startOfMonth()->utc();

        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'appointmentsToday' => Appointment::where('organization_id', $orgId)
                    ->whereBetween('start_at', [$todayStart, $todayEnd])
                    ->where('status', 'confirmed')
                    ->count(),
                'confirmedTotal' => Appointment::where('organization_id', $orgId)
                    ->where('status', 'confirmed')
                    ->where('start_at', '>=', $todayStart)
                    ->count(),
                'cancelledTotal' => Appointment::where('organization_id', $orgId)
                    ->where('status', 'cancelled')
                    ->count(),
                'totalPatients' => Patient::where('organization_id', $orgId)->count(),
                'activeConversations' => Conversation::where('organization_id', $orgId)
                    ->where('handoff_to_human', false)
                    ->count(),
                'costToday' => (float) ClaudeApiLog::forOrg($orgId)
                    ->whereBetween('created_at', [$todayStart, $todayEnd])
                    ->sum('cost_usd'),
                'costThisMonth' => (float) ClaudeApiLog::forOrg($orgId)
                    ->where('created_at', '>=', $monthStart)
                    ->sum('cost_usd'),
                'tokensTodayTotal' => (int) ClaudeApiLog::forOrg($orgId)
                    ->whereBetween('created_at', [$todayStart, $todayEnd])
                    ->selectRaw('SUM(input_tokens + output_tokens + cache_write_tokens + cache_read_tokens) as total')
                    ->value('total'),
            ],
            'upcomingAppointments' => Appointment::where('organization_id', $orgId)
                ->where('start_at', '>=', $todayStart)
                ->where('status', 'confirmed')
                ->with('patient:id,name', 'professional:id,name', 'service:id,name')
                ->orderBy('start_at')
                ->limit(5)
                ->get()
                ->map(fn ($appt) => [
                    'id' => $appt->id,
                    'patient' => $appt->patient?->name ?? 'Sin nombre',
                    'professional' => $appt->professional?->name ?? '-',
                    'service' => $appt->service?->name ?? '-',
                    'date' => $appt->start_at->setTimezone('America/Guayaquil')->format('d/m/Y'),
                    'time' => $appt->start_at->setTimezone('America/Guayaquil')->format('H:i'),
                    'status' => $appt->status,
                ]),
        ]);
    }
}
