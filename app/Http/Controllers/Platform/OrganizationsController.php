<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationsController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'wa_phone_number' => 'required|string|max:32|unique:organizations',
            'timezone' => 'required|string|max:50',
            'cancellation_hours_min' => 'required|integer|min:0|max:168',
            'type' => 'required|in:production,test',
        ]);

        Organization::create($validated);

        return redirect()->route('platform.dashboard', ['tab' => 'orgs'])
            ->with('success', 'Organización creada.');
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'wa_phone_number' => "required|string|max:32|unique:organizations,wa_phone_number,{$organization->id}",
            'timezone' => 'required|string|max:50',
            'cancellation_hours_min' => 'required|integer|min:0|max:168',
            'type' => 'required|in:production,test',
        ]);

        $organization->update($validated);

        return redirect()->route('platform.dashboard', ['tab' => 'orgs'])
            ->with('success', 'Organización actualizada.');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        $organization->delete();

        return redirect()->route('platform.dashboard', ['tab' => 'orgs'])
            ->with('success', 'Organización eliminada.');
    }
}
