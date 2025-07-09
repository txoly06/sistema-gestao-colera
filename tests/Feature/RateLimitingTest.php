<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Teste para verificar se o rate limiting nas rotas públicas está funcionando.
     * Espera-se que após 5 requisições em 1 minuto, a próxima requisição retorne 429 (too many requests).
     *
     * @return void
     */
    public function test_rate_limiting_em_rotas_publicas()
    {
        // Fazer 5 requisições (limite para rotas públicas)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/login', [
                'email' => 'teste@example.com',
                'password' => 'password',
            ]);
            
            // As primeiras 5 devem retornar 422 (validação falhou, mas não importa para o teste)
            $response->assertStatus(422);
        }
        
        // A 6ª requisição deve falhar com 429 (too many requests)
        $response = $this->postJson('/api/v1/login', [
            'email' => 'teste@example.com',
            'password' => 'password',
        ]);
        
        $response->assertStatus(429);
        $response->assertJsonStructure(['message']);
    }

    /**
     * Teste para verificar se o rate limiting nas rotas protegidas está funcionando.
     * Este teste simula requisições rápidas para uma rota protegida com limite de 30 requisições.
     *
     * @return void
     */
    public function test_rate_limiting_em_rotas_protegidas()
    {
        // Criar um usuário e atribuir permissão
        $user = User::factory()->create();
        $user->givePermissionTo('ver pontos-cuidado');
        
        // Fazer 31 requisições para uma rota com limite de 30 req/min
        for ($i = 0; $i < 30; $i++) {
            $response = $this->actingAs($user, 'sanctum')
                            ->getJson('/api/v1/pontos-cuidado');
            
            // As primeiras 30 devem retornar 200
            $response->assertStatus(200);
        }
        
        // A 31ª requisição deve falhar com 429 (too many requests)
        $response = $this->actingAs($user, 'sanctum')
                        ->getJson('/api/v1/pontos-cuidado');
        
        $response->assertStatus(429);
        $response->assertJsonStructure(['message']);
    }
    
    /**
     * Teste para verificar se o rate limiting nas rotas de relatório está funcionando.
     * Este teste simula requisições rápidas para uma rota de relatório com limite de 15 requisições.
     *
     * @return void
     */
    public function test_rate_limiting_em_rotas_relatorios()
    {
        // Criar um usuário e atribuir permissão
        $user = User::factory()->create();
        $user->givePermissionTo('ver relatorios');
        
        // Fazer 16 requisições para uma rota com limite de 15 req/min
        for ($i = 0; $i < 15; $i++) {
            $response = $this->actingAs($user, 'sanctum')
                            ->getJson('/api/v1/relatorios/estatisticas-gerais');
            
            // As primeiras 15 devem retornar 200
            $response->assertStatus(200);
        }
        
        // A 16ª requisição deve falhar com 429 (too many requests)
        $response = $this->actingAs($user, 'sanctum')
                        ->getJson('/api/v1/relatorios/estatisticas-gerais');
        
        $response->assertStatus(429);
        $response->assertJsonStructure(['message']);
    }
}
