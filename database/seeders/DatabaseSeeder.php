<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesEtPermissionsSeeder::class,
            ReferentielsSeeder::class,
        ]);

        $dsi = Service::query()->where('code', 'DSI')->firstOrFail();

        $admin = User::query()->create([
            'matricule' => 'ADMIN-0001',
            'nom' => 'Administrateur',
            'prenom' => 'Justicia',
            'email' => 'admin@justicia.test',
            'password' => 'password',
            'service_id' => $dsi->id,
            'actif' => true,
        ]);

        $admin->assignRole('administrateur');
    }
}
