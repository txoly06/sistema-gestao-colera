<?php

// Script para criar usuário administrador e atribuir permissões
// Execute com: php criar-admin.php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Criar usuário administrador se não existir
$user = \App\Models\User::where('email', 'admin@example.com')->first();
if (!$user) {
    $user = new \App\Models\User();
    $user->name = 'Administrador';
    $user->email = 'admin@example.com';
    $user->forceFill([
        'password' => \Illuminate\Support\Facades\Hash::make('senha123')
    ]);
    $user->save();
    echo "Usuário admin criado com sucesso!\n";
} else {
    echo "Usuário admin já existe!\n";
}

// Cria a role admin se não existir
$adminRole = Spatie\Permission\Models\Role::where('name', 'admin')->where('guard_name', 'sanctum')->first();
if (!$adminRole) {
    $adminRole = Spatie\Permission\Models\Role::create([
        'name' => 'admin',
        'guard_name' => 'sanctum',
    ]);
    echo "Role 'admin' criada com sucesso!\n";
} else {
    echo "Role 'admin' já existe!\n";
}

// Busca todas as permissões existentes
$todasPermissoes = Spatie\Permission\Models\Permission::where('guard_name', 'sanctum')->get();
echo "Encontradas " . $todasPermissoes->count() . " permissões no sistema.\n";

// Atribui todas as permissões à role admin
$adminRole->syncPermissions($todasPermissoes);
echo "Permissões atribuídas à role 'admin'!\n";

// Atribui a role admin ao usuário
$user->assignRole($adminRole);
echo "Usuário '{$user->name}' recebeu a role 'admin'!\n";

// Limpa o cache de permissões
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
echo "Cache de permissões limpo!\n";

// Cria um token de acesso para o usuário
$token = $user->createToken('admin-token')->plainTextToken;
echo "\n===== TOKEN DE ACESSO =====\n";
echo $token . "\n";
echo "=============================\n";
echo "Use este token para autenticar no Swagger com o prefixo 'Bearer '\n";

echo "\nProcesso finalizado com sucesso!\n";
