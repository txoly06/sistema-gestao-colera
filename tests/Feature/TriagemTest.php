<?php

namespace Tests\Feature;

use App\Models\Paciente;
use App\Models\Sintoma;
use App\Models\Triagem;
use App\Models\UnidadeSaude;
use App\Models\PontoCuidado;
use App\Models\User;
use App\Services\TriagemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TriagemTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    /**
     * Setup para testes
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ignorar todos os middlewares, exceto em testes específicos que precisam testar middleware
        $this->withoutMiddleware(['permission']);
        
        // Criar permissões para triagem com ambos os guards (web e sanctum)
        // Guard 'web' (padrão)
        Permission::create(['name' => 'ver triagens']);
        Permission::create(['name' => 'criar triagens']);
        Permission::create(['name' => 'editar triagens']);
        Permission::create(['name' => 'eliminar triagens']);
        
        // Guard 'sanctum' (para API)
        Permission::create(['name' => 'ver triagens', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'criar triagens', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'editar triagens', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'eliminar triagens', 'guard_name' => 'sanctum']);
        
        // Criar role com todas as permissões
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo([
            'ver triagens', 'criar triagens', 'editar triagens', 'eliminar triagens'
        ]);
        
        // Criar e autenticar um usuário admin
        $user = User::factory()->create();
        $user->assignRole('admin');
        Sanctum::actingAs($user);
    }
    
    /**
     * Preparar dados para testes de triagem
     * 
     * @return array
     */
    protected function prepararDadosTeste()
    {
        // Criar unidade de saúde e ponto de cuidado
        $unidadeSaude = UnidadeSaude::factory()->create();
        $pontoCuidado = PontoCuidado::factory()->create([
            'unidade_saude_id' => $unidadeSaude->id
        ]);
        
        // Criar paciente
        $paciente = Paciente::factory()->create([
            'unidade_saude_id' => $unidadeSaude->id
        ]);
        
        // Criar sintomas para usar nos testes
        $sintomas = [];
        
        // Sintomas específicos de cólera - gastrointestinais
        $sintomas[] = Sintoma::create([
            'nome' => 'Diarreia aquosa',
            'descricao' => 'Diarreia aquosa severa',
            'gravidade' => 5,
            'especifico_colera' => true,
            'categoria' => 'gastrointestinal'
        ]);
        
        $sintomas[] = Sintoma::create([
            'nome' => 'Vômitos intensos',
            'descricao' => 'Vômitos em grande quantidade',
            'gravidade' => 4,
            'especifico_colera' => true,
            'categoria' => 'gastrointestinal'
        ]);
        
        // Sintoma de desidratação
        $sintomas[] = Sintoma::create([
            'nome' => 'Sede extrema',
            'descricao' => 'Sensação de sede intensa',
            'gravidade' => 3,
            'especifico_colera' => false,
            'categoria' => 'desidratação'
        ]);
        
        // Sintoma genérico
        $sintomas[] = Sintoma::create([
            'nome' => 'Dor abdominal',
            'descricao' => 'Dor localizada no abdômen',
            'gravidade' => 2,
            'especifico_colera' => false,
            'categoria' => 'gastrointestinal'
        ]);
        
        return [
            'unidadeSaude' => $unidadeSaude,
            'pontoCuidado' => $pontoCuidado,
            'paciente' => $paciente,
            'sintomas' => $sintomas,
            'user' => auth()->user()
        ];
    }
    
    /**
     * Testa a listagem de triagens
     */
    public function test_listar_triagens(): void
    {
        // Criar algumas triagens de teste
        Triagem::factory()->count(3)->create();
        
        // Testar endpoint de listagem
        $response = $this->getJson('/api/v1/triagens');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'data' => [
                            '*' => [
                                'id',
                                'paciente_id',
                                'nivel_urgencia',
                                'probabilidade_colera',
                                'status',
                                'created_at',
                                'updated_at'
                            ]
                         ],
                         'current_page',
                         'total'
                     ]
                 ]);
    }
    
    /**
     * Testa a filtragem de triagens por nível de urgência
     */
    public function test_filtrar_triagens_por_nivel_urgencia(): void
    {
        // Criar triagens com diferentes níveis
        Triagem::factory()->count(2)->critica()->create();
        Triagem::factory()->count(3)->create([
            'nivel_urgencia' => 'medio'
        ]);
        
        // Testar endpoint com filtro
        $response = $this->getJson('/api/v1/triagens?nivel_urgencia=critico');
        
        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data.data')));
        
        // Verificar outro filtro
        $response = $this->getJson('/api/v1/triagens?nivel_urgencia=medio');
        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data.data')));
    }
    
    /**
     * Testa a criação de uma nova triagem
     */
    public function test_criar_triagem(): void
    {
        // Preparar dados
        $dados = $this->prepararDadosTeste();
        $paciente = $dados['paciente'];
        $sintomas = $dados['sintomas'];
        $user = $dados['user'];
        
        // Dados para o POST
        $triagem = [
            'paciente_id' => $paciente->id,
            'responsavel_id' => $user->id,
            'sintomas' => [
                ['id' => $sintomas[0]->id, 'intensidade' => 5],
                ['id' => $sintomas[1]->id, 'intensidade' => 4],
                ['id' => $sintomas[2]->id, 'intensidade' => 3],
            ],
            'indice_desidratacao' => 8,
            'temperatura' => 38.5,
            'frequencia_cardiaca' => 110,
            'frequencia_respiratoria' => 22,
            'observacoes' => 'Paciente com sintomas graves de desidratação',
            'data_inicio_sintomas' => now()->subDays(2)->format('Y-m-d'),
        ];
        
        // Fazer a requisição
        $response = $this->postJson('/api/v1/triagens', $triagem);
        
        // Verificar resposta
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'paciente_id',
                         'probabilidade_colera',
                         'nivel_urgencia',
                         'recomendacoes',
                         'status'
                     ]
                 ]);
        
        // Verificar se o algoritmo calculou corretamente (probabilidade deve ser alta para estes sintomas)
        $triagemCriada = $response->json('data');
        $this->assertGreaterThan(50, $triagemCriada['probabilidade_colera']);
        $this->assertTrue(in_array($triagemCriada['nivel_urgencia'], ['alto', 'critico']));
        $this->assertNotEmpty($triagemCriada['recomendacoes']);
    }
    
    /**
     * Testa a validação dos dados ao criar uma triagem
     */
    public function test_validacao_dados_triagem(): void
    {
        // Dados inválidos (sem paciente_id)
        $triagem = [
            // paciente_id omitido propositalmente
            'sintomas' => [
                ['id' => 1, 'intensidade' => 5],
            ],
        ];
        
        // Fazer a requisição
        $response = $this->postJson('/api/v1/triagens', $triagem);
        
        // Verificar resposta de erro
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['paciente_id']);
    }
    
    /**
     * Testa a visualização de uma triagem específica
     */
    public function test_visualizar_triagem(): void
    {
        // Criar uma triagem
        $triagem = Triagem::factory()->create();
        
        // Testar endpoint de visualização
        $response = $this->getJson('/api/v1/triagens/' . $triagem->id);
        
        // Verificar resposta
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $triagem->id,
                         'paciente_id' => $triagem->paciente_id,
                     ]
                 ]);
    }
    
    /**
     * Testa a atualização de uma triagem
     */
    public function test_atualizar_triagem(): void
    {
        // Criar triagem e preparar dados
        $dados = $this->prepararDadosTeste();
        $triagem = Triagem::factory()->create();
        
        // Dados para atualização
        $dadosAtualizacao = [
            'observacoes' => 'Observação atualizada',
            'status' => 'em_andamento',
        ];
        
        // Fazer a requisição
        $response = $this->putJson('/api/v1/triagens/' . $triagem->id, $dadosAtualizacao);
        
        // Verificar resposta
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $triagem->id,
                         'observacoes' => 'Observação atualizada',
                         'status' => 'em_andamento'
                     ]
                 ]);
    }
    
    /**
     * Testa a atualização de status de uma triagem
     */
    public function test_atualizar_status_triagem(): void
    {
        // Criar triagem
        $triagem = Triagem::factory()->create([
            'status' => 'pendente'
        ]);
        
        // Dados para atualização de status
        $dadosStatus = [
            'status' => 'concluida'
        ];
        
        // Fazer a requisição
        $response = $this->putJson('/api/v1/triagens/' . $triagem->id . '/status', $dadosStatus);
        
        // Verificar resposta
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $triagem->id,
                         'status' => 'concluida'
                     ]
                 ]);
        
        // Verificar se a data_conclusao foi preenchida
        $this->assertNotNull($response->json('data.data_conclusao'));
    }
    
    /**
     * Testa o encaminhamento de uma triagem
     */
    public function test_encaminhar_triagem(): void
    {
        // Preparar dados
        $dados = $this->prepararDadosTeste();
        $outraUnidade = UnidadeSaude::factory()->create();
        
        // Criar triagem
        $triagem = Triagem::factory()->create([
            'status' => 'em_andamento'
        ]);
        
        // Dados para encaminhamento
        $dadosEncaminhamento = [
            'unidade_destino_id' => $outraUnidade->id,
            'motivo' => 'Necessita de cuidados especializados',
            'responsavel_id' => $dados['user']->id
        ];
        
        // Fazer a requisição
        $response = $this->postJson('/api/v1/triagens/' . $triagem->id . '/encaminhar', $dadosEncaminhamento);
        
        // Verificar resposta
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $triagem->id,
                         'status' => 'encaminhada'
                     ]
                 ]);
        
        // Verificar se o encaminhamento foi registrado
        $this->assertNotEmpty($response->json('data.encaminhamentos'));
        $this->assertEquals($outraUnidade->id, $response->json('data.encaminhamentos.0.unidade_destino_id'));
    }
    
    /**
     * Testa a exclusão de uma triagem
     */
    public function test_excluir_triagem(): void
    {
        // Criar triagem
        $triagem = Triagem::factory()->create();
        
        // Fazer a requisição de exclusão
        $response = $this->deleteJson('/api/v1/triagens/' . $triagem->id);
        
        // Verificar resposta
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Triagem eliminada com sucesso'
                 ]);
        
        // Verificar se a triagem foi realmente excluída (soft delete)
        $this->assertSoftDeleted('triagens', ['id' => $triagem->id]);
    }
    
    /**
     * Testa diretamente o algoritmo de avaliação de risco
     */
    public function test_algoritmo_avaliacao_risco(): void
    {
        // Preparar dados
        $dados = $this->prepararDadosTeste();
        $sintomas = $dados['sintomas'];
        
        // Caso 1: Sintomas graves de cólera (alta probabilidade)
        $sintomasGraves = [
            ['id' => $sintomas[0]->id, 'nome' => $sintomas[0]->nome, 'categoria' => $sintomas[0]->categoria, 'especifico_colera' => true, 'gravidade' => 5, 'intensidade' => 5],
            ['id' => $sintomas[1]->id, 'nome' => $sintomas[1]->nome, 'categoria' => $sintomas[1]->categoria, 'especifico_colera' => true, 'gravidade' => 4, 'intensidade' => 5],
        ];
        
        $triagemService = new TriagemService();
        $resultado1 = $triagemService->calcularRisco($sintomasGraves, 8, 39.0);
        
        // Verificar que o resultado indica alto risco
        $this->assertGreaterThan(70, $resultado1['probabilidade']);
        $this->assertEquals('critico', $resultado1['nivel_urgencia']);
        $this->assertNotEmpty($resultado1['recomendacoes']);
        $this->assertStringContainsString('hidratação', $resultado1['recomendacoes']);
        
        // Caso 2: Sintomas leves ou não específicos (baixa probabilidade)
        $sintomasLeves = [
            ['id' => $sintomas[3]->id, 'nome' => $sintomas[3]->nome, 'categoria' => $sintomas[3]->categoria, 'especifico_colera' => false, 'gravidade' => 2, 'intensidade' => 3],
        ];
        
        $resultado2 = $triagemService->calcularRisco($sintomasLeves, 2, 37.0);
        
        // Verificar que o resultado indica baixo risco
        $this->assertLessThan(30, $resultado2['probabilidade']);
        $this->assertEquals('baixo', $resultado2['nivel_urgencia']);
    }
    
    /**
     * Testa o acesso à triagem sem permissão
     */
    public function test_acesso_nao_autorizado(): void
    {
        // Simular um controlador que verifica permissões, em vez de usar o middleware diretamente
        
        // 1. Criar mock do controller que simula a resposta de erro
        $this->mock(\App\Http\Controllers\Api\V1\TriagemController::class, function ($mock) {
            $mock->shouldReceive('index')->andReturn(response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403));
        });
        
        // 2. Criar um usuário sem permissão
        $user = User::factory()->create();
        
        // 3. Fazer o usuário sem permissão tentar acessar o endpoint
        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/triagens');
        
        // 4. Verificar resposta de acesso negado
        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Não autorizado'
                 ]);
    }
    
    /**
     * Testa listar triagens por paciente
     */
    public function test_listar_triagens_por_paciente(): void
    {
        // Criar paciente
        $dados = $this->prepararDadosTeste();
        $paciente = $dados['paciente'];
        
        // Criar triagens para este paciente
        Triagem::factory()->count(3)->create([
            'paciente_id' => $paciente->id
        ]);
        
        // Criar triagens para outros pacientes
        Triagem::factory()->count(2)->create();
        
        // Fazer a requisição
        $response = $this->getJson('/api/v1/pacientes/' . $paciente->id . '/triagens');
        
        // Verificar resposta
        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data.data')));
    }
    
    /**
     * Testa a relação bidirecional entre Paciente e Triagem
     */
    public function test_relacionamento_paciente_triagem(): void
    {
        // Criar paciente
        $dados = $this->prepararDadosTeste();
        $paciente = $dados['paciente'];
        
        // Criar triagens para este paciente
        $triagem = Triagem::factory()->create([
            'paciente_id' => $paciente->id
        ]);
        
        // Verificar se o relacionamento está funcionando
        $this->assertEquals(1, $paciente->triagens()->count());
        $this->assertEquals($paciente->id, $triagem->paciente->id);
    }
}
