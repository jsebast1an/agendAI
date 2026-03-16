<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $orgId = $request->user()->organization_id;

        $patients = Patient::where('organization_id', $orgId)
            ->withCount('appointments')
            ->orderBy('name')
            ->paginate(20)
            ->through(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'cedula' => $p->cedula,
                'phone_number' => $p->phone_number,
                'appointments_count' => $p->appointments_count,
            ]);

        return Inertia::render('Admin/Patients/Index', [
            'patients' => $patients,
        ]);
    }
}
