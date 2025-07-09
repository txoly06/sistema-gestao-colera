<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AtribuirPermissoesUsuario extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:give-permissions {email : O email do usuário} {--all : Atribuir todas as permissões} {--admin : Atribuir role de admin} {--permissoes=* : Lista de permissões específicas para atribuir}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atribui permissões específicas ou todas as permissões a um usuário';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $atribuirTodas = $this->option('all');
        $atribuirAdmin = $this->option('admin');
        $permissoesEspecificas = $this->option('permissoes');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Usuário com email {$email} não encontrado!");
            return 1;
        }

        $this->info("Atribuindo permissões para o usuário: {$user->name} ({$user->email})");

        if ($atribuirAdmin) {
            $adminRole = Role::where('name', 'admin')->where('guard_name', 'sanctum')->first();
            
            if (!$adminRole) {
                $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
                $this->info("Role 'admin' criada com guard 'sanctum'");
            }
            
            // Atribui todas as permissões à role admin
            $todasPermissoes = Permission::where('guard_name', 'sanctum')->get();
            $adminRole->syncPermissions($todasPermissoes);
            
            $user->assignRole($adminRole);
            $this->info("Role 'admin' atribuída com todas as permissões: " . $todasPermissoes->pluck('name')->implode(', '));
            
            $this->limparCache();
            return 0;
        }

        if ($atribuirTodas) {
            $permissoes = Permission::where('guard_name', 'sanctum')->get();
            $user->syncPermissions($permissoes);
            $this->info("Todas as permissões atribuídas: " . $permissoes->pluck('name')->implode(', '));
        } else if (!empty($permissoesEspecificas)) {
            $permissoes = Permission::where('guard_name', 'sanctum')
                ->whereIn('name', $permissoesEspecificas)
                ->get();
                
            if ($permissoes->count() != count($permissoesEspecificas)) {
                $encontradas = $permissoes->pluck('name')->toArray();
                $naoEncontradas = array_diff($permissoesEspecificas, $encontradas);
                $this->warn("Algumas permissões não foram encontradas: " . implode(', ', $naoEncontradas));
            }
            
            $user->syncPermissions($permissoes);
            $this->info("Permissões específicas atribuídas: " . $permissoes->pluck('name')->implode(', '));
        } else {
            $this->info("Listando todas as permissões disponíveis:");
            $permissoes = Permission::where('guard_name', 'sanctum')->get();
            $tabela = [];
            foreach ($permissoes as $permissao) {
                $tabela[] = [$permissao->id, $permissao->name];
            }
            $this->table(['ID', 'Nome'], $tabela);
            
            $this->info("Use --all para atribuir todas as permissões");
            $this->info("Use --permissoes=nome1 --permissoes=nome2 para atribuir permissões específicas");
            $this->info("Use --admin para atribuir a role de administrador com todas as permissões");
        }

        $this->limparCache();
        return 0;
    }

    /**
     * Limpa todos os caches relacionados a permissões e configurações
     */
    protected function limparCache()
    {
        $this->call('permission:cache-reset');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('cache:clear');
        $this->info('Todos os caches foram limpos.');
    }
}
