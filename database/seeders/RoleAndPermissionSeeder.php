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
        Permission::create(['name' => 'gabinetes.listar']);
        Permission::create(['name' => 'gabinetes.visualizar']);
        Permission::create(['name' => 'gabinetes.criar']);
        Permission::create(['name' => 'gabinetes.editar']);
        Permission::create(['name' => 'gabinetes.eliminar']);
        
        // Unidades de Saúde
        Permission::create(['name' => 'ver unidades-saude']);
        Permission::create(['name' => 'criar unidades-saude']);
        Permission::create(['name' => 'editar unidades-saude']);
        Permission::create(['name' => 'eliminar unidades-saude']);
        Permission::create(['name' => 'restaurar unidades-saude']);
        Permission::create(['name' => 'eliminar-permanente unidades-saude']);
        
        // Pacientes
        Permission::create(['name' => 'ver pacientes']);
        Permission::create(['name' => 'criar pacientes']);
        Permission::create(['name' => 'editar pacientes']);
        Permission::create(['name' => 'eliminar pacientes']);
        Permission::create(['name' => 'restaurar pacientes']);
        Permission::create(['name' => 'eliminar-permanente pacientes']);
        Permission::create(['name' => 'ver-dados-confidenciais pacientes']);
        
        // Pontos de Cuidados de Emergência
        Permission::create(['name' => 'ver pontos-cuidado']);
        Permission::create(['name' => 'criar pontos-cuidado']);
        Permission::create(['name' => 'editar pontos-cuidado']);
        Permission::create(['name' => 'eliminar pontos-cuidado']);
        Permission::create(['name' => 'atualizar-prontidao pontos-cuidado']);
        Permission::create(['name' => 'atualizar-capacidade pontos-cuidado']);
        
        // Fichas Clínicas
        Permission::create(['name' => 'fichas.listar']);
        Permission::create(['name' => 'fichas.visualizar']);
        Permission::create(['name' => 'fichas.criar']);
        Permission::create(['name' => 'fichas.editar']);
        Permission::create(['name' => 'fichas.eliminar']);
        
        // Casos de Cólera
        Permission::create(['name' => 'casos.listar']);
        Permission::create(['name' => 'casos.visualizar']);
        Permission::create(['name' => 'casos.criar']);
        Permission::create(['name' => 'casos.editar']);
        Permission::create(['name' => 'casos.eliminar']);
        Permission::create(['name' => 'casos.relatorios']);
        
        // Veículos
        Permission::create(['name' => 'veiculos.listar']);
        Permission::create(['name' => 'veiculos.visualizar']);
        Permission::create(['name' => 'veiculos.criar']);
        Permission::create(['name' => 'veiculos.editar']);
        Permission::create(['name' => 'veiculos.eliminar']);
        Permission::create(['name' => 'veiculos.rastrear']);
        
        // Encaminhamentos
        Permission::create(['name' => 'encaminhamentos.listar']);
        Permission::create(['name' => 'encaminhamentos.visualizar']);
        Permission::create(['name' => 'encaminhamentos.criar']);
        Permission::create(['name' => 'encaminhamentos.editar']);
        Permission::create(['name' => 'encaminhamentos.eliminar']);
        
        // Dashboard e Relatórios
        Permission::create(['name' => 'dashboard.visualizar']);
        Permission::create(['name' => 'relatorios.gerar']);
        Permission::create(['name' => 'relatorios.exportar']);
        Permission::create(['name' => 'mapas.visualizar']);
        
        // Utilizadores e Papéis
        Permission::create(['name' => 'usuarios.listar']);
        Permission::create(['name' => 'usuarios.visualizar']);
        Permission::create(['name' => 'usuarios.criar']);
        Permission::create(['name' => 'usuarios.editar']);
        Permission::create(['name' => 'usuarios.eliminar']);
        Permission::create(['name' => 'papeis.gerenciar']);
        
        // Criar papéis e atribuir permissões
        
        // Administrador - acesso completo
        $roleAdmin = Role::create(['name' => 'Administrador']);
        $roleAdmin->givePermissionTo(Permission::all());
        
        // Gestor - acesso de gestão mas não pode eliminar nem acessar configurações avançadas
        $roleGestor = Role::create(['name' => 'Gestor']);
        $roleGestor->givePermissionTo([
            // Visualização geral
            'dashboard.visualizar', 'relatorios.gerar', 'relatorios.exportar', 'mapas.visualizar',
            // Gestão de entidades
            'gabinetes.listar', 'gabinetes.visualizar', 'gabinetes.criar', 'gabinetes.editar',
            'unidades.listar', 'unidades.visualizar', 'unidades.criar', 'unidades.editar',
            'pacientes.listar', 'pacientes.visualizar',
            'casos.listar', 'casos.visualizar', 'casos.relatorios',
            'veiculos.listar', 'veiculos.visualizar', 'veiculos.rastrear',
            'encaminhamentos.listar', 'encaminhamentos.visualizar',
            'usuarios.listar', 'usuarios.visualizar', 'usuarios.criar', 'usuarios.editar'
        ]);
        
        // Profissional de Saúde - acesso clínico
        $roleProfissionalSaude = Role::create(['name' => 'Profissional_Saude']);
        $roleProfissionalSaude->givePermissionTo([
            'pacientes.listar', 'pacientes.visualizar', 'pacientes.criar', 'pacientes.editar', 'pacientes.ver_dados_confidenciais',
            'fichas.listar', 'fichas.visualizar', 'fichas.criar', 'fichas.editar',
            'casos.listar', 'casos.visualizar', 'casos.criar', 'casos.editar',
            'encaminhamentos.listar', 'encaminhamentos.visualizar', 'encaminhamentos.criar',
            'dashboard.visualizar', 'mapas.visualizar'
        ]);
        
        // Técnico - suporte técnico e logística
        $roleTecnico = Role::create(['name' => 'Tecnico']);
        $roleTecnico->givePermissionTo([
            'unidades.listar', 'unidades.visualizar',
            'veiculos.listar', 'veiculos.visualizar', 'veiculos.editar', 'veiculos.rastrear',
            'encaminhamentos.listar', 'encaminhamentos.visualizar',
            'mapas.visualizar'
        ]);
        
        // Condutor - apenas para gestão de veículos
        $roleCondutor = Role::create(['name' => 'Condutor']);
        $roleCondutor->givePermissionTo([
            'veiculos.visualizar', 'veiculos.rastrear',
            'encaminhamentos.visualizar',
            'mapas.visualizar'
        ]);
        
        // Paciente - apenas acessa informações próprias
        $rolePaciente = Role::create(['name' => 'Paciente']);
        $rolePaciente->givePermissionTo([
            'pacientes.visualizar', // Apenas próprios dados
            'fichas.visualizar',    // Apenas próprias fichas
            'casos.visualizar'      // Apenas próprios casos
        ]);
    }
}
