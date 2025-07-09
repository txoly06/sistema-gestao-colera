<?php

namespace Database\Seeders;

use App\Models\GabineteProvincial;
use App\Models\Paciente;
use App\Models\PontoCuidado;
use App\Models\Sintoma;
use App\Models\Triagem;
use App\Models\UnidadeSaude;
use App\Models\User;
use App\Models\Veiculo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TestDataSeeder extends Seeder
{
    /**
     * Execute o seeder com dados de teste para desenvolvimento e demonstração.
     */
    public function run(): void
    {
        $this->migrateOrCleanRoles();
        $this->createRoles();
        $this->createPermissions();
        $this->assignPermissionsToRoles();
        // Criar usuários de teste
        $this->createUsers();
        
        // Criar dados demo (províncias, unidades de saúde, etc)
        $this->createDemoData();
    }
    
    /**
     * Criar papéis básicos do sistema
     */
    /**
     * Migra ou limpa papéis com guard 'sanctum' para evitar conflitos
     */
    private function migrateOrCleanRoles(): void
    {
        // Limpa a tabela de relacionamento entre papéis e permissões (role_has_permissions) para guard sanctum
        DB::table('role_has_permissions')
            ->join('roles', 'role_has_permissions.role_id', '=', 'roles.id')
            ->where('roles.guard_name', '=', 'sanctum')
            ->delete();
            
        // Limpa a tabela de relacionamento entre usuários e papéis (model_has_roles) para guard sanctum
        DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.guard_name', '=', 'sanctum')
            ->delete();
            
        // Agora é seguro deletar os papéis com guard sanctum
        Role::where('guard_name', 'sanctum')->delete();
        
        // Também limpa permissões sanctum não usadas
        DB::table('model_has_permissions')
            ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
            ->where('permissions.guard_name', '=', 'sanctum')
            ->delete();
            
        Permission::where('guard_name', 'sanctum')->delete();
    }
    
    private function createRoles(): void
    {
        // Verificar se os papéis já existem
        if (!Role::where('name', 'admin')->exists()) {
            Role::create(['name' => 'admin', 'guard_name' => 'web']);
        }
        if (!Role::where('name', 'gestor')->exists()) {
            Role::create(['name' => 'gestor', 'guard_name' => 'web']);
        }
        if (!Role::where('name', 'medico')->exists()) {
            Role::create(['name' => 'medico', 'guard_name' => 'web']);
        }
        if (!Role::where('name', 'enfermeiro')->exists()) {
            Role::create(['name' => 'enfermeiro', 'guard_name' => 'web']);
        }
        if (!Role::where('name', 'motorista')->exists()) {
            Role::create(['name' => 'motorista', 'guard_name' => 'web']);
        }
    }
    
    /**
     * Criar permissões do sistema
     */
    private function createPermissions(): void
    {
        // Lista de permissões por categoria
        $permissionsMap = [
            'gabinetes' => ['ver', 'criar', 'editar', 'eliminar'],
            'unidades-saude' => ['ver', 'criar', 'editar', 'eliminar'],
            'pacientes' => ['ver', 'criar', 'editar', 'eliminar'],
            'pontos-cuidado' => ['ver', 'criar', 'editar', 'eliminar', 'atualizar-prontidao', 'atualizar-capacidade'],
            'triagens' => ['ver', 'criar', 'editar', 'eliminar'],
            'veiculos' => ['ver', 'criar', 'editar', 'eliminar'],
            'mapa' => ['ver'],
            'relatorios' => ['ver'],
            'auditoria' => ['ver'],
        ];
        
        // Criar permissões para o guard 'sanctum'
        foreach ($permissionsMap as $prefix => $actions) {
            foreach ($actions as $action) {
                $permissionName = "$action $prefix";
                
                if (!Permission::where('name', $permissionName)->where('guard_name', 'web')->exists()) {
                    Permission::create([
                        'name' => $permissionName,
                        'guard_name' => 'web'
                    ]);
                }
            }
        }
    }
    
    /**
     * Associar permissões aos papéis
     */
    private function assignPermissionsToRoles(): void
    {
        // Admin - todas as permissões
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        $allPermissions = Permission::where('guard_name', 'web')->get();
        $adminRole->syncPermissions($allPermissions);
        
        // Gestor - permissões de visualização e relatórios
        $gestorRole = Role::where('name', 'gestor')->where('guard_name', 'web')->first();
        $gestorPermissions = Permission::where('guard_name', 'web')
            ->where(function($query) {
                $query->where('name', 'like', 'ver %')
                      ->orWhere('name', 'like', '% relatorios');
            })->get();
        $gestorRole->syncPermissions($gestorPermissions);
        
        // Médico - pacientes, triagens, pontos de cuidado
        $medicoRole = Role::where('name', 'medico')->where('guard_name', 'web')->first();
        $medicoPermissions = Permission::where('guard_name', 'web')
            ->where(function($query) {
                $query->where('name', 'like', '% pacientes')
                      ->orWhere('name', 'like', '% triagens')
                      ->orWhere('name', 'like', 'ver pontos-cuidado')
                      ->orWhere('name', 'like', 'ver mapa');
            })->get();
        $medicoRole->syncPermissions($medicoPermissions);
        
        // Enfermeiro - visualizar e editar pacientes, criar triagens
        $enfermeiroRole = Role::where('name', 'enfermeiro')->where('guard_name', 'web')->first();
        $enfermeiroPermissions = Permission::where('guard_name', 'web')
            ->where(function($query) {
                $query->where('name', 'like', 'ver pacientes')
                      ->orWhere('name', 'like', 'editar pacientes')
                      ->orWhere('name', 'like', 'criar pacientes')
                      ->orWhere('name', 'like', 'ver triagens')
                      ->orWhere('name', 'like', 'criar triagens')
                      ->orWhere('name', 'like', 'ver pontos-cuidado')
                      ->orWhere('name', 'like', 'ver mapa');
            })->get();
        $enfermeiroRole->syncPermissions($enfermeiroPermissions);
        
        // Motorista - veículos e mapa
        $motoristaRole = Role::where('name', 'motorista')->first();
        $motoristaPermissions = Permission::where('guard_name', 'web')
            ->where(function($query) {
                $query->where('name', 'like', 'ver veiculos')
                      ->orWhere('name', 'like', 'editar veiculos')
                      ->orWhere('name', 'like', 'ver mapa');
            })->get();
        $motoristaRole->syncPermissions($motoristaPermissions);
    }
    
    /**
     * Criar usuários de teste para cada papel
     */
    private function createUsers(): void
    {
        // Usuário administrador
        if (!User::where('email', 'admin@sistema-colera.ao')->exists()) {
            $admin = User::create([
                'name' => 'Admin Sistema',
                'email' => 'admin@sistema-colera.ao',
                'password' => Hash::make('password123'),
            ]);
            $admin->assignRole('admin');
        }
        
        // Usuário gestor
        if (!User::where('email', 'gestor@sistema-colera.ao')->exists()) {
            $gestor = User::create([
                'name' => 'Gestor Provincial',
                'email' => 'gestor@sistema-colera.ao',
                'password' => Hash::make('password123'),
            ]);
            $gestor->assignRole('gestor');
        }
        
        // Usuário médico
        if (!User::where('email', 'medico@sistema-colera.ao')->exists()) {
            $medico = User::create([
                'name' => 'Dr. Carlos Santos',
                'email' => 'medico@sistema-colera.ao',
                'password' => Hash::make('password123'),
            ]);
            $medico->assignRole('medico');
        }
        
        // Usuário enfermeiro
        if (!User::where('email', 'enfermeiro@sistema-colera.ao')->exists()) {
            $enfermeiro = User::create([
                'name' => 'Enf. Maria Joaquina',
                'email' => 'enfermeiro@sistema-colera.ao',
                'password' => Hash::make('password123'),
            ]);
            $enfermeiro->assignRole('enfermeiro');
        }
        
        // Usuário motorista
        if (!User::where('email', 'motorista@sistema-colera.ao')->exists()) {
            $motorista = User::create([
                'name' => 'João Motorista',
                'email' => 'motorista@sistema-colera.ao',
                'password' => Hash::make('password123'),
            ]);
            $motorista->assignRole('motorista');
        }
    }
    
    /**
     * Criar dados de demonstração (províncias, unidades, pacientes, etc)
     */
    private function createDemoData(): void
    {
        // Criar gabinetes provinciais
        $provincias = ['Luanda', 'Benguela', 'Huambo', 'Huíla'];
        $gabinetes = [];
        
        foreach ($provincias as $provincia) {
            $gabinete = GabineteProvincial::firstOrCreate(
                ['nome' => $provincia],
                [
                    'provincia' => $provincia, // Adicionar o campo provincia
                    'diretor' => 'Diretor de ' . $provincia,
                    'endereco' => 'Rua Principal, ' . $provincia,
                    'telefone' => '+244 9' . rand(10000000, 99999999),
                    'email' => strtolower($provincia) . '@gabinete.gov.ao'
                ]
            );
            $gabinetes[$provincia] = $gabinete;
        }
        
        // Criar unidades de saúde em cada província
        foreach ($gabinetes as $provincia => $gabinete) {
            for ($i = 1; $i <= 2; $i++) {
                $unidade = UnidadeSaude::firstOrCreate(
                    ['nome' => "Hospital $provincia $i"],
                    [
                        'tipo' => rand(0, 1) ? 'Hospital_Geral' : 'Centro_Saude',
                        'endereco' => "Avenida $i, $provincia",
                        'capacidade' => rand(50, 300),
                        'status' => ['Ativo', 'Em_Manutencao', 'Sobrelotado'][rand(0, 2)],
                        'latitude' => -8.838333 + (rand(-100, 100) / 1000),
                        'longitude' => 13.234444 + (rand(-100, 100) / 1000),
                        'gabinete_provincial_id' => $gabinete->id,
                    ]
                );
                
                // Criar pontos de cuidado para cada unidade
                for ($j = 1; $j <= 2; $j++) {
                    PontoCuidado::firstOrCreate(
                        ['nome' => "Ponto de Cuidado $j - " . $unidade->nome],
                        [
                            'descricao' => "Ponto de atendimento $j na unidade " . $unidade->nome,
                            'responsavel' => "Dr. Responsável " . $j,
                            'capacidade_maxima' => rand(10, 50),
                            'capacidade_atual' => rand(5, 15),
                            'nivel_prontidao' => ['Normal', 'Alerta', 'Emergência'][rand(0, 2)],
                            'status' => ['Ativo', 'Inativo', 'Manutenção'][rand(0, 2)],
                            'endereco' => $unidade->endereco . ', Bloco ' . chr(65 + rand(0, 5)),
                            'telefone' => '+244 9' . rand(10000000, 99999999),
                            'email' => 'ponto' . $j . '@' . strtolower(str_replace(' ', '', $unidade->nome)) . '.ao',
                            'provincia' => explode(' ', $provincia)[0],
                            'municipio' => 'Município ' . rand(1, 5),
                            'unidade_saude_id' => $unidade->id,
                            'tem_ambulancia' => (bool)rand(0, 1),
                            'ambulancias_disponiveis' => rand(0, 3),
                            'latitude' => -8.838333 + (rand(-1000, 1000) / 10000),
                            'longitude' => 13.234444 + (rand(-1000, 1000) / 10000),
                        ]
                    );
                }
                
                // Criar veículos para cada unidade
                $tiposVeiculo = ['ambulancia', 'transporte', 'apoio'];
                $tipoVeiculo = $tiposVeiculo[rand(0, 2)];
                
                Veiculo::firstOrCreate(
                    ['placa' => 'LD-' . rand(1000, 9999) . '-AO'],
                    [
                        'tipo' => $tipoVeiculo,
                        'modelo' => 'Toyota ' . rand(2010, 2023),
                        'ano' => rand(2010, 2023),
                        'capacidade_pacientes' => rand(3, 15),
                        'status' => ['disponivel', 'em_transito', 'em_manutencao', 'indisponivel'][rand(0, 3)],
                        'nivel_combustivel' => rand(10, 100),
                        'descricao' => 'Veículo de ' . $tipoVeiculo . ' para transporte de pacientes',
                        'responsavel' => 'Motorista ' . rand(1, 10),
                        'contato_responsavel' => '+244 9' . rand(10000000, 99999999),
                        'unidade_saude_id' => $unidade->id,
                        'latitude' => -8.838333 + (rand(-1000, 1000) / 10000),
                        'longitude' => 13.234444 + (rand(-1000, 1000) / 10000),
                    ]
                );
                
                // Criar pacientes para cada unidade
                for ($k = 1; $k <= 5; $k++) {
                    $genero = rand(0, 1) ? 'Masculino' : 'Feminino';
                    $nomes = $genero == 'Masculino' 
                        ? ['João', 'Carlos', 'Pedro', 'Manuel', 'António']
                        : ['Maria', 'Ana', 'Joana', 'Fátima', 'Luísa'];
                    $sobrenomes = ['Silva', 'Santos', 'Oliveira', 'Costa', 'Pereira'];
                    
                    $paciente = Paciente::firstOrCreate(
                        ['bi' => rand(100000000, 999999999) . 'LA' . rand(10, 99)],
                        [
                            'nome' => $nomes[rand(0, 4)] . ' ' . $sobrenomes[rand(0, 4)],
                            'data_nascimento' => date('Y-m-d', strtotime('-' . rand(1, 80) . ' years')),
                            'sexo' => $genero,
                            'telefone' => '+244 9' . rand(10000000, 99999999),
                            'endereco' => 'Rua ' . rand(1, 100) . ', ' . $provincia,
                            'provincia' => $provincia,
                            'unidade_saude_id' => $unidade->id,
                            'email' => strtolower($nomes[rand(0, 4)]) . rand(1, 999) . '@email.com',
                            'latitude' => -8.838333 + (rand(-1000, 1000) / 10000),
                            'longitude' => 13.234444 + (rand(-1000, 1000) / 10000),
                            'estado' => ['Ativo', 'Em_Tratamento', 'Recuperado', 'Óbito'][rand(0, 3)],
                            'tem_alergias' => rand(0, 1),
                            'alergias' => rand(0, 1) ? 'Penicilina, Aspirina' : null,
                            'grupo_sanguineo' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'][rand(0, 7)],
                            'historico_saude' => rand(0, 1) ? 'Hipertensão, Diabetes' : null,
                        ]
                    );
                    
                    // Criar triagens para alguns pacientes
                    if (rand(0, 1)) {
                        // Criar ou obter alguns sintomas
                        $sintomas = [];
                        $sintomasNomes = [
                            'Diarreia', 'Vómitos', 'Desidratação', 'Febre', 'Cólicas abdominais',
                            'Náusea', 'Dor abdominal', 'Fadiga extrema', 'Sede intensa'
                        ];
                        
                        foreach (array_rand($sintomasNomes, 3) as $index) {
                            $sintoma = Sintoma::firstOrCreate(
                                ['nome' => $sintomasNomes[$index]],
                                [
                                    'descricao' => 'Descrição de ' . $sintomasNomes[$index],
                                    'gravidade' => rand(1, 5),
                                    'categoria' => ['Gastrointestinal', 'Respiratório', 'Geral'][rand(0, 2)],
                                ]
                            );
                            $sintomas[] = $sintoma->id;
                        }
                        
                        // Criar triagem
                        Triagem::firstOrCreate(
                            ['paciente_id' => $paciente->id, 'created_at' => now()->subDays(rand(0, 30))],
                            [
                                'unidade_saude_id' => $unidade->id,
                                'ponto_cuidado_id' => PontoCuidado::where('unidade_saude_id', $unidade->id)->first()?->id,
                                'responsavel_id' => User::inRandomOrder()->first()?->id,
                                'sintomas' => json_encode($sintomas),
                                'nivel_urgencia' => ['baixo', 'medio', 'alto', 'critico'][rand(0, 3)],
                                'observacoes' => 'Observações da triagem ' . rand(1, 1000),
                                'temperatura' => rand(350, 400) / 10,
                                'frequencia_cardiaca' => rand(60, 120),
                                'frequencia_respiratoria' => rand(12, 30),
                                'probabilidade_colera' => rand(10, 95),
                                'indice_desidratacao' => rand(0, 80) / 10,
                                'status' => ['pendente', 'em_andamento', 'concluida', 'encaminhada'][rand(0, 3)],
                                'recomendacoes' => json_encode(['Rehidratação oral', 'Monitoramento contínuo']),
                                'encaminhamentos' => json_encode([])
                            ]
                        );
                    }
                }
            }
        }
    }
}
