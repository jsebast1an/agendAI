<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Professional;
use App\Models\Schedule;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $orgId = $request->user()->organization_id;

        $professionals = Professional::where('organization_id', $orgId)
            ->with(['services:id,name', 'schedules'])
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id'        => $p->id,
                'name'      => $p->name,
                'specialty' => $p->specialty,
                'active'    => $p->active,
                'services'  => $p->services->map(fn($s) => [
                    'service_id'       => $s->id,
                    'name'             => $s->name,
                    'duration_minutes' => $s->pivot->duration_minutes,
                    'price'            => $s->pivot->price,
                ]),
                'schedules' => $p->schedules->map(fn($sc) => [
                    'id'          => $sc->id,
                    'day_of_week' => $sc->day_of_week,
                    'start_time'  => $sc->start_time,
                    'end_time'    => $sc->end_time,
                ]),
            ]);

        $services = Service::where('organization_id', $orgId)
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'active']);

        return Inertia::render('Admin/Settings/Index', [
            'professionals' => $professionals,
            'services'      => $services,
            'tab'           => $request->input('tab', 'professionals'),
        ]);
    }

    // ── Professionals ────────────────────────────────────────────────────────

    public function storeProfessional(Request $request)
    {
        $orgId = $request->user()->organization_id;

        $validated = $request->validate([
            'name'                        => 'required|string|max:100',
            'specialty'                   => 'nullable|string|max:100',
            'active'                      => 'boolean',
            'services'                    => 'array',
            'services.*.service_id'       => ['required', 'integer', "exists:services,id,organization_id,{$orgId}"],
            'services.*.duration_minutes' => 'required|integer|min:5|max:480',
            'services.*.price'            => 'nullable|numeric|min:0',
        ]);

        $professional = Professional::create([
            'organization_id' => $orgId,
            'name'            => $validated['name'],
            'specialty'       => $validated['specialty'] ?? null,
            'active'          => $validated['active'] ?? true,
        ]);

        $this->syncServices($professional, $validated['services'] ?? []);

        return redirect()
            ->route('admin.settings.index', ['tab' => 'professionals'])
            ->with('success', 'Profesional creado.');
    }

    public function updateProfessional(Request $request, Professional $professional)
    {
        $orgId = $request->user()->organization_id;
        abort_if($professional->organization_id !== $orgId, 403);

        $validated = $request->validate([
            'name'                        => 'required|string|max:100',
            'specialty'                   => 'nullable|string|max:100',
            'active'                      => 'boolean',
            'services'                    => 'array',
            'services.*.service_id'       => ['required', 'integer', "exists:services,id,organization_id,{$orgId}"],
            'services.*.duration_minutes' => 'required|integer|min:5|max:480',
            'services.*.price'            => 'nullable|numeric|min:0',
        ]);

        $professional->update([
            'name'      => $validated['name'],
            'specialty' => $validated['specialty'] ?? null,
            'active'    => $validated['active'] ?? true,
        ]);

        $this->syncServices($professional, $validated['services'] ?? []);

        return redirect()
            ->route('admin.settings.index', ['tab' => 'professionals'])
            ->with('success', 'Profesional actualizado.');
    }

    public function destroyProfessional(Request $request, Professional $professional)
    {
        $orgId = $request->user()->organization_id;
        abort_if($professional->organization_id !== $orgId, 403);

        $professional->delete();

        return redirect()
            ->route('admin.settings.index', ['tab' => 'professionals'])
            ->with('success', 'Profesional eliminado.');
    }

    // ── Services ─────────────────────────────────────────────────────────────

    public function storeService(Request $request)
    {
        $orgId = $request->user()->organization_id;

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'active'      => 'boolean',
        ]);

        Service::create([
            'organization_id' => $orgId,
            'name'            => $validated['name'],
            'description'     => $validated['description'] ?? null,
            'active'          => $validated['active'] ?? true,
        ]);

        return redirect()
            ->route('admin.settings.index', ['tab' => 'services'])
            ->with('success', 'Servicio creado.');
    }

    public function updateService(Request $request, Service $service)
    {
        $orgId = $request->user()->organization_id;
        abort_if($service->organization_id !== $orgId, 403);

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'active'      => 'boolean',
        ]);

        $service->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'active'      => $validated['active'] ?? true,
        ]);

        return redirect()
            ->route('admin.settings.index', ['tab' => 'services'])
            ->with('success', 'Servicio actualizado.');
    }

    public function destroyService(Request $request, Service $service)
    {
        $orgId = $request->user()->organization_id;
        abort_if($service->organization_id !== $orgId, 403);

        $service->delete();

        return redirect()
            ->route('admin.settings.index', ['tab' => 'services'])
            ->with('success', 'Servicio eliminado.');
    }

    // ── Schedules ────────────────────────────────────────────────────────────

    public function updateSchedules(Request $request, Professional $professional)
    {
        $orgId = $request->user()->organization_id;
        abort_if($professional->organization_id !== $orgId, 403);

        $validated = $request->validate([
            'schedules'               => 'array',
            'schedules.*.day_of_week' => 'required|integer|between:0,6',
            'schedules.*.start_time'  => 'required|date_format:H:i',
            'schedules.*.end_time'    => 'required|date_format:H:i',
        ]);

        $professional->schedules()->delete();

        foreach ($validated['schedules'] ?? [] as $sc) {
            $professional->schedules()->create([
                'day_of_week' => $sc['day_of_week'],
                'start_time'  => $sc['start_time'],
                'end_time'    => $sc['end_time'],
            ]);
        }

        return redirect()
            ->route('admin.settings.index', ['tab' => 'schedules'])
            ->with('success', 'Horarios guardados.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function syncServices(Professional $professional, array $services): void
    {
        $syncData = collect($services)
            ->keyBy('service_id')
            ->map(fn($s) => [
                'duration_minutes' => $s['duration_minutes'],
                'price'            => $s['price'] ?? null,
            ])
            ->all();

        $professional->services()->sync($syncData);
    }
}
