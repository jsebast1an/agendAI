<?php

namespace Tests\Feature\Admin;

use App\Models\Organization;
use App\Models\Professional;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::create([
            'name'             => 'Test Clinic',
            'wa_phone_number'  => '593991111111',
            'timezone'         => 'America/Guayaquil',
        ]);

        $this->user = User::create([
            'name'            => 'Admin User',
            'email'           => 'admin@test.com',
            'password'        => bcrypt('password'),
            'organization_id' => $this->org->id,
        ]);
    }

    // ── Auth guard ────────────────────────────────────────────────────────────

    public function test_unauthenticated_user_is_redirected_from_settings(): void
    {
        $response = $this->get(route('admin.settings.index'));
        $response->assertRedirect(route('login'));
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_settings_index_renders_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.settings.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Settings/Index'));
    }

    public function test_settings_index_only_returns_own_org_data(): void
    {
        $otherOrg = Organization::create([
            'name'            => 'Other Clinic',
            'wa_phone_number' => '593992222222',
            'timezone'        => 'America/Guayaquil',
        ]);
        Professional::create([
            'organization_id' => $otherOrg->id,
            'name'            => 'Dr. Other',
            'active'          => true,
        ]);
        Professional::create([
            'organization_id' => $this->org->id,
            'name'            => 'Dr. Mine',
            'active'          => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.settings.index'));
        $response->assertInertia(fn ($page) => $page
            ->has('professionals', 1)
            ->where('professionals.0.name', 'Dr. Mine')
        );
    }

    // ── Professionals — store ─────────────────────────────────────────────────

    public function test_store_professional_creates_record(): void
    {
        $service = Service::create([
            'organization_id' => $this->org->id,
            'name'            => 'Consulta',
            'active'          => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.settings.professionals.store'), [
            'name'      => 'Dr. Martinez',
            'specialty' => 'Cardiologia',
            'active'    => true,
            'services'  => [
                ['service_id' => $service->id, 'duration_minutes' => 30, 'price' => 50.00],
            ],
        ]);

        $response->assertRedirect(route('admin.settings.index', ['tab' => 'professionals']));
        $this->assertDatabaseHas('professionals', [
            'organization_id' => $this->org->id,
            'name'            => 'Dr. Martinez',
            'specialty'       => 'Cardiologia',
        ]);
        $professional = Professional::where('name', 'Dr. Martinez')->first();
        $this->assertDatabaseHas('professional_service', [
            'professional_id'  => $professional->id,
            'service_id'       => $service->id,
            'duration_minutes' => 30,
        ]);
    }

    public function test_store_professional_requires_name(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.settings.professionals.store'), [
            'specialty' => 'Cardiologia',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('professionals', 0);
    }

    public function test_store_professional_rejects_service_from_other_org(): void
    {
        $otherOrg = Organization::create([
            'name'            => 'Other',
            'wa_phone_number' => '593993333333',
            'timezone'        => 'America/Guayaquil',
        ]);
        $foreignService = Service::create([
            'organization_id' => $otherOrg->id,
            'name'            => 'Foreign Service',
            'active'          => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.settings.professionals.store'), [
            'name'     => 'Dr. X',
            'services' => [
                ['service_id' => $foreignService->id, 'duration_minutes' => 30],
            ],
        ]);

        $response->assertSessionHasErrors('services.0.service_id');
    }

    // ── Professionals — update ────────────────────────────────────────────────

    public function test_update_professional_changes_name_and_specialty(): void
    {
        $professional = Professional::create([
            'organization_id' => $this->org->id,
            'name'            => 'Dr. Old',
            'specialty'       => 'General',
            'active'          => true,
        ]);

        $this->actingAs($this->user)->put(
            route('admin.settings.professionals.update', $professional),
            ['name' => 'Dr. New', 'specialty' => 'Pediatria', 'active' => true]
        );

        $this->assertDatabaseHas('professionals', [
            'id'        => $professional->id,
            'name'      => 'Dr. New',
            'specialty' => 'Pediatria',
        ]);
    }

    public function test_update_professional_from_other_org_returns_403(): void
    {
        $otherOrg = Organization::create([
            'name'            => 'Other',
            'wa_phone_number' => '593994444444',
            'timezone'        => 'America/Guayaquil',
        ]);
        $foreignProfessional = Professional::create([
            'organization_id' => $otherOrg->id,
            'name'            => 'Dr. Foreign',
            'active'          => true,
        ]);

        $response = $this->actingAs($this->user)->put(
            route('admin.settings.professionals.update', $foreignProfessional),
            ['name' => 'Hacked', 'active' => true]
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('professionals', ['name' => 'Dr. Foreign']);
    }

    // ── Professionals — destroy ───────────────────────────────────────────────

    public function test_destroy_professional_deletes_record(): void
    {
        $professional = Professional::create([
            'organization_id' => $this->org->id,
            'name'            => 'Dr. ToDelete',
            'active'          => true,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('admin.settings.professionals.destroy', $professional));

        $response->assertRedirect(route('admin.settings.index', ['tab' => 'professionals']));
        $this->assertDatabaseMissing('professionals', ['id' => $professional->id]);
    }

    public function test_destroy_professional_from_other_org_returns_403(): void
    {
        $otherOrg = Organization::create([
            'name'            => 'Other',
            'wa_phone_number' => '593995555555',
            'timezone'        => 'America/Guayaquil',
        ]);
        $foreignProfessional = Professional::create([
            'organization_id' => $otherOrg->id,
            'name'            => 'Dr. Foreign',
            'active'          => true,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('admin.settings.professionals.destroy', $foreignProfessional));

        $response->assertStatus(403);
        $this->assertDatabaseHas('professionals', ['id' => $foreignProfessional->id]);
    }

    // ── Services — store ──────────────────────────────────────────────────────

    public function test_store_service_creates_record(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.settings.services.store'), [
            'name'        => 'Limpieza Dental',
            'description' => 'Limpieza profesional',
            'active'      => true,
        ]);

        $response->assertRedirect(route('admin.settings.index', ['tab' => 'services']));
        $this->assertDatabaseHas('services', [
            'organization_id' => $this->org->id,
            'name'            => 'Limpieza Dental',
        ]);
    }

    public function test_store_service_requires_name(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.settings.services.store'), [
            'description' => 'Sin nombre',
        ]);

        $response->assertSessionHasErrors('name');
    }

    // ── Services — update ─────────────────────────────────────────────────────

    public function test_update_service_changes_name(): void
    {
        $service = Service::create([
            'organization_id' => $this->org->id,
            'name'            => 'Old Service',
            'active'          => true,
        ]);

        $this->actingAs($this->user)->put(
            route('admin.settings.services.update', $service),
            ['name' => 'New Service', 'active' => true]
        );

        $this->assertDatabaseHas('services', ['id' => $service->id, 'name' => 'New Service']);
    }

    public function test_update_service_from_other_org_returns_403(): void
    {
        $otherOrg = Organization::create([
            'name'            => 'Other',
            'wa_phone_number' => '593996666666',
            'timezone'        => 'America/Guayaquil',
        ]);
        $foreignService = Service::create([
            'organization_id' => $otherOrg->id,
            'name'            => 'Foreign Service',
            'active'          => true,
        ]);

        $response = $this->actingAs($this->user)->put(
            route('admin.settings.services.update', $foreignService),
            ['name' => 'Hacked', 'active' => true]
        );

        $response->assertStatus(403);
    }

    // ── Services — destroy ────────────────────────────────────────────────────

    public function test_destroy_service_deletes_record(): void
    {
        $service = Service::create([
            'organization_id' => $this->org->id,
            'name'            => 'To Delete',
            'active'          => true,
        ]);

        $this->actingAs($this->user)->delete(route('admin.settings.services.destroy', $service));

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_destroy_service_from_other_org_returns_403(): void
    {
        $otherOrg = Organization::create([
            'name'            => 'Other',
            'wa_phone_number' => '593997777777',
            'timezone'        => 'America/Guayaquil',
        ]);
        $foreignService = Service::create([
            'organization_id' => $otherOrg->id,
            'name'            => 'Foreign',
            'active'          => true,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('admin.settings.services.destroy', $foreignService));

        $response->assertStatus(403);
    }

    // ── Schedules — update ────────────────────────────────────────────────────

    public function test_update_schedules_replaces_all_schedules_for_professional(): void
    {
        $professional = Professional::create([
            'organization_id' => $this->org->id,
            'name'            => 'Dr. Sched',
            'active'          => true,
        ]);
        Schedule::create([
            'professional_id' => $professional->id,
            'day_of_week'     => 0,
            'start_time'      => '08:00',
            'end_time'        => '12:00',
        ]);

        $this->actingAs($this->user)->put(
            route('admin.settings.schedules.update', $professional),
            [
                'schedules' => [
                    ['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00'],
                    ['day_of_week' => 3, 'start_time' => '09:00', 'end_time' => '17:00'],
                ],
            ]
        );

        $this->assertDatabaseMissing('schedules', ['day_of_week' => 0, 'professional_id' => $professional->id]);
        $this->assertDatabaseHas('schedules', ['day_of_week' => 1, 'professional_id' => $professional->id]);
        $this->assertDatabaseHas('schedules', ['day_of_week' => 3, 'professional_id' => $professional->id]);
        $this->assertDatabaseCount('schedules', 2);
    }

    public function test_update_schedules_validates_time_format(): void
    {
        $professional = Professional::create([
            'organization_id' => $this->org->id,
            'name'            => 'Dr. Sched',
            'active'          => true,
        ]);

        $response = $this->actingAs($this->user)->put(
            route('admin.settings.schedules.update', $professional),
            ['schedules' => [['day_of_week' => 1, 'start_time' => 'bad-time', 'end_time' => '17:00']]]
        );

        $response->assertSessionHasErrors('schedules.0.start_time');
    }

    public function test_update_schedules_from_other_org_returns_403(): void
    {
        $otherOrg = Organization::create([
            'name'            => 'Other',
            'wa_phone_number' => '593998888888',
            'timezone'        => 'America/Guayaquil',
        ]);
        $foreignProfessional = Professional::create([
            'organization_id' => $otherOrg->id,
            'name'            => 'Dr. Foreign',
            'active'          => true,
        ]);

        $response = $this->actingAs($this->user)->put(
            route('admin.settings.schedules.update', $foreignProfessional),
            ['schedules' => []]
        );

        $response->assertStatus(403);
    }
}
