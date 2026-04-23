<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\ClaudeApiLog;
use App\Models\Conversation;
use App\Models\Organization;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request): Response
    {
        $tab = $request->input('tab', 'orgs');
        $now = Carbon::now('America/Guayaquil');
        $todayStart = $now->copy()->startOfDay()->utc();
        $monthStart = $now->copy()->startOfMonth()->utc();

        $monthlyStats = ClaudeApiLog::where('created_at', '>=', $monthStart)
            ->selectRaw('organization_id,
                SUM(cost_usd) as cost_month,
                SUM(input_tokens + output_tokens + cache_write_tokens + cache_read_tokens) as tokens_month')
            ->groupBy('organization_id')
            ->get()
            ->keyBy('organization_id');

        $organizations = Organization::withCount(['conversations', 'patients'])
            ->get()
            ->map(fn ($org) => [
                'id' => $org->id,
                'name' => $org->name,
                'wa_number' => $org->wa_phone_number,
                'conversations' => $org->conversations_count,
                'patients' => $org->patients_count,
                'cost_month' => (float) ($monthlyStats[$org->id]?->cost_month ?? 0),
                'tokens_month' => (int) ($monthlyStats[$org->id]?->tokens_month ?? 0),
                'type' => $org->type,
                'timezone' => $org->timezone,
                'cancellation_hours_min' => $org->cancellation_hours_min,
            ]);

        $apiLogs = ClaudeApiLog::with('organization:id,name')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'org' => $log->organization?->name ?? '-',
                'model' => $log->model,
                'input' => $log->input_tokens,
                'output' => $log->output_tokens,
                'cache_write' => $log->cache_write_tokens,
                'cache_read' => $log->cache_read_tokens,
                'cost_usd' => (float) $log->cost_usd,
                'created_at' => $log->created_at->setTimezone('America/Guayaquil')->format('d/m H:i'),
            ]);

        return Inertia::render('Platform/Dashboard', [
            'tab' => $tab,
            'globalMetrics' => [
                'totalOrgs' => Organization::count(),
                'costToday' => (float) ClaudeApiLog::where('created_at', '>=', $todayStart)->sum('cost_usd'),
                'costThisMonth' => (float) ClaudeApiLog::where('created_at', '>=', $monthStart)->sum('cost_usd'),
                'tokensTodayTotal' => (int) ClaudeApiLog::where('created_at', '>=', $todayStart)
                    ->selectRaw('SUM(input_tokens + output_tokens + cache_write_tokens + cache_read_tokens) as t')
                    ->value('t'),
                'activeConversations' => Conversation::where('handoff_to_human', false)->count(),
            ],
            'organizations' => $organizations,
            'apiLogs' => $apiLogs,
        ]);
    }
}
