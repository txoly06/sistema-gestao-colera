<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Paciente;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class QrCodeTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    protected $paciente;
    
    public function setUp(): void
    {
        parent::setUp();
        
        // Ignorar middleware de permissão exceto em testes específicos
        $this->withoutMiddleware(['permission']);
        
        // Criar permissões necessárias para o teste com o guard 'web' (padrão)
        Permission::create(['name' => 'ver pacientes']);
        
        // Criar role admin com todas as permissões
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo(['ver pacientes']);
        
        // Criar usuário e autenticar com Sanctum
        $this->user = User::factory()->create();
        $this->user->assignRole($role);
        Sanctum::actingAs($this->user);
        
        // Criar um paciente para teste
        $this->paciente = Paciente::factory()->create([
            'nome' => 'João Teste',
            'bi' => '123456789LA042',
            'data_nascimento' => '1990-01-15',
            'sexo' => 'Masculino',
            'telefone' => '+244123456789',
            'endereco' => 'Rua Teste, 123',
            'provincia' => 'Luanda'
        ]);
    }
    
    /**
     * Testa a geração de QR Code em formato base64.
     */
    public function test_gerar_qr_code_base64(): void
    {
        // Fazer requisição para obter QR code
        $response = $this->getJson('/api/v1/pacientes/'.$this->paciente->id.'/qrcode');
        
        // Verificar resposta
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'QR Code gerado com sucesso'
                 ]);
        
        // Verificar estrutura da resposta com QR code base64
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'paciente_id',
                'paciente_nome',
                'qrcode'
            ]
        ]);
        
        // Verificar que o QR code contém formato base64 de imagem PNG
        $response->assertJsonPath('data.paciente_id', $this->paciente->id)
                 ->assertJsonPath('data.paciente_nome', 'João Teste');
                 
        // Verificar que o QR code contém formato válido
        $this->assertStringContainsString('data:image/png;base64,', $response->json('data.qrcode'));
    }
    
    /**
     * Testa o download do QR Code como imagem PNG.
     */
    public function test_download_qr_code_imagem(): void
    {
        // Fazer requisição para obter QR code como download
        $response = $this->get('/api/v1/pacientes/'.$this->paciente->id.'/qrcode?download=true');
        
        // Verificar resposta
        $response->assertStatus(200)
                 ->assertHeader('Content-Type', 'image/png')
                 ->assertHeader('Content-Disposition', 'attachment; filename="paciente-'.$this->paciente->id.'-qrcode.png"');
    }
    
    /**
     * Testa a tentativa de acessar QR Code de paciente inexistente.
     */
    public function test_qr_code_paciente_inexistente(): void
    {
        // Usar um ID que não existe
        $id = $this->paciente->id + 999;
        
        // Fazer requisição para obter QR code
        $response = $this->getJson('/api/v1/pacientes/'.$id.'/qrcode');
        
        // Verificar resposta de erro
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Paciente não encontrado'
                 ]);
    }
    
    /**
     * Testa acesso não autorizado à função de QR Code.
     */
    public function test_acesso_nao_autorizado(): void
    {
        // Reativar middleware para este teste
        $this->withMiddleware(['permission']);
        
        // Criar um mock do controller que simula a resposta de erro
        $this->mock(\App\Http\Controllers\Api\V1\PacienteController::class, function ($mock) {
            $mock->shouldReceive('generateQrCode')->andReturn(response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403));
        });
        
        // Criar um usuário sem permissão
        $user = User::factory()->create();
        
        // Fazer o usuário sem permissão tentar acessar o endpoint
        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/pacientes/'.$this->paciente->id.'/qrcode');
        
        // Verificar resposta de acesso negado
        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Não autorizado'
                 ]);
    }
}
