<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Professional;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DentalClinicSeeder extends Seeder
{
    // day_of_week: 0=Sun 1=Mon 2=Tue 3=Wed 4=Thu 5=Fri 6=Sat

    public function run(): void
    {
        $org = Organization::updateOrCreate(
            ['wa_phone_number' => '15550436116'],
            [
                'name' => 'Clínica Dental Sonrisa',
                'timezone' => 'America/Guayaquil',
                'cancellation_hours_min' => 24,
            ]
        );

        // ── SERVICES ────────────────────────────────────────────────
        $services = [];
        $serviceData = [
            ['name' => 'Consulta General',              'description' => 'Revisión y diagnóstico dental inicial'],
            ['name' => 'Limpieza Dental',               'description' => 'Profilaxis y eliminación de sarro'],
            ['name' => 'Blanqueamiento Dental',         'description' => 'Blanqueamiento profesional en consultorio'],
            ['name' => 'Ortodoncia',                    'description' => 'Brackets y corrección de mordida'],
            ['name' => 'Endodoncia',                    'description' => 'Tratamiento de conductos radiculares'],
            ['name' => 'Implante Dental',               'description' => 'Colocación de implante de titanio'],
            ['name' => 'Extracción Simple',             'description' => 'Extracción de pieza dental sin complicaciones'],
            ['name' => 'Extracción de Muela del Juicio', 'description' => 'Extracción quirúrgica de terceros molares'],
            ['name' => 'Odontopediatría',               'description' => 'Atención dental para niños'],
            ['name' => 'Periodoncia',                   'description' => 'Tratamiento de encías y hueso alveolar'],
        ];

        foreach ($serviceData as $data) {
            $services[$data['name']] = Service::updateOrCreate(
                ['organization_id' => $org->id, 'name' => $data['name']],
                ['description' => $data['description'], 'active' => true]
            );
        }

        // ── PROFESSIONALS ────────────────────────────────────────────
        $this->seedDraMartinez($org, $services);
        $this->seedDrRamirez($org, $services);
        $this->seedDraVega($org, $services);
        $this->seedDrSolano($org, $services);

        // ── ADMIN USER ──────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'admin@sonrisa.ec'],
            [
                'name' => 'Admin Sonrisa',
                'password' => Hash::make('password'),
                'organization_id' => $org->id,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Clínica Dental Sonrisa seeded — org_id: {$org->id}");
        $this->command->info("Admin user: admin@sonrisa.ec / password");
    }

    // ── Dra. Martínez — Odontología general + limpieza + extracciones + niños
    private function seedDraMartinez(Organization $org, array $services): void
    {
        $prof = Professional::updateOrCreate(
            ['organization_id' => $org->id, 'name' => 'Dra. Carmen Martínez'],
            ['specialty' => 'Odontología General', 'active' => true]
        );

        $prof->services()->syncWithoutDetaching([
            $services['Consulta General']->id       => ['duration_minutes' => 30, 'price' => 35.00],
            $services['Limpieza Dental']->id        => ['duration_minutes' => 45, 'price' => 50.00],
            $services['Extracción Simple']->id      => ['duration_minutes' => 30, 'price' => 40.00],
            $services['Odontopediatría']->id        => ['duration_minutes' => 30, 'price' => 35.00],
        ]);

        // Lun-Vie 8:00-13:00, más tarde Lun/Mié/Vie 15:00-18:00
        $this->attachSchedules($prof, [1, 2, 3, 4, 5], '08:00', '13:00');
        $this->attachSchedules($prof, [1, 3, 5], '15:00', '18:00');
    }

    // ── Dr. Ramírez — Endodoncia e implantología
    private function seedDrRamirez(Organization $org, array $services): void
    {
        $prof = Professional::updateOrCreate(
            ['organization_id' => $org->id, 'name' => 'Dr. Andrés Ramírez'],
            ['specialty' => 'Endodoncia e Implantología', 'active' => true]
        );

        $prof->services()->syncWithoutDetaching([
            $services['Consulta General']->id               => ['duration_minutes' => 30, 'price' => 40.00],
            $services['Endodoncia']->id                     => ['duration_minutes' => 90, 'price' => 180.00],
            $services['Implante Dental']->id                => ['duration_minutes' => 120, 'price' => 600.00],
            $services['Extracción de Muela del Juicio']->id => ['duration_minutes' => 60, 'price' => 120.00],
        ]);

        // Mar-Jue 9:00-14:00 y 15:00-18:00, Vie 9:00-13:00
        $this->attachSchedules($prof, [2, 3, 4], '09:00', '14:00');
        $this->attachSchedules($prof, [2, 3, 4], '15:00', '18:00');
        $this->attachSchedules($prof, [5], '09:00', '13:00');
    }

    // ── Dra. Vega — Ortodoncia y estética
    private function seedDraVega(Organization $org, array $services): void
    {
        $prof = Professional::updateOrCreate(
            ['organization_id' => $org->id, 'name' => 'Dra. Lucía Vega'],
            ['specialty' => 'Ortodoncia y Estética Dental', 'active' => true]
        );

        $prof->services()->syncWithoutDetaching([
            $services['Consulta General']->id      => ['duration_minutes' => 30, 'price' => 35.00],
            $services['Ortodoncia']->id            => ['duration_minutes' => 60, 'price' => 80.00],
            $services['Blanqueamiento Dental']->id => ['duration_minutes' => 90, 'price' => 150.00],
        ]);

        // Lun-Mié 10:00-14:00 y 16:00-19:00, Sáb 9:00-13:00
        $this->attachSchedules($prof, [1, 2, 3], '10:00', '14:00');
        $this->attachSchedules($prof, [1, 2, 3], '16:00', '19:00');
        $this->attachSchedules($prof, [6], '09:00', '13:00');
    }

    // ── Dr. Solano — Periodoncia
    private function seedDrSolano(Organization $org, array $services): void
    {
        $prof = Professional::updateOrCreate(
            ['organization_id' => $org->id, 'name' => 'Dr. Miguel Solano'],
            ['specialty' => 'Periodoncia', 'active' => true]
        );

        $prof->services()->syncWithoutDetaching([
            $services['Consulta General']->id => ['duration_minutes' => 30, 'price' => 40.00],
            $services['Periodoncia']->id      => ['duration_minutes' => 60, 'price' => 90.00],
            $services['Limpieza Dental']->id  => ['duration_minutes' => 60, 'price' => 70.00],
        ]);

        // Jue-Vie 8:00-13:00 y 14:00-17:00, Sáb 9:00-12:00
        $this->attachSchedules($prof, [4, 5], '08:00', '13:00');
        $this->attachSchedules($prof, [4, 5], '14:00', '17:00');
        $this->attachSchedules($prof, [6], '09:00', '12:00');
    }

    private function attachSchedules(Professional $prof, array $days, string $start, string $end): void
    {
        foreach ($days as $day) {
            Schedule::updateOrCreate(
                ['professional_id' => $prof->id, 'day_of_week' => $day, 'start_time' => $start],
                ['end_time' => $end]
            );
        }
    }
}
