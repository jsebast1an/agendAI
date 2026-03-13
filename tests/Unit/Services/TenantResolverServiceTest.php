<?php

namespace Tests\Unit\Services;

use App\Models\Organization;
use App\Services\TenantResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_organization_from_wa_business_number(): void
    {
        $org = Organization::create([
            'name' => 'Consultorio Dr. López',
            'wa_phone_number' => '593991000001',
        ]);

        $resolver = new TenantResolverService();
        $resolved = $resolver->resolve('593991000001');

        $this->assertNotNull($resolved);
        $this->assertEquals($org->id, $resolved->id);
    }

    public function test_returns_null_for_unknown_business_number(): void
    {
        $resolver = new TenantResolverService();
        $resolved = $resolver->resolve('999999999999');

        $this->assertNull($resolved);
    }
}
