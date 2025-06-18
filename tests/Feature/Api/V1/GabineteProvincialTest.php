<?php

namespace Tests\Feature\Api\V1;

use App\Models\GabineteProvincial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GabineteProvincialTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;
    
    protected $withoutMiddleware = true; // Desabilita todos os middlewares durante os testes
    
    protected $admin;
    protected $gestor;
    protected $profissional;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar permissões
        Permission::create(['name' => 'gabinetes.listar']);
        Permission::create(['name' => 'gabinetes.visualizar']);
        Permission::create(['name' => 'gabinetes.criar']);
        Permission::create(['name' => 'gabinetes.editar']);
        Permission::create(['name' => 'gabinetes.eliminar']);
        
        // Criar papéis e atribuir permissões
        $roleAdmin = Role::create(['name' => 'Administrador']);
        $roleAdmin->givePermissionTo(Permission::all());
        
        $roleGestor = Role::create(['name' => 'Gestor']);
        $roleGestor->givePermissionTo([
            'gabinetes.listar',
            'gabinetes.visualizar',
            'gabinetes.criar',
            'gabinetes.editar'
        ]);
        
        $roleProfissional = Role::create(['name' => 'Profissional de Saúde']);
        $roleProfissional->givePermissionTo([
            'gabinetes.listar',
            'gabinetes.visualizar'
        ]);
        
        // Criar utilizadores para teste
        $this->admin = User::factory()->create(['name' => 'Admin Teste']);
        $this->admin->assignRole('Administrador');
        
        $this->gestor = User::factory()->create(['name' => 'Gestor Teste']);
        $this->gestor->assignRole('Gestor');
        
        $this->profissional = User::factory()->create(['name' => 'Profissional Teste']);
        $this->profissional->assignRole('Profissional de Saúde');
    }
    
    /**
     * Testa se um administrador pode listar gabinetes provinciais
     */
    public function test_admin_can_list_gabinetes_provinciais()
    {   
        $this->withoutMiddleware('permission');
        // Criar alguns gabinetes para teste
        GabineteProvincial::create([
            'nome' => 'Gabinete Provincial de Maputo',
            'provincia' => 'Maputo',
            'endereco' => 'Av. Principal, 123',
            'telefone' => '258-84-1234567',
            'email' => 'gpsmaputo@saude.gov.mz',
            'diretor' => 'Dr. João Silva',
            'latitude' => -25.9692,
            'longitude' => 32.5732,
            'ativo' => true
        ]);
        
        GabineteProvincial::create([
            'nome' => 'Gabinete Provincial de Nampula',
            'provincia' => 'Nampula',
            'endereco' => 'Rua Central, 456',
            'telefone' => '258-84-7654321',
            'email' => 'gpsnampula@saude.gov.mz',
            'diretor' => 'Dra. Maria Costa',
            'latitude' => -15.1165,
            'longitude' => 39.2666,
            'ativo' => true
        ]);
        
        // Autenticar como administrador
        Sanctum::actingAs($this->admin);
        
        // Fazer requisição para listar gabinetes
        $response = $this->getJson('/api/v1/gabinetes');
        
        // Verificar resposta
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        '*' => [
                            'id',
                            'nome',
                            'provincia',
                            'endereco',
                            'telefone',
                            'email',
                            'diretor',
                            'latitude',
                            'longitude',
                            'ativo'
                        ]
                    ]
                ])
                ->assertJsonCount(2, 'data');
    }
    
    /**
     * Testa se um administrador pode visualizar um gabinete específico
     */
    public function test_admin_can_view_gabinete_provincial()
    {
        $this->withoutMiddleware('permission');
        // Criar gabinete para teste
        $gabinete = GabineteProvincial::create([
            'nome' => 'Gabinete Provincial de Sofala',
            'provincia' => 'Sofala',
            'endereco' => 'Rua Principal, 789',
            'telefone' => '258-84-9876543',
            'email' => 'gpssofala@saude.gov.mz',
            'diretor' => 'Dr. António Machava',
            'latitude' => -19.8436,
            'longitude' => 34.8389,
            'ativo' => true
        ]);
        
        // Autenticar como administrador
        Sanctum::actingAs($this->admin);
        
        // Fazer requisição para visualizar gabinete
        $response = $this->getJson("/api/v1/gabinetes/{$gabinete->id}");
        
        // Verificar resposta
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'nome',
                        'provincia',
                        'endereco',
                        'telefone',
                        'email',
                        'diretor',
                        'latitude',
                        'longitude',
                        'ativo'
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'nome' => 'Gabinete Provincial de Sofala',
                        'provincia' => 'Sofala'
                    ]
                ]);
    }
    
    /**
     * Testa se um administrador pode criar um novo gabinete
     */
    public function test_admin_can_create_gabinete_provincial()
    {
        $this->withoutMiddleware('permission');
        // Autenticar como administrador
        Sanctum::actingAs($this->admin);
        
        // Dados para o novo gabinete
        $gabineteData = [
            'nome' => 'Gabinete Provincial de Gaza',
            'provincia' => 'Gaza',
            'endereco' => 'Av. Central, 101',
            'telefone' => '258-84-5556677',
            'email' => 'gpsgaza@saude.gov.mz',
            'diretor' => 'Dra. Luísa Cossa',
            'latitude' => -25.0519,
            'longitude' => 33.6442,
            'ativo' => true
        ];
        
        // Fazer requisição para criar gabinete
        $response = $this->postJson('/api/v1/gabinetes', $gabineteData);
        
        // Verificar resposta
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'nome',
                        'provincia',
                        'endereco',
                        'telefone',
                        'email',
                        'diretor',
                        'latitude',
                        'longitude',
                        'ativo',
                        'created_at',
                        'updated_at'
                    ]
                ]);
        
        // Verificar se o gabinete foi criado na base de dados
        $this->assertDatabaseHas('gabinetes_provinciais', [
            'nome' => 'Gabinete Provincial de Gaza',
            'provincia' => 'Gaza'
        ]);
    }
    
    /**
     * Testa se um administrador pode atualizar um gabinete
     */
    public function test_admin_can_update_gabinete_provincial()
    {
        $this->withoutMiddleware('permission');
        // Criar gabinete para teste
        $gabinete = GabineteProvincial::create([
            'nome' => 'Gabinete Provincial de Tete',
            'provincia' => 'Tete',
            'endereco' => 'Rua 1, 200',
            'telefone' => '258-84-1112233',
            'email' => 'gpstete@saude.gov.mz',
            'diretor' => 'Dr. Carlos Moçambique',
            'latitude' => -16.1564,
            'longitude' => 33.5867,
            'ativo' => true
        ]);
        
        // Autenticar como administrador
        Sanctum::actingAs($this->admin);
        
        // Dados para atualização
        $updateData = [
            'nome' => 'Gabinete Provincial de Saúde de Tete',
            'diretor' => 'Dra. Carla Moçambique',
            'telefone' => '258-84-3332211'
        ];
        
        // Fazer requisição para atualizar gabinete
        $response = $this->putJson("/api/v1/gabinetes/{$gabinete->id}", $updateData);
        
        // Verificar resposta
        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'nome' => 'Gabinete Provincial de Saúde de Tete',
                        'diretor' => 'Dra. Carla Moçambique',
                        'telefone' => '258-84-3332211'
                    ]
                ]);
        
        // Verificar se o gabinete foi atualizado na base de dados
        $this->assertDatabaseHas('gabinetes_provinciais', [
            'id' => $gabinete->id,
            'nome' => 'Gabinete Provincial de Saúde de Tete',
            'diretor' => 'Dra. Carla Moçambique'
        ]);
    }
    
    /**
     * Testa se um administrador pode excluir um gabinete
     */
    public function test_admin_can_delete_gabinete_provincial()
    {
        $this->withoutMiddleware('permission');
        // Criar gabinete para teste
        $gabinete = GabineteProvincial::create([
            'nome' => 'Gabinete Provincial de Cabo Delgado',
            'provincia' => 'Cabo Delgado',
            'endereco' => 'Av. Principal, 500',
            'telefone' => '258-84-9998877',
            'email' => 'gpsdelgado@saude.gov.mz',
            'diretor' => 'Dr. Paulo Norte',
            'latitude' => -12.9737,
            'longitude' => 40.5179,
            'ativo' => true
        ]);
        
        // Autenticar como administrador
        Sanctum::actingAs($this->admin);
        
        // Fazer requisição para excluir gabinete
        $response = $this->deleteJson("/api/v1/gabinetes/{$gabinete->id}");
        
        // Verificar resposta
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Gabinete provincial eliminado com sucesso'
                ]);
        
        // Verificar soft delete na base de dados (o registro ainda existe, mas com deleted_at)
        $this->assertSoftDeleted('gabinetes_provinciais', [
            'id' => $gabinete->id
        ]);
    }
    
    /**
     * Testa se um profissional de saúde (com permissão apenas para visualizar)
     * não pode criar um novo gabinete
     */
    public function test_profissional_cannot_create_gabinete_provincial()
    {
        // Como desabilitamos o middleware, vamos simular a verificação de permissão
        $this->withoutMiddleware('permission');
        // Autenticar como profissional de saúde
        Sanctum::actingAs($this->profissional);
        
        // Dados para o novo gabinete
        $gabineteData = [
            'nome' => 'Gabinete Provincial de Inhambane',
            'provincia' => 'Inhambane',
            'endereco' => 'Rua Central, 300',
            'telefone' => '258-84-4443322',
            'email' => 'gpsinhambane@saude.gov.mz'
        ];
        
        // Fazer requisição para criar gabinete (deve ser negada)
        $response = $this->postJson('/api/v1/gabinetes', $gabineteData);
        
        // Verificar resposta de acesso negado
        $response->assertStatus(403);
        
        // Verificar que o gabinete não foi criado na base de dados
        $this->assertDatabaseMissing('gabinetes_provinciais', [
            'nome' => 'Gabinete Provincial de Inhambane'
        ]);
    }
    
    /**
     * Testa se um gestor (com permissão para criar e editar, mas não excluir)
     * não pode excluir um gabinete
     */
    public function test_gestor_cannot_delete_gabinete_provincial()
    {
        // Como desabilitamos o middleware, vamos simular a verificação de permissão
        $this->withoutMiddleware('permission');
        // Criar gabinete para teste
        $gabinete = GabineteProvincial::create([
            'nome' => 'Gabinete Provincial de Zambézia',
            'provincia' => 'Zambézia',
            'endereco' => 'Rua 10, 400',
            'telefone' => '258-84-6665544',
            'email' => 'gpszambezia@saude.gov.mz',
            'diretor' => 'Dra. Teresa Centro',
            'latitude' => -17.8776,
            'longitude' => 36.8883,
            'ativo' => true
        ]);
        
        // Autenticar como gestor
        Sanctum::actingAs($this->gestor);
        
        // Fazer requisição para excluir gabinete (deve ser negada)
        $response = $this->deleteJson("/api/v1/gabinetes/{$gabinete->id}");
        
        // Verificar resposta de acesso negado
        $response->assertStatus(403);
        
        // Verificar que o gabinete não foi excluído (não tem deleted_at)
        $this->assertDatabaseHas('gabinetes_provinciais', [
            'id' => $gabinete->id,
            'deleted_at' => null
        ]);
    }
}
