<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Rôle Administrateur
        Role::create([
            'name' => 'admin',
            'permissions' => [
                'tickets.view.all',
                'tickets.create',
                'tickets.edit',
                'tickets.delete',
                'tickets.assign',
                'tickets.comment',
                'tickets.reply',
                'users.view',
                'users.create',
                'users.edit',
                'users.delete',
                'workflows.view',
                'workflows.create',
                'workflows.edit',
                'workflows.delete',
                'labels.view',
                'labels.create',
                'labels.edit',
                'labels.delete',
                'companies.view',
                'companies.create',
                'companies.edit',
                'companies.delete',
                'reports.view',
            ],
        ]);

        // Rôle Agent
        Role::create([
            'name' => 'agent',
            'permissions' => [
                'tickets.view.assigned',
                'tickets.create',
                'tickets.edit',
                'tickets.assign',
                'tickets.comment',
                'tickets.reply',
                'reports.view',
            ],
        ]);

        // Rôle Client
        Role::create([
            'name' => 'client',
            'permissions' => [
                'tickets.view.own',
                'tickets.create',
                'tickets.reply',
            ],
        ]);
    }
}
