<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\Log;

class TenantResolverService
{
    public function resolve(string $waBusinessNumber): ?Organization
    {
        try {
            return Organization::where('wa_phone_number', $waBusinessNumber)->first();
        } catch (\Throwable $e) {
            Log::channel('api')->error('Tenant resolution failed', [
                'wa_business_number' => $waBusinessNumber,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
