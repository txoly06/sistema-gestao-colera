<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MigrarPermissoesGuard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:migrate-guard {--old=web} {--new=sanctum}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra todas as permissões de um guard para outro';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $oldGuard = $this->option('old');
        $newGuard = $this->option('new');

        // Mostrar o que estamos prestes a fazer
        $this->info("Migrando permissões do guard '{$oldGuard}' para '{$newGuard}'...");
        
        // Passo 1: Atualizar todas as permissões para o novo guard
        $permissoes = Permission::where('guard_name', $oldGuard)->get();
        $total = $permissoes->count();
        $this->info("Encontradas {$total} permissões com guard '{$oldGuard}'.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();
        
        foreach ($permissoes as $permissao) {
            $this->line("Migrando permissão: {$permissao->name}");
            
            // Verificar se já existe uma permissão com o mesmo nome e novo guard
            $existingPermission = Permission::where('name', $permissao->name)
                ->where('guard_name', $newGuard)
                ->first();
                
            if ($existingPermission) {
                $this->line("Permissão '{$permissao->name}' já existe com guard '{$newGuard}'. Removendo duplicata.");
                // Transferir todas as relações para a permissão existente
                $this->transferirRelacoes($permissao, $existingPermission);
                // Remover a permissão antiga
                $permissao->delete();
            } else {
                // Atualizar o guard da permissão
                $permissao->guard_name = $newGuard;
                $permissao->save();
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        // Passo 2: Atualizar todas as roles para o novo guard
        $roles = Role::where('guard_name', $oldGuard)->get();
        $totalRoles = $roles->count();
        $this->info("Encontradas {$totalRoles} roles com guard '{$oldGuard}'.");
        
        $bar = $this->output->createProgressBar($totalRoles);
        $bar->start();
        
        foreach ($roles as $role) {
            $this->line("Migrando role: {$role->name}");
            
            // Verificar se já existe uma role com o mesmo nome e novo guard
            $existingRole = Role::where('name', $role->name)
                ->where('guard_name', $newGuard)
                ->first();
                
            if ($existingRole) {
                $this->line("Role '{$role->name}' já existe com guard '{$newGuard}'. Removendo duplicata.");
                // Transferir todas as relações para a role existente
                $this->transferirRelacoesRole($role, $existingRole);
                // Remover a role antiga
                $role->delete();
            } else {
                // Atualizar o guard da role
                $role->guard_name = $newGuard;
                $role->save();
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        // Limpar o cache das permissões
        $this->call('permission:cache-reset');
        
        $this->info("Migração concluída! Todas as permissões e roles foram atualizadas para o guard '{$newGuard}'.");
    }

    /**
     * Transfere as relações de uma permissão para outra
     * 
     * @param Permission $origem
     * @param Permission $destino
     */
    protected function transferirRelacoes($origem, $destino)
    {
        // Transferir relações model_has_permissions
        DB::table('model_has_permissions')
            ->where('permission_id', $origem->id)
            ->update(['permission_id' => $destino->id]);
            
        // Transferir relações role_has_permissions
        DB::table('role_has_permissions')
            ->where('permission_id', $origem->id)
            ->update(['permission_id' => $destino->id]);
    }
    
    /**
     * Transfere as relações de uma role para outra
     * 
     * @param Role $origem
     * @param Role $destino
     */
    protected function transferirRelacoesRole($origem, $destino)
    {
        // Transferir relações model_has_roles
        DB::table('model_has_roles')
            ->where('role_id', $origem->id)
            ->update(['role_id' => $destino->id]);
            
        // Transferir relações role_has_permissions
        DB::table('role_has_permissions')
            ->where('role_id', $origem->id)
            ->update(['role_id' => $destino->id]);
    }
}
