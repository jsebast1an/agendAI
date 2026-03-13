<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Patient;
use Illuminate\Support\Facades\Log;

class PatientResolverService
{
    public function resolve(Organization $org, string $waId): Patient
    {
        try {
            return Patient::firstOrCreate(
                ['organization_id' => $org->id, 'wa_id' => $waId],
                ['phone_number' => $waId]
            );
        } catch (\Throwable $e) {
            Log::channel('api')->error('Patient resolution failed', [
                'org_id' => $org->id,
                'wa_id' => $waId,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
