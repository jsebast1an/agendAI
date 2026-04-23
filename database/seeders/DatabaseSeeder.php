<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Platform superadmin — no organization
        User::updateOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name' => 'Admin Demo',
                'password' => Hash::make('password'),
                'organization_id' => null,
                'role' => 'superadmin',
                'email_verified_at' => now(),
            ]
        );

        $this->call(DentalClinicSeeder::class);
    }
}
