<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpa cache de permissões
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Criar permissões por módulo
        
        // Gabinetes Provinciais
        Permission::updateOrCreate(['name' => 'gabinetes.listar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'gabinetes.visualizar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'gabinetes.criar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'gabinetes.editar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'gabinetes.eliminar', 'guard_name' => 'sanctum']);
        
        // Unidades de Saúde
        Permission::updateOrCreate(['name' => 'ver unidades-saude', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'criar unidades-saude', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'editar unidades-saude', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'eliminar unidades-saude', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'restaurar unidades-saude', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'eliminar-permanente unidades-saude', 'guard_name' => 'sanctum']);
        
        // Pacientes
        Permission::updateOrCreate(['name' => 'ver pacientes', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'criar pacientes', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'editar pacientes', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'eliminar pacientes', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'restaurar pacientes', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'eliminar-permanente pacientes', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'ver-dados-confidenciais pacientes', 'guard_name' => 'sanctum']);
        
        // Pontos de Cuidados de Emergência
        Permission::updateOrCreate(['name' => 'ver pontos-cuidado', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'criar pontos-cuidado', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'editar pontos-cuidado', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'eliminar pontos-cuidado', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'atualizar-prontidao pontos-cuidado', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'atualizar-capacidade pontos-cuidado', 'guard_name' => 'sanctum']);
        
        // Fichas Clínicas
        Permission::updateOrCreate(['name' => 'fichas.listar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'fichas.visualizar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'fichas.criar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'fichas.editar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'fichas.eliminar', 'guard_name' => 'sanctum']);
        
        // Casos de Cólera
        Permission::updateOrCreate(['name' => 'casos.listar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'casos.visualizar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'casos.criar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'casos.editar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'casos.eliminar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'casos.relatorios', 'guard_name' => 'sanctum']);
        
        // Veículos
        Permission::updateOrCreate(['name' => 'veiculos.listar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'veiculos.visualizar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'veiculos.criar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'veiculos.editar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'veiculos.eliminar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'veiculos.rastrear', 'guard_name' => 'sanctum']);
        
        // Encaminhamentos
        Permission::updateOrCreate(['name' => 'encaminhamentos.listar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'encaminhamentos.visualizar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'encaminhamentos.criar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'encaminhamentos.editar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'encaminhamentos.eliminar', 'guard_name' => 'sanctum']);
        
        // Dashboard e Relatórios
        Permission::updateOrCreate(['name' => 'dashboard.visualizar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'relatorios.gerar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'relatorios.exportar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'mapas.visualizar', 'guard_name' => 'sanctum']);
        
        // Utilizadores e Papéis
        Permission::updateOrCreate(['name' => 'usuarios.listar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'usuarios.visualizar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'usuarios.criar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'usuarios.editar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'usuarios.eliminar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'papeis.listar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'papeis.visualizar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'papeis.criar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'papeis.editar', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'papeis.eliminar', 'guard_name' => 'sanctum']);
        
        // Triagens
        Permission::updateOrCreate(['name' => 'ver triagens', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'criar triagens', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'editar triagens', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'eliminar triagens', 'guard_name' => 'sanctum']);
        Permission::updateOrCreate(['name' => 'encaminhar triagens', 'guard_name' => 'sanctum']);
        
        // Criar papéis e atribuir permissões
        
        // Administrador - acesso completo
        $roleAdmin = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'sanctum']);
        $roleAdmin->givePermissionTo(Permission::where('guard_name', 'sanctum')->get());
        
        // Gestor - acesso de gestão mas não pode eliminar nem acessar configurações avançadas
        $roleGestor = Role::firstOrCreate(['name' => 'Gestor', 'guard_name' => 'sanctum']);
        $roleGestor->givePermissionTo([
            // Visualização geral
            'dashboard.visualizar', 'relatorios.gerar', 'relatorios.exportar', 'mapas.visualizar',
            // Gestão de entidades
            'gabinetes.listar', 'gabinetes.visualizar', 'gabinetes.criar', 'gabinetes.editar',
            'ver unidades-saude', 'criar unidades-saude', 'editar unidades-saude',
            'ver pacientes', 'criar pacientes',
            'casos.listar', 'casos.visualizar', 'casos.relatorios',
            'veiculos.listar', 'veiculos.visualizar', 'veiculos.rastrear',
            'encaminhamentos.listar', 'encaminhamentos.visualizar',
            'usuarios.listar', 'usuarios.visualizar', 'usuarios.criar', 'usuarios.editar'
        ]);
        
        // Profissional de Saúde - acesso clínico
        $roleProfissionalSaude = Role::firstOrCreate(['name' => 'Profissional_Saude', 'guard_name' => 'sanctum']);
        $roleProfissionalSaude->givePermissionTo([
            'ver pacientes', 'criar pacientes', 'editar pacientes', 'ver-dados-confidenciais pacientes',
            'fichas.listar', 'fichas.visualizar', 'fichas.criar', 'fichas.editar',
            'casos.listar', 'casos.visualizar', 'casos.criar', 'casos.editar',
            'encaminhamentos.listar', 'encaminhamentos.visualizar', 'encaminhamentos.criar',
            'dashboard.visualizar', 'mapas.visualizar'
        ]);
        
        // Técnico - suporte técnico e logística
        $roleTecnico = Role::firstOrCreate(['name' => 'Tecnico', 'guard_name' => 'sanctum']);
        $roleTecnico->givePermissionTo([
            'ver unidades-saude',
            'veiculos.listar', 'veiculos.visualizar', 'veiculos.editar', 'veiculos.rastrear',
            'dashboard.visualizar', 'mapas.visualizar'
        ]);
        
        // Condutor - apenas para gestão de veículos
        $roleCondutor = Role::firstOrCreate(['name' => 'Condutor', 'guard_name' => 'sanctum']);
        $roleCondutor->givePermissionTo([
            'veiculos.visualizar', 'veiculos.rastrear',
            'encaminhamentos.visualizar',
            'mapas.visualizar'
        ]);
        
        // Paciente - apenas acessa informações próprias
        $rolePaciente = Role::firstOrCreate(['name' => 'Paciente', 'guard_name' => 'sanctum']);
        $rolePaciente->givePermissionTo([
            'ver pacientes', // Apenas próprios dados
            'fichas.visualizar',    // Apenas próprias fichas
            'casos.visualizar'      // Apenas próprios casos
        ]);
    }
}
