<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Criar permissões para o módulo de encaminhamentos
        $permissions = [
            'ver encaminhamentos',
            'criar encaminhamentos',
            'editar encaminhamentos',
            'eliminar encaminhamentos',
        ];
        
        // Adicionar permissões ao banco de dados
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'sanctum']);
        }
        
        // Atribuir permissões aos papéis existentes
        $adminRole = Role::where('name', 'admin')->first();
        $gestorRole = Role::where('name', 'gestor')->first();
        $medicoRole = Role::where('name', 'medico')->first();
        
        // Admins têm todas as permissões
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }
        
        // Gestores podem ver, criar e editar
        if ($gestorRole) {
            $gestorRole->givePermissionTo([
                'ver encaminhamentos', 
                'criar encaminhamentos', 
                'editar encaminhamentos'
            ]);
        }
        
        // Médicos podem ver e criar
        if ($medicoRole) {
            $medicoRole->givePermissionTo([
                'ver encaminhamentos', 
                'criar encaminhamentos'
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover permissões do banco de dados
        $permissions = [
            'ver encaminhamentos',
            'criar encaminhamentos',
            'editar encaminhamentos',
            'eliminar encaminhamentos',
        ];
        
        foreach ($permissions as $permission) {
            $p = Permission::where('name', $permission)->first();
            if ($p) {
                $p->delete();
            }
        }
    }
};
