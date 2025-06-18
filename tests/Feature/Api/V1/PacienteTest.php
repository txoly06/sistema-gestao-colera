<?php

namespace Tests\Feature\Api\V1;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PacienteTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        // Desabilitar TODOS os middlewares para os testes
        $this->withoutMiddleware();

        // Criar permissões
        Permission::create(['name' => 'ver pacientes']);
        Permission::create(['name' => 'criar pacientes']);
        Permission::create(['name' => 'editar pacientes']);
        Permission::create(['name' => 'eliminar pacientes']);
        Permission::create(['name' => 'restaurar pacientes']);
        Permission::create(['name' => 'eliminar-permanente pacientes']);
        Permission::create(['name' => 'ver-dados-confidenciais pacientes']);

        // Criar um utilizador de teste com todas as permissões
        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'ver pacientes',
            'criar pacientes',
            'editar pacientes',
            'eliminar pacientes',
            'restaurar pacientes',
            'eliminar-permanente pacientes',
            'ver-dados-confidenciais pacientes'
        ]);
    }

    /**
     * Teste para listar todos os pacientes.
     */
    public function test_listar_pacientes_com_sucesso(): void
    {
        // Autenticar utilizador
        $this->actingAs($this->user);

        // Criar alguns pacientes para teste
        Paciente::factory(3)->create();

        // Fazer solicitação GET para o endpoint de listagem
        $response = $this->getJson('/api/v1/pacientes');

        // Verificar resposta bem-sucedida
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'nome',
                        'bi',
                        'data_nascimento',
                        'sexo',
                        'endereco',
                        'provincia',
                        'estado',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ])
            ->assertJson(['success' => true]);
    }

    /**
     * Teste para criar um novo paciente.
     */
    public function test_criar_paciente_com_sucesso(): void
    {
        // Autenticar utilizador
        $this->actingAs($this->user);

        // Dados de teste para criar paciente
        $dados = [
            'nome' => $this->faker->name,
            'bi' => $this->faker->unique()->numerify('########LA###'),
            'data_nascimento' => $this->faker->date(),
            'sexo' => $this->faker->randomElement(['Masculino', 'Feminino']),
            'telefone' => $this->faker->phoneNumber,
            'endereco' => $this->faker->address,
            'provincia' => $this->faker->state,
            'email' => $this->faker->email,
            'grupo_sanguineo' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'tem_alergias' => $this->faker->boolean,
            'alergias' => $this->faker->sentence,
            'estado' => 'Ativo'
        ];

        // Fazer solicitação POST para criar paciente
        $response = $this->postJson('/api/v1/pacientes', $dados);

        // Verificar resposta bem-sucedida
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'nome',
                    'bi',
                    'data_nascimento',
                    'sexo',
                    'endereco',
                    'provincia',
                    'estado',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'nome' => $dados['nome'],
                    'bi' => $dados['bi'],
                    'sexo' => $dados['sexo'],
                    'provincia' => $dados['provincia'],
                ]
            ]);

        // Verificar se o paciente foi salvo no banco de dados
        $this->assertDatabaseHas('pacientes', [
            'nome' => $dados['nome'],
            'bi' => $dados['bi'],
            'sexo' => $dados['sexo'],
            'provincia' => $dados['provincia'],
        ]);
    }

    /**
     * Teste para criar paciente com dados inválidos.
     */
    public function test_criar_paciente_com_dados_invalidos(): void
    {
        // Autenticar utilizador
        $this->actingAs($this->user);

        // Dados inválidos (faltando campos obrigatórios)
        $dados = [
            'nome' => '',
            'data_nascimento' => 'data-invalida',
        ];

        // Fazer solicitação POST para criar paciente
        $response = $this->postJson('/api/v1/pacientes', $dados);

        // Verificar resposta de erro
        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'error'
            ])
            ->assertJson(['success' => false]);
    }

    /**
     * Teste para visualizar um paciente específico.
     */
    public function test_visualizar_paciente_com_sucesso(): void
    {
        // Autenticar utilizador
        $this->actingAs($this->user);

        // Criar paciente para teste
        $paciente = Paciente::factory()->create();

        // Fazer solicitação GET para ver o paciente
        $response = $this->getJson('/api/v1/pacientes/' . $paciente->id);

        // Verificar resposta bem-sucedida
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'nome',
                    'bi',
                    'data_nascimento',
                    'sexo',
                    'endereco',
                    'provincia',
                    'estado',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $paciente->id,
                    'nome' => $paciente->nome,
                    'bi' => $paciente->bi
                ]
            ]);
    }

    /**
     * Teste para visualizar paciente que não existe.
     */
    public function test_visualizar_paciente_nao_existente(): void
    {
        // Autenticar utilizador
        $this->actingAs($this->user);

        // Fazer solicitação GET para ver um paciente que não existe
        $response = $this->getJson('/api/v1/pacientes/9999');

        // Verificar resposta de erro
        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'message',
                'error'
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Paciente não encontrado'
            ]);
    }

    /**
     * Teste para atualizar um paciente.
     */
    public function test_atualizar_paciente_com_sucesso(): void
    {
        // Autenticar utilizador
        $this->actingAs($this->user);

        // Criar paciente para teste
        $paciente = Paciente::factory()->create();

        // Dados para atualização
        $dados = [
            'nome' => 'Nome Atualizado',
            'endereco' => 'Endereço Atualizado',
            'estado' => 'Em_Tratamento'
        ];

        // Fazer solicitação PUT para atualizar o paciente
        $response = $this->putJson('/api/v1/pacientes/' . $paciente->id, $dados);

        // Verificar resposta bem-sucedida
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'nome',
                    'bi',
                    'data_nascimento',
                    'sexo',
                    'endereco',
                    'provincia',
                    'estado',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $paciente->id,
                    'nome' => $dados['nome'],
                    'endereco' => $dados['endereco'],
                    'estado' => $dados['estado']
                ]
            ]);

        // Verificar se os dados foram atualizados no banco
        $this->assertDatabaseHas('pacientes', [
            'id' => $paciente->id,
            'nome' => $dados['nome'],
            'endereco' => $dados['endereco'],
            'estado' => $dados['estado']
        ]);
    }

    /**
     * Teste para eliminar um paciente.
     */
    public function test_eliminar_paciente_com_sucesso(): void
    {
        // Autenticar utilizador
        $this->actingAs($this->user);

        // Criar paciente para teste
        $paciente = Paciente::factory()->create();

        // Fazer solicitação DELETE para eliminar o paciente
        $response = $this->deleteJson('/api/v1/pacientes/' . $paciente->id);

        // Verificar resposta bem-sucedida
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Paciente eliminado com sucesso'
            ]);

        // Verificar se foi feito soft delete (deleted_at não é nulo)
        $this->assertSoftDeleted('pacientes', ['id' => $paciente->id]);
    }

    /**
     * Teste para acesso não autorizado.
     */
    public function test_acesso_nao_autorizado(): void
    {
        // Criar um utilizador sem permissões
        $userSemPermissao = User::factory()->create();
        $this->actingAs($userSemPermissao);

        // Tentar acessar a listagem de pacientes
        $response = $this->getJson('/api/v1/pacientes');

        // Verificar que o acesso foi negado
        $response->assertStatus(403);
    }
}
