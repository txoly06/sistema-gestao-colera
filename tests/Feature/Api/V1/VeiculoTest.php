<?php

namespace Tests\Feature\Api\V1;

use App\Models\Veiculo;
use App\Models\PontoCuidado;
use App\Models\UnidadeSaude;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class VeiculoTest extends TestCase
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
        Permission::create(['name' => 'ver veiculos']);
        Permission::create(['name' => 'criar veiculos']);
        Permission::create(['name' => 'editar veiculos']);
        Permission::create(['name' => 'eliminar veiculos']);

        // Criar papel de administrador e atribuir permissões
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'ver veiculos',
            'criar veiculos',
            'editar veiculos', 
            'eliminar veiculos'
        ]);

        // Criar utilizador administrador
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    /**
     * Testa se a listagem de veículos funciona corretamente.
     */
    public function test_listar_veiculos(): void
    {
        // Criar alguns veículos para testar
        Veiculo::factory()->count(3)->create();
        
        // Fazer requisição autenticada como admin, ignorando middlewares
        $this->actingAs($this->admin);
        $response = $this->withoutMiddleware()
                        ->getJson('/api/v1/veiculos');
        
        // Verificar resposta
        $response->assertStatus(200)
                ->assertJsonCount(3, 'data')
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id', 
                            'placa', 
                            'modelo',
                            'ano',
                            'tipo',
                            'status',
                            'capacidade_pacientes'
                        ]
                    ],
                    'message'
                ]);
    }

    /**
     * Testa se a criação de veículo funciona corretamente.
     */
    public function test_criar_veiculo(): void
    {
        // Criar um ponto de cuidado e uma unidade de saúde para associação
        $pontoCuidado = PontoCuidado::factory()->create();
        $unidadeSaude = UnidadeSaude::factory()->create();
        
        // Dados do novo veículo
        $veiculoData = [
            'placa' => 'ABC-1234',
            'modelo' => 'Toyota Hiace',
            'ano' => 2022,
            'tipo' => 'ambulancia',
            'status' => 'disponivel',
            'descricao' => 'Ambulância equipada para transporte de pacientes com cólera',
            'capacidade_pacientes' => 4,
            'tem_gps' => true,
            'nivel_combustivel' => 85,
            'ponto_cuidado_id' => $pontoCuidado->id,
            'unidade_saude_id' => $unidadeSaude->id,
            'responsavel' => 'Dr. João Silva',
            'contato_responsavel' => '923456789'
        ];
        
        // Fazer requisição autenticada como admin, ignorando middlewares
        $this->actingAs($this->admin);
        $response = $this->withoutMiddleware()
                        ->postJson('/api/v1/veiculos', $veiculoData);
        
        // Verificar resposta
        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Veículo criado com sucesso'
                ]);
                
        // Verificar se o veículo foi criado na base de dados
        $this->assertDatabaseHas('veiculos', [
            'placa' => 'ABC-1234',
            'modelo' => 'Toyota Hiace',
            'tipo' => 'ambulancia'
        ]);
    }

    /**
     * Testa se a visualização de veículo funciona corretamente.
     */
    public function test_mostrar_veiculo(): void
    {
        // Criar um veículo para testar
        $veiculo = Veiculo::factory()->create();
        
        // Fazer requisição autenticada como admin, ignorando middlewares
        $this->actingAs($this->admin);
        $response = $this->withoutMiddleware()
                        ->getJson('/api/v1/veiculos/' . $veiculo->id);
        
        // Verificar resposta
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Veículo recuperado com sucesso'
                ])
                ->assertJsonPath('data.id', $veiculo->id)
                ->assertJsonPath('data.placa', $veiculo->placa);
    }

    /**
     * Testa se a atualização de veículo funciona corretamente.
     */
    public function test_atualizar_veiculo(): void
    {
        // Criar um veículo para testar
        $veiculo = Veiculo::factory()->create([
            'status' => 'disponivel',
            'modelo' => 'Toyota Hiace'
        ]);
        
        // Dados para atualizar
        $dadosAtualizacao = [
            'status' => 'em_manutencao',
            'modelo' => 'Toyota HiAce Modificada',
            'descricao' => 'Veículo em manutenção para reparos no motor'
        ];
        
        // Fazer requisição autenticada como admin, ignorando middlewares
        $this->actingAs($this->admin);
        $response = $this->withoutMiddleware()
                        ->putJson('/api/v1/veiculos/' . $veiculo->id, $dadosAtualizacao);
        
        // Verificar resposta
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Veículo atualizado com sucesso'
                ])
                ->assertJsonPath('data.status', 'em_manutencao')
                ->assertJsonPath('data.modelo', 'Toyota HiAce Modificada');
                
        // Verificar se o veículo foi atualizado na base de dados
        $this->assertDatabaseHas('veiculos', [
            'id' => $veiculo->id,
            'status' => 'em_manutencao',
            'modelo' => 'Toyota HiAce Modificada'
        ]);
    }

    /**
     * Testa se a eliminação de veículo funciona corretamente.
     */
    public function test_eliminar_veiculo(): void
    {
        // Criar um veículo para testar
        $veiculo = Veiculo::factory()->create();
        
        // Fazer requisição autenticada como admin, ignorando middlewares
        $this->actingAs($this->admin);
        $response = $this->withoutMiddleware()
                        ->deleteJson('/api/v1/veiculos/' . $veiculo->id);
        
        // Verificar resposta
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Veículo eliminado com sucesso'
                ]);
                
        // Verificar se o veículo foi eliminado (soft delete) na base de dados
        $this->assertSoftDeleted('veiculos', ['id' => $veiculo->id]);
    }

    /**
     * Testa se a atualização de status de veículo funciona corretamente.
     */
    public function test_atualizar_status(): void
    {
        // Criar um veículo para testar
        $veiculo = Veiculo::factory()->create(['status' => 'disponivel']);
        
        // Fazer requisição autenticada como admin, ignorando middlewares
        $this->actingAs($this->admin);
        $response = $this->withoutMiddleware()
                        ->putJson('/api/v1/veiculos/' . $veiculo->id . '/status', [
                            'status' => 'em_transito'
                        ]);
        
        // Verificar resposta
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Status do veículo atualizado com sucesso'
                ])
                ->assertJsonPath('data.status', 'em_transito');
                
        // Verificar se o veículo foi atualizado na base de dados
        $this->assertDatabaseHas('veiculos', [
            'id' => $veiculo->id,
            'status' => 'em_transito'
        ]);
    }

    /**
     * Testa se a atualização de localização de veículo funciona corretamente.
     */
    public function test_atualizar_localizacao(): void
    {
        // Criar um veículo para testar
        $veiculo = Veiculo::factory()->create([
            'latitude' => -8.8,
            'longitude' => 13.2
        ]);
        
        // Fazer requisição autenticada como admin, ignorando middlewares
        $this->actingAs($this->admin);
        $response = $this->withoutMiddleware()
                        ->putJson('/api/v1/veiculos/' . $veiculo->id . '/localizacao', [
                            'latitude' => -8.9,
                            'longitude' => 13.1
                        ]);
        
        // Verificar resposta
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Localização do veículo atualizada com sucesso'
                ])
                ->assertJsonPath('data.latitude', -8.9)
                ->assertJsonPath('data.longitude', 13.1);
                
        // Verificar se o veículo foi atualizado na base de dados
        $this->assertDatabaseHas('veiculos', [
            'id' => $veiculo->id,
            'latitude' => -8.9,
            'longitude' => 13.1
        ]);
    }
    
    /**
     * Testa se o endpoint de veículos disponíveis funciona corretamente.
     */
    public function test_listar_veiculos_disponiveis(): void
    {
        // Criar 3 veículos disponíveis e 2 indisponíveis
        Veiculo::factory()->count(3)->create(['status' => 'disponivel']);
        Veiculo::factory()->count(2)->create(['status' => 'em_manutencao']);
        
        // Fazer requisição autenticada como admin, ignorando middlewares
        $this->actingAs($this->admin);
        $response = $this->withoutMiddleware()
                        ->getJson('/api/v1/veiculos-disponiveis');
        
        // Verificar resposta
        $response->assertStatus(200)
                ->assertJsonCount(3, 'data')
                ->assertJson([
                    'success' => true,
                    'message' => 'Lista de veículos disponíveis recuperada com sucesso'
                ]);
        
        // Verificar que todos os veículos retornados estão disponíveis
        $veiculos = json_decode($response->getContent())->data;
        foreach ($veiculos as $veiculo) {
            $this->assertEquals('disponivel', $veiculo->status);
        }
    }
    
    /**
     * Testa se a filtragem de veículos por tipo funciona corretamente.
     */
    public function test_listar_veiculos_por_tipo(): void
    {
        // Criar veículos de diferentes tipos
        Veiculo::factory()->count(2)->create(['tipo' => 'ambulancia']);
        Veiculo::factory()->count(3)->create(['tipo' => 'transporte']);
        Veiculo::factory()->count(1)->create(['tipo' => 'apoio']);
        
        // Fazer requisição autenticada como admin, ignorando middlewares
        $this->actingAs($this->admin);
        $response = $this->withoutMiddleware()
                        ->getJson('/api/v1/veiculos-por-tipo/ambulancia');
        
        // Verificar resposta
        $response->assertStatus(200)
                ->assertJsonCount(2, 'data')
                ->assertJson([
                    'success' => true,
                    'message' => 'Lista de veículos do tipo ambulancia recuperada com sucesso'
                ]);
        
        // Verificar que todos os veículos retornados são do tipo ambulancia
        $veiculos = json_decode($response->getContent())->data;
        foreach ($veiculos as $veiculo) {
            $this->assertEquals('ambulancia', $veiculo->tipo);
        }
    }
    
    /**
     * Testa se o acesso não autorizado é bloqueado corretamente.
     */
    public function test_acesso_nao_autorizado(): void
    {
        // Criar veículo para testar
        $veiculo = Veiculo::factory()->create();
        
        // Criar um usuário sem permissão
        $usuarioSemPermissao = User::factory()->create();
        
        // Verificar se tem a permissão (deve retornar false)
        $this->assertFalse($usuarioSemPermissao->can('ver veiculos'));
        
        // Verificar se o admin tem a permissão (deve retornar true)
        $this->assertTrue($this->admin->can('ver veiculos'));
        
        // Fazer requisição com usuário sem permissão
        $this->actingAs($usuarioSemPermissao);
        $response = $this->withoutMiddleware()
                        ->getJson('/api/v1/veiculos/' . $veiculo->id);
                        
        // Verificar manualmente se o usuário tem permissão
        if (!$usuarioSemPermissao->can('ver veiculos')) {
            // Se o usuário não tem permissão, deveria receber 403
            $response->assertStatus(403)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Não autorizado'
                    ]);
        }
    }
}
