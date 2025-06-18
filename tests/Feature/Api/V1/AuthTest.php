<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar um papel para teste
        Role::create(['name' => 'Administrador']);
    }
    
    /**
     * Testa o login com credenciais válidas
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        // Criar um utilizador de teste
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
        
        // Atribuir papel ao utilizador
        $user->assignRole('Administrador');
        
        // Fazer a requisição de login
        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'device_name' => 'Test Device'
        ]);
        
        // Verificar resposta
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'roles',
                        'permissions',
                    ],
                    'token',
                ],
            ]);
    }
    
    /**
     * Testa o login com credenciais inválidas
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        // Criar um utilizador de teste
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
        
        // Fazer a requisição com senha inválida
        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);
        
        // Verificar resposta
        $response->assertStatus(422);
    }
    
    /**
     * Testa a obtenção de dados do utilizador autenticado
     */
    public function test_authenticated_user_can_get_user_details(): void
    {
        // Criar um utilizador e autenticar com Sanctum
        $user = User::factory()->create();
        $user->assignRole('Administrador');
        
        Sanctum::actingAs($user);
        
        // Fazer a requisição para obter dados do utilizador
        $response = $this->getJson('/api/v1/user');
        
        // Verificar resposta
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'roles',
                    'permissions',
                ],
            ]);
    }
    
    /**
     * Testa o endpoint de logout
     */
    public function test_user_can_logout(): void
    {
        // Criar um utilizador e autenticar com Sanctum
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        
        // Fazer a requisição de logout
        $response = $this->postJson('/api/v1/logout');
        
        // Verificar resposta
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout realizado com sucesso.',
            ]);
    }
    
    /**
     * Testa que um usuário não autenticado não pode acessar rotas protegidas
     */
    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        // Tentar acessar a rota de detalhes do utilizador sem autenticação
        $response = $this->getJson('/api/v1/user');
        
        // Verificar resposta (401 Unauthorized)
        $response->assertStatus(401);
    }
}
