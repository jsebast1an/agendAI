<?php

namespace Tests\Unit\Services;

use App\Models\Organization;
use App\Models\Patient;
use App\Services\PatientResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->org = Organization::create([
            'name' => 'Consultorio Test',
            'wa_phone_number' => '593991000001',
        ]);
    }

    public function test_resolves_existing_patient_by_wa_id(): void
    {
        $patient = Patient::create([
            'organization_id' => $this->org->id,
            'wa_id' => '593991234567',
            'name' => 'Juan Pérez',
            'phone_number' => '593991234567',
        ]);

        $resolver = new PatientResolverService();
        $resolved = $resolver->resolve($this->org, '593991234567');

        $this->assertEquals($patient->id, $resolved->id);
        $this->assertEquals('Juan Pérez', $resolved->name);
    }

    public function test_auto_registers_new_patient_if_not_found(): void
    {
        $resolver = new PatientResolverService();
        $resolved = $resolver->resolve($this->org, '593999999999');

        $this->assertNotNull($resolved);
        $this->assertEquals('593999999999', $resolved->wa_id);
        $this->assertEquals('593999999999', $resolved->phone_number);
        $this->assertEquals($this->org->id, $resolved->organization_id);
        $this->assertDatabaseHas('patients', [
            'wa_id' => '593999999999',
            'organization_id' => $this->org->id,
        ]);
    }

    public function test_does_not_duplicate_patient_on_repeated_resolve(): void
    {
        $resolver = new PatientResolverService();
        $resolver->resolve($this->org, '593991111111');
        $resolver->resolve($this->org, '593991111111');

        $this->assertCount(1, Patient::where('wa_id', '593991111111')->get());
    }
}
