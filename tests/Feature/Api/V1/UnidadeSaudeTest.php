<?php

namespace Tests\Feature\Api\V1;

use App\Models\GabineteProvincial;
use App\Models\UnidadeSaude;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UnidadeSaudeTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected GabineteProvincial $gabineteProvincial;

    /**
     * Setup dos testes
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Criar permissões necessárias para o teste
        Permission::create(['name' => 'ver unidades-saude', 'guard_name' => 'web']);
        Permission::create(['name' => 'criar unidades-saude', 'guard_name' => 'web']);
        Permission::create(['name' => 'editar unidades-saude', 'guard_name' => 'web']);
        Permission::create(['name' => 'eliminar unidades-saude', 'guard_name' => 'web']);
        Permission::create(['name' => 'restaurar unidades-saude', 'guard_name' => 'web']);
        Permission::create(['name' => 'eliminar-permanente unidades-saude', 'guard_name' => 'web']);

        // Criar um papel de administrador e associar permissões
        $role = Role::create(['name' => 'Administrador', 'guard_name' => 'web']);
        $role->givePermissionTo([
            'ver unidades-saude',
            'criar unidades-saude',
            'editar unidades-saude',
            'eliminar unidades-saude',
            'restaurar unidades-saude',
            'eliminar-permanente unidades-saude'
        ]);

        // Criar um usuário com permissões
        $this->user = User::factory()->create();
        $this->user->assignRole('Administrador');
        
        Sanctum::actingAs($this->user);
        
        // Criar um gabinete provincial para associar às unidades de saúde
        $this->gabineteProvincial = GabineteProvincial::create([
            'nome' => 'Gabinete Provincial de Luanda',
            'provincia' => 'Luanda',
            'diretor' => 'Dr. Manuel Silva',
            'endereco' => 'Rua Principal, 123, Luanda',
            'telefone' => '+244 923456789',
            'email' => 'gabinete.luanda@saude.gov.ao',
            'ativo' => true
        ]);
    }

    /** @test */
    public function pode_listar_unidades_de_saude()
    {
        // Criar algumas unidades de saúde
        UnidadeSaude::create([
            'gabinete_provincial_id' => $this->gabineteProvincial->id,
            'nome' => 'Hospital Central de Luanda',
            'diretor_medico' => 'Dra. Ana Ferreira',
            'tipo' => 'Hospital_Geral',
            'endereco' => 'Av. Revolução, 500, Luanda',
            'telefone' => '+244 923111222',
            'email' => 'hospital.central@saude.gov.ao',
            'status' => 'Ativo'
        ]);

        UnidadeSaude::create([
            'gabinete_provincial_id' => $this->gabineteProvincial->id,
            'nome' => 'Centro de Saúde Kilamba',
            'diretor_medico' => 'Dr. João Costa',
            'tipo' => 'Centro_Saude',
            'endereco' => 'Kilamba, Quadra Z, Luanda',
            'telefone' => '+244 923333444',
            'email' => 'cs.kilamba@saude.gov.ao',
            'status' => 'Ativo'
        ]);

        // Fazer requisição à API
        $response = $this->withoutMiddleware(['permission'])->getJson('/api/v1/unidades');

        // Assegurar resposta correta
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ])
                ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function pode_criar_unidade_de_saude()
    {
        $unidadeData = [
            'gabinete_provincial_id' => $this->gabineteProvincial->id,
            'nome' => 'Nova Clínica de Saúde',
            'diretor_medico' => 'Dr. Pedro Simões',
            'tipo' => 'Clinica',
            'endereco' => 'Rua das Palmeiras, 45, Luanda',
            'telefone' => '+244 923123456',
            'email' => 'clinica.nova@saude.gov.ao',
            'capacidade' => 80,
            'tem_isolamento' => true,
            'capacidade_isolamento' => 15,
            'status' => 'Ativo',
            'nivel_alerta' => 'Baixo'
        ];

        // Fazer requisição à API
        $response = $this->withoutMiddleware(['permission'])->postJson('/api/v1/unidades', $unidadeData);

        // Assegurar resposta correta
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'gabinete_provincial_id',
                        'nome',
                        'diretor_medico',
                        'tipo',
                        'endereco',
                        'telefone',
                        'email',
                        'status',
                        'created_at',
                        'updated_at'
                    ],
                    'message'
                ]);
                
        // Verificar base de dados
        $this->assertDatabaseHas('unidades_saude', [
            'nome' => 'Nova Clínica de Saúde',
            'diretor_medico' => 'Dr. Pedro Simões'
        ]);
    }

    /** @test */
    public function nao_pode_criar_unidade_de_saude_com_dados_invalidos()
    {
        // Dados inválidos - sem nome
        $unidadeData = [
            'gabinete_provincial_id' => $this->gabineteProvincial->id,
            'tipo' => 'Clinica',
            'endereco' => 'Rua das Palmeiras, 45, Luanda',
            'status' => 'Ativo'
        ];

        // Fazer requisição à API
        $response = $this->withoutMiddleware(['permission'])->postJson('/api/v1/unidades', $unidadeData);

        // Assegurar resposta de erro
        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'error',
                    'message'
                ]);
    }

    /** @test */
    public function pode_obter_unidade_de_saude_por_id()
    {
        // Criar unidade
        $unidade = UnidadeSaude::create([
            'gabinete_provincial_id' => $this->gabineteProvincial->id,
            'nome' => 'Hospital Provincial',
            'diretor_medico' => 'Dr. Carlos Mendes',
            'tipo' => 'Hospital_Geral',
            'endereco' => 'Av. Principal, 100, Benguela',
            'telefone' => '+244 923555666',
            'email' => 'hospital.benguela@saude.gov.ao',
            'status' => 'Ativo'
        ]);

        // Fazer requisição à API
        $response = $this->withoutMiddleware(['permission'])->getJson('/api/v1/unidades/' . $unidade->id);

        // Assegurar resposta correta
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'gabinete_provincial_id',
                        'nome',
                        'diretor_medico',
                        'tipo',
                        'endereco',
                        'telefone',
                        'email',
                        'status',
                        'created_at',
                        'updated_at'
                    ],
                    'message'
                ])
                ->assertJson([
                    'data' => [
                        'id' => $unidade->id,
                        'nome' => 'Hospital Provincial'
                    ]
                ]);
    }

    /** @test */
    public function pode_atualizar_unidade_de_saude()
    {
        // Criar unidade
        $unidade = UnidadeSaude::create([
            'gabinete_provincial_id' => $this->gabineteProvincial->id,
            'nome' => 'Centro de Saúde Velho',
            'diretor_medico' => 'Dr. José Santos',
            'tipo' => 'Centro_Saude',
            'endereco' => 'Rua Antiga, 10, Huambo',
            'telefone' => '+244 923777888',
            'email' => 'cs.velho@saude.gov.ao',
            'status' => 'Inativo'
        ]);

        // Dados para atualização
        $updateData = [
            'nome' => 'Centro de Saúde Renovado',
            'diretor_medico' => 'Dra. Maria Antunes',
            'status' => 'Ativo',
            'nivel_alerta' => 'Medio'
        ];

        // Fazer requisição à API
        $response = $this->withoutMiddleware(['permission'])->putJson('/api/v1/unidades/' . $unidade->id, $updateData);

        // Assegurar resposta correta
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ])
                ->assertJson([
                    'data' => [
                        'id' => $unidade->id,
                        'nome' => 'Centro de Saúde Renovado',
                        'diretor_medico' => 'Dra. Maria Antunes',
                        'status' => 'Ativo'
                    ]
                ]);
                
        // Verificar base de dados
        $this->assertDatabaseHas('unidades_saude', [
            'id' => $unidade->id,
            'nome' => 'Centro de Saúde Renovado',
            'diretor_medico' => 'Dra. Maria Antunes',
            'status' => 'Ativo'
        ]);
    }

    /** @test */
    public function pode_eliminar_unidade_de_saude()
    {
        // Criar unidade
        $unidade = UnidadeSaude::create([
            'gabinete_provincial_id' => $this->gabineteProvincial->id,
            'nome' => 'Clínica para Eliminar',
            'diretor_medico' => 'Dr. António Pedro',
            'tipo' => 'Clinica',
            'endereco' => 'Rua Sem Saída, 99, Lubango',
            'telefone' => '+244 923999000',
            'email' => 'clinica.temp@saude.gov.ao',
            'status' => 'Em_Manutencao'
        ]);

        // Fazer requisição à API
        $response = $this->withoutMiddleware(['permission'])->deleteJson('/api/v1/unidades/' . $unidade->id);

        // Assegurar resposta correta
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Unidade de saúde eliminada com sucesso'
                ]);
                
        // Verificar soft delete
        $this->assertSoftDeleted('unidades_saude', [
            'id' => $unidade->id
        ]);
    }

    /** @test */
    public function retorna_erro_para_unidade_inexistente()
    {
        // ID inexistente
        $idInexistente = 999;

        // Fazer requisição à API
        $response = $this->withoutMiddleware(['permission'])->getJson('/api/v1/unidades/' . $idInexistente);

        // Assegurar resposta de erro
        $response->assertStatus(404)
                ->assertJsonStructure([
                    'success',
                    'error',
                    'message'
                ])
                ->assertJson([
                    'success' => false
                ]);
    }
}
