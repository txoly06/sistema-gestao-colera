<?php

namespace Database\Seeders;

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
        
        // Rodar o seeder de dados de teste (utilizadores, permissões e dados de demonstração)
        $this->call(TestDataSeeder::class);
        
        // Nota: TestDataSeeder já inclui a criação de utilizadores com os papéis específicos,
        // pacientes, unidades de saúde, pontos de cuidado, veículos e triagens.
    }
}
