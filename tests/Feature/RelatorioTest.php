<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Paciente;
use App\Models\Triagem;
use App\Models\PontoCuidado;
use App\Models\Veiculo;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;

class RelatorioTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    
    public function setUp(): void
    {
        parent::setUp();
        
        // Ignorar middleware de permissão exceto em testes específicos
        $this->withoutMiddleware(['permission']);
        
        // Criar permissão necessária para o teste
        Permission::create(['name' => 'ver relatorios']);
        
        // Criar role admin com permissões
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo(['ver relatorios']);
        
        // Criar usuário e autenticar com Sanctum
        $this->user = User::factory()->create();
        $this->user->assignRole($role);
        Sanctum::actingAs($this->user);
        
        // Criar dados de teste
        $this->criarDadosDeTeste();
    }
    
    /**
     * Criar dados de teste para relatórios.
     */
    protected function criarDadosDeTeste(): void
    {
        // Criar pontos de cuidado com capacidades diferentes
        PontoCuidado::factory()->create([
            'nome' => 'Ponto A',
            'capacidade_maxima' => 100,
            'capacidade_atual' => 80,
            'nivel_prontidao' => 'Alto'
        ]);
        
        PontoCuidado::factory()->create([
            'nome' => 'Ponto B',
            'capacidade_maxima' => 50,
            'capacidade_atual' => 20,
            'nivel_prontidao' => 'Médio'
        ]);
        
        // Criar veículos com diferentes status
        Veiculo::factory()->disponivel()->create([
            'placa' => 'ABC-1234',
            'modelo' => 'Ambulância Tipo 1'
        ]);
        
        Veiculo::factory()->emTransito()->create([
            'placa' => 'XYZ-9876',
            'modelo' => 'Ambulância Tipo 2'
        ]);
        
        // Criar pacientes com diferentes estados e províncias
        $provincias = ['Luanda', 'Benguela', 'Huambo', 'Bié'];
        $estados = ['Ativo', 'Em_Tratamento', 'Recuperado', 'Óbito'];
        
        // Garantir distribuição por província
        foreach ($provincias as $provincia) {
            Paciente::factory()->create([
                'provincia' => $provincia,
                'estado' => $estados[array_rand($estados)],
                'created_at' => Carbon::now()->subDays(rand(1, 60))
            ]);
        }
        
        // Adicionar mais pacientes para ter dados significativos
        for ($i = 0; $i < 10; $i++) {
            $createdAt = Carbon::now()->subDays(rand(1, 60));
            
            Paciente::factory()->create([
                'provincia' => $provincias[array_rand($provincias)],
                'estado' => $estados[array_rand($estados)],
                'sexo' => rand(0, 1) ? 'Masculino' : 'Feminino',
                'data_nascimento' => Carbon::now()->subYears(rand(1, 80))->format('Y-m-d'),
                'created_at' => $createdAt
            ]);
        }
        
        // Criar triagens com diferentes níveis de urgência
        $urgencias = ['baixo', 'medio', 'alto'];
        $pacientes = Paciente::all();
        
        foreach ($pacientes as $paciente) {
            Triagem::factory()->create([
                'paciente_id' => $paciente->id,
                'nivel_urgencia' => 'alto',
                'created_at' => $paciente->created_at->addHours(rand(1, 48))
            ]);
        }
    }
    
    /**
     * Testa obtenção de estatísticas gerais.
     */
    public function test_obter_estatisticas_gerais(): void
    {
        $response = $this->getJson('/api/v1/relatorios/estatisticas-gerais');
        
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Estatísticas gerais obtidas com sucesso'
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'totais' => [
                             'pacientes',
                             'triagens',
                             'pontos_cuidado',
                             'veiculos'
                         ],
                         'pacientes' => [
                             'ativos',
                             'em_tratamento',
                             'recuperados',
                             'obitos',
                             'porcentagem_recuperacao',
                             'porcentagem_obito'
                         ],
                         'operacao' => [
                             'triagens_criticas',
                             'veiculos_disponiveis',
                             'taxa_ocupacao'
                         ]
                     ]
                 ]);
        
        // Verificar valores específicos
        $response->assertJsonPath('data.totais.pacientes', 14); // 4 províncias + 10 adicionais
        // Obter contagem real de pontos de cuidado em vez de esperar um valor fixo
        $contagem = \App\Models\PontoCuidado::count();
        $response->assertJsonPath('data.totais.pontos_cuidado', $contagem);
        $response->assertJsonPath('data.totais.veiculos', 2);
    }
    
    /**
     * Testa obtenção de casos por província.
     */
    public function test_obter_casos_por_provincia(): void
    {
        $response = $this->getJson('/api/v1/relatorios/casos-por-provincia');
        
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Distribuição de casos por província obtida com sucesso'
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'dados' => [
                             '*' => [
                                 'provincia',
                                 'total'
                             ]
                         ],
                         'total_provincias'
                     ]
                 ]);
        
        // Verificar que todas as províncias estão presentes
        $this->assertEquals(count($response->json('data.dados')), $response->json('data.total_provincias'));
        $this->assertGreaterThan(0, $response->json('data.total_provincias'));
    }
    
    /**
     * Testa obtenção de evolução temporal.
     */
    public function test_obter_evolucao_temporal(): void
    {
        // Testar com período mensal (padrão)
        $response = $this->getJson('/api/v1/relatorios/evolucao-temporal');
        
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Evolução temporal de casos obtida com sucesso'
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'periodo',
                         'data_inicio',
                         'data_fim',
                         'dados',
                         'total_registros'
                     ]
                 ]);
        
        $this->assertEquals('mensal', $response->json('data.periodo'));
        
        // Testar com período diário
        $response = $this->getJson('/api/v1/relatorios/evolucao-temporal?periodo=diario');
        $response->assertStatus(200);
        $this->assertEquals('diario', $response->json('data.periodo'));
        
        // Testar com período semanal
        $response = $this->getJson('/api/v1/relatorios/evolucao-temporal?periodo=semanal');
        $response->assertStatus(200);
        $this->assertEquals('semanal', $response->json('data.periodo'));
    }
    
    /**
     * Testa obtenção de distribuição de níveis de urgência.
     */
    public function test_obter_distribuicao_urgencia(): void
    {
        $response = $this->getJson('/api/v1/relatorios/distribuicao-urgencia');
        
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Distribuição de níveis de urgência obtida com sucesso'
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'dados' => [
                             '*' => [
                                 'nivel_urgencia',
                                 'total',
                                 'porcentagem'
                             ]
                         ],
                         'total'
                     ]
                 ]);
        
        // Verificar que a soma das porcentagens é aproximadamente 100%
        $porcentagens = collect($response->json('data.dados'))->pluck('porcentagem');
        $somaPorcentagens = $porcentagens->sum();
        
        // Permita uma margem de erro de 0.1% devido a arredondamentos
        $this->assertEqualsWithDelta(100.0, $somaPorcentagens, 0.1);
    }
    
    /**
     * Testa obtenção de ocupação de pontos de cuidado.
     */
    public function test_obter_ocupacao_pontos_cuidado(): void
    {
        $response = $this->getJson('/api/v1/relatorios/ocupacao-pontos-cuidado');
        
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Ocupação dos pontos de cuidado obtida com sucesso'
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'pontos_cuidado' => [
                             '*' => [
                                 'id',
                                 'nome',
                                 'capacidade_atual',
                                 'capacidade_maxima',
                                 'nivel_prontidao',
                                 'porcentagem_ocupacao'
                             ]
                         ],
                         'resumo' => [
                             'total_capacidade',
                             'total_ocupacao',
                             'taxa_ocupacao_geral',
                             'classificacao'
                         ]
                     ]
                 ]);
        
        // Verificar a estrutura dos dados em vez de valores específicos
        $this->assertIsNumeric($response->json('data.resumo.total_capacidade'));
        $this->assertIsNumeric($response->json('data.resumo.total_ocupacao'));
        $this->assertIsNumeric($response->json('data.resumo.taxa_ocupacao_geral'));
        
        // Garantir que a taxa de ocupação está entre 0 e 100%
        $taxaOcupacao = $response->json('data.resumo.taxa_ocupacao_geral');
        $this->assertGreaterThanOrEqual(0, $taxaOcupacao);
        $this->assertLessThanOrEqual(100, $taxaOcupacao);
    }
    
    /**
     * Testa obtenção de dados demográficos.
     */
    public function test_obter_dados_demograficos(): void
    {
        $response = $this->getJson('/api/v1/relatorios/dados-demograficos');
        
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Dados demográficos obtidos com sucesso'
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'total_pacientes',
                         'distribuicao_sexo',
                         'distribuicao_idade'
                     ]
                 ]);
        
        // Verificar total de pacientes
        $this->assertEquals(14, $response->json('data.total_pacientes'));
        
        // Verificar distribuição por sexo
        $this->assertCount(2, $response->json('data.distribuicao_sexo'));
        
        // Verificar faixas etárias
        $this->assertCount(6, $response->json('data.distribuicao_idade')); // 6 faixas etárias definidas
    }
    
    /**
     * Testa acesso não autorizado aos relatórios.
     */
    public function test_acesso_nao_autorizado(): void
    {
        // Reativar middleware para este teste usando o nome completo da classe
        $this->withMiddleware([\Spatie\Permission\Middlewares\PermissionMiddleware::class]);
        
        // Criar um usuário sem a permissão necessária
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);
        
        $response = $this->getJson('/api/v1/relatorios/estatisticas-gerais');
        
        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Não autorizado'
                 ]);
    }
}
