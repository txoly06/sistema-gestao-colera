<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Rodar o seeder de papéis e permissões primeiro
        $this->call(RoleAndPermissionSeeder::class);
        
        // Criar utilizador administrador de teste
        $admin = User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@sistemacolera.gov.ao',
            'password' => bcrypt('password'),
        ]);
        
        // Atribuir papel de administrador
        $admin->assignRole('Administrador');
        
        // Criar utilizador gestor de teste
        $gestor = User::factory()->create([
            'name' => 'Gestor Provincial',
            'email' => 'gestor@sistemacolera.gov.ao',
            'password' => bcrypt('password'),
        ]);
        
        // Atribuir papel de gestor
        $gestor->assignRole('Gestor');
        
        // Criar utilizador médico de teste
        $medico = User::factory()->create([
            'name' => 'Dr. Miranda',
            'email' => 'medico@sistemacolera.gov.ao',
            'password' => bcrypt('password'),
        ]);
        
        // Atribuir papel de profissional de saúde
        $medico->assignRole('Profissional_Saude');
    }
}
