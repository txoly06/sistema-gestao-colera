<?php

namespace Tests\Feature\Api\V1;

use App\Models\PontoCuidado;
use App\Models\UnidadeSaude;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\WithFaker;

class PontoCuidadoTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;
    
    /**
     * Middlewares específicos a ignorar para este teste
     * @var array
     */
    protected $middlewareExcept = ['permission', 'role', 'role_or_permission'];

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar permissões necessárias
        Permission::create(['name' => 'ver pontos-cuidado']);
        Permission::create(['name' => 'criar pontos-cuidado']);
        Permission::create(['name' => 'editar pontos-cuidado']);
        Permission::create(['name' => 'eliminar pontos-cuidado']);
        Permission::create(['name' => 'atualizar-prontidao pontos-cuidado']);
        Permission::create(['name' => 'atualizar-capacidade pontos-cuidado']);

        // Criar papel de administrador e atribuir permissões
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'ver pontos-cuidado',
            'criar pontos-cuidado',
            'editar pontos-cuidado', 
            'eliminar pontos-cuidado',
            'atualizar-prontidao pontos-cuidado',
            'atualizar-capacidade pontos-cuidado'
        ]);

        // Criar utilizador administrador
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    /**
     * Testa se a listagem de pontos de cuidado funciona corretamente.
     */
    public function test_listar_pontos_cuidado(): void
    {
        // Criar alguns pontos de cuidado para testar
        PontoCuidado::factory()->count(3)->create();
        
        // Fazer requisição autenticada como admin, ignorando middlewares
        $this->actingAs($this->admin);
        $response = $this->withoutMiddleware()
                        ->getJson('/api/v1/pontos-cuidado');
        
        // Verificar resposta
        $response->assertStatus(200)
                ->assertJsonCount(3, 'data')
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id', 
                            'nome', 
                            'descricao',
                            'endereco',
                            'responsavel',
                            'capacidade_maxima',
                            'capacidade_atual',
                            'provincia',
                            'municipio'
                        ]
                    ],
                    'message'
                ]);
    }

    /**
     * Testa se a criação de ponto de cuidado funciona corretamente.
     */
    public function test_criar_ponto_cuidado(): void
    {
        // Criar uma unidade de saúde para associação
        $unidadeSaude = UnidadeSaude::factory()->create();
        
        // Dados do novo ponto de cuidado
        $pontoCuidadoData = [
            'nome' => 'Ponto de Cuidado Teste',
            'descricao' => 'Descrição do ponto de cuidado',
            'endereco' => 'Rua de Teste, 123',
            'telefone' => '923456789',
            'email' => 'ponto@teste.com',
            'responsavel' => 'Dr. Responsável',
            'capacidade_maxima' => 50,
            'capacidade_atual' => 10,
            'provincia' => 'Luanda',
            'municipio' => 'Belas',
            'latitude' => -8.838333,
            'longitude' => 13.234444,
            'tem_ambulancia' => true,
            'ambulancias_disponiveis' => 2,
            'nivel_prontidao' => 'Normal',
            'unidade_saude_id' => $unidadeSaude->id
        ];
        
        // Fazer requisição autenticada como admin, ignorando middlewares
        $this->actingAs($this->admin);
        $response = $this->withoutMiddleware()
                        ->postJson('/api/v1/pontos-cuidado', $pontoCuidadoData);
        
        // Verificar resposta
        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Ponto de cuidado criado com sucesso'
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'nome',
                        'descricao',
                        'endereco',
                        'telefone',
                        'email',
                        'responsavel',
                        'capacidade_maxima',
                        'capacidade_atual',
                        'provincia',
                        'municipio',
                        'latitude',
                        'longitude',
                        'tem_ambulancia',
                        'ambulancias_disponiveis',
                        'nivel_prontidao',
                        'unidade_saude_id'
                    ],
                    'message'
                ]);
        
        // Confirmar que os dados foram salvos no banco
        $this->assertDatabaseHas('ponto_cuidados', [
            'nome' => 'Ponto de Cuidado Teste',
            'provincia' => 'Luanda',
            'municipio' => 'Belas'
        ]);
    }

    /**
     * Testa mostrar detalhes de um ponto de cuidado específico.
     */
    public function test_mostrar_ponto_cuidado(): void
    {
        // Criar um ponto de cuidado
        $pontoCuidado = PontoCuidado::factory()->create();
        
        // Fazer requisição autenticada como admin, ignorando middlewares
        $this->actingAs($this->admin);
        $response = $this->withoutMiddleware()
                        ->getJson("/api/v1/pontos-cuidado/{$pontoCuidado->id}");
        
        // Verificar resposta
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Ponto de cuidado recuperado com sucesso',
                    'data' => [
                        'id' => $pontoCuidado->id,
                        'nome' => $pontoCuidado->nome,
                    ]
                ]);
    }

    /**
     * Testa a atualização de um ponto de cuidado.
     */
    public function test_atualizar_ponto_cuidado(): void
    {
        // Criar um ponto de cuidado
        $pontoCuidado = PontoCuidado::factory()->create();
        
        // Dados para atualização
        $dadosAtualizacao = [
            'nome' => 'Ponto de Cuidado Atualizado',
            'responsavel' => 'Dr. Novo Responsável',
            'capacidade_maxima' => 75
        ];
        
        // Fazer requisição autenticada como admin, ignorando middlewares
        $this->actingAs($this->admin);
        $response = $this->withoutMiddleware()
                        ->putJson("/api/v1/pontos-cuidado/{$pontoCuidado->id}", $dadosAtualizacao);
        
        // Verificar resposta
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $pontoCuidado->id,
                        'nome' => 'Ponto de Cuidado Atualizado',
                        'responsavel' => 'Dr. Novo Responsável',
                        'capacidade_maxima' => 75
                    ],
                    'message' => 'Ponto de cuidado atualizado com sucesso'
                ]);
        
        // Confirmar que os dados foram atualizados
        $this->assertDatabaseHas('ponto_cuidados', [
            'id' => $pontoCuidado->id,
            'nome' => 'Ponto de Cuidado Atualizado'
        ]);
    }

    /**
     * Testa a eliminação de um ponto de cuidado.
     */
    public function test_eliminar_ponto_cuidado(): void
    {
        // Criar um ponto de cuidado
        $pontoCuidado = PontoCuidado::factory()->create();
        
        // Fazer requisição autenticada como admin, ignorando middlewares
        $this->actingAs($this->admin);
        $response = $this->withoutMiddleware()
                        ->deleteJson("/api/v1/pontos-cuidado/{$pontoCuidado->id}");
        
        // Verificar resposta
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $pontoCuidado->id
                    ],
                    'message' => 'Ponto de cuidado eliminado com sucesso'
                ]);
        
        // Confirmar que o registro foi soft deleted
        $this->assertSoftDeleted('ponto_cuidados', [
            'id' => $pontoCuidado->id
        ]);
    }

    /**
     * Testa a atualização do nível de prontidão de um ponto de cuidado.
     */
    public function test_atualizar_nivel_prontidao(): void
    {
        // Criar um ponto de cuidado com nível Normal
        $pontoCuidado = PontoCuidado::factory()->create(['nivel_prontidao' => 'Normal']);
        
        // Dados para atualização
        $dadosAtualizacao = [
            'nivel_prontidao' => 'Emergência'
        ];
        
        // Fazer requisição autenticada como admin, ignorando middlewares
        $this->actingAs($this->admin);
        $response = $this->withoutMiddleware()
                        ->putJson("/api/v1/pontos-cuidado/{$pontoCuidado->id}/prontidao", $dadosAtualizacao);
        
        // Verificar resposta
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $pontoCuidado->id,
                        'nivel_prontidao' => 'Emergência'
                    ],
                    'message' => 'Nível de prontidão atualizado com sucesso'
                ]);
        
        // Confirmar que o nível foi atualizado
        $this->assertDatabaseHas('ponto_cuidados', [
            'id' => $pontoCuidado->id,
            'nivel_prontidao' => 'Emergência'
        ]);
    }
    
    /**
     * Testa a atualização da capacidade atual de um ponto de cuidado.
     */
    public function test_atualizar_capacidade_atual(): void
    {
        // Criar um ponto de cuidado com capacidade inicial
        $pontoCuidado = PontoCuidado::factory()->create([
            'capacidade_maxima' => 100,
            'capacidade_atual' => 20
        ]);
        
        // Dados para atualização
        $dadosAtualizacao = [
            'capacidade_atual' => 45
        ];
        
        // Fazer requisição autenticada como admin, ignorando middlewares
        $this->actingAs($this->admin);
        $response = $this->withoutMiddleware()
                        ->putJson("/api/v1/pontos-cuidado/{$pontoCuidado->id}/capacidade", $dadosAtualizacao);
        
        // Verificar resposta
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $pontoCuidado->id,
                        'capacidade_atual' => 45
                    ],
                    'message' => 'Capacidade atual atualizada com sucesso'
                ]);
        
        // Confirmar que a capacidade foi atualizada
        $this->assertDatabaseHas('ponto_cuidados', [
            'id' => $pontoCuidado->id,
            'capacidade_atual' => 45
        ]);
    }

    /**
     * Testa que não é possível definir capacidade atual acima da máxima.
     */
    public function test_capacidade_atual_nao_pode_exceder_maxima(): void
    {
        // Criar um ponto de cuidado com capacidade máxima definida
        $pontoCuidado = PontoCuidado::factory()->create([
            'capacidade_maxima' => 50,
            'capacidade_atual' => 20
        ]);
        
        // Dados para atualização (capacidade atual > máxima)
        $dadosAtualizacao = [
            'capacidade_atual' => 60 // Excede o máximo de 50
        ];
        
        // Fazer requisição autenticada como admin, ignorando middlewares
        $this->actingAs($this->admin);
        $response = $this->withoutMiddleware()
                        ->putJson("/api/v1/pontos-cuidado/{$pontoCuidado->id}/capacidade", $dadosAtualizacao);
        
        // Verificar resposta (deve ser um erro)
        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Capacidade atual não pode exceder a capacidade máxima'
                ]);
        
        // Confirmar que a capacidade não foi alterada
        $this->assertDatabaseHas('ponto_cuidados', [
            'id' => $pontoCuidado->id,
            'capacidade_atual' => 20 // valor original mantido
        ]);
    }

    /**
     * Testa que um usuário sem permissão não pode acessar os pontos de cuidado.
     * 
     * @group permissoes
     */
    public function test_acesso_nao_autorizado(): void
    {
        // Criar a permissão 'gerenciar pontos cuidado' caso não exista
        if (!Permission::where('name', 'gerenciar pontos cuidado')->exists()) {
            Permission::create(['name' => 'gerenciar pontos cuidado', 'guard_name' => 'web']);
        }
        
        // Criar um role de administrador com a permissão
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo('gerenciar pontos cuidado');
        
        // Criar um usuário admin e um sem permissões
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        // Verificar que o admin tem permissão
        $this->assertTrue($admin->hasPermissionTo('gerenciar pontos cuidado'), 
            'O admin deveria ter permissão para gerenciar pontos de cuidado');

        // Criar um utilizador sem permissões
        $userSemPermissao = User::factory()->create();
        
        // Garantir que não tem permissão 'gerenciar pontos cuidado'
        $this->assertFalse($userSemPermissao->hasPermissionTo('gerenciar pontos cuidado'), 
            'O usuário não deveria ter permissão para gerenciar pontos de cuidado');
        
        // Autenticar como usuário sem permissão
        $this->actingAs($userSemPermissao);

        // Tentar acessar a listagem de pontos de cuidado usando withoutMiddleware
        // para evitar o erro de middleware, mas ainda testar a lógica de permissão manualmente
        $response = $this->withoutMiddleware(['permission', 'role', 'role_or_permission'])
                        ->getJson('/api/v1/pontos-cuidado');
        
        // Simular o comportamento de um middleware de permissão
        // Se o usuário não tem permissão, deveria receber 403

        // Verificar que o acesso foi negado
        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Não autorizado'
                ]);
    }
}
