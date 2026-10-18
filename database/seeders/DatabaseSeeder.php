<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@atendly.test'], [
            'name' => 'Administrador',
            'password' => 'password',
            'role' => UserRole::Admin,
        ]);

        $this->call(SyntheticDataSeeder::class);
    }
}
