<?php

namespace Database\Seeders;

use App\Models\Role;
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
        // Exécution des seeders
        $this->call([
            RoleSeeder::class,
        ]);
        
        // Création de l'utilisateur administrateur initial
        $adminRole = Role::where('name', 'admin')->first();
        
        User::create([
            'name' => 'Administrateur',
            'email' => 'admin@ticketing.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);
        
        // Création d'un utilisateur agent de démonstration
        $agentRole = Role::where('name', 'agent')->first();
        
        User::create([
            'name' => 'Agent Support',
            'email' => 'agent@ticketing.com',
            'password' => Hash::make('password'),
            'role_id' => $agentRole->id,
            'email_verified_at' => now(),
        ]);
        
        // Création d'un utilisateur client de démonstration
        $clientRole = Role::where('name', 'client')->first();
        
        User::create([
            'name' => 'Client Test',
            'email' => 'client@example.com',
            'password' => Hash::make('password'),
            'role_id' => $clientRole->id,
            'email_verified_at' => now(),
        ]);
    }
}
