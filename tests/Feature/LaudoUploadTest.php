<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class LaudoUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock S3 storage para testes
        Storage::fake('s3');
    }

    /** @test */
    public function admin_pode_fazer_upload_de_laudo_pdf()
    {
        // Arrange: Criar usuário admin
        $admin = Usuario::factory()->create([
            'tipo_usuario' => 'administrador',
            'email' => 'admin@test.com'
        ]);

        $token = JWTAuth::fromUser($admin);

        // Criar arquivo PDF fake para teste
        $pdfFile = UploadedFile::fake()->create('laudo_teste.pdf', 1024, 'application/pdf');

        // Act: Fazer upload do laudo
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/laudos', [
            'titulo' => 'Laudo de Teste',
            'descricao' => 'Descrição do laudo de teste',
            'arquivo' => $pdfFile
        ]);

        // Assert: Verificar resposta
        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Laudo criado com sucesso'
                ]);

        // Verificar se arquivo foi "enviado" para S3
        $laudoData = $response->json('data');
        $this->assertNotNull($laudoData['url_arquivo']);
        
        // Verificar se o arquivo existe no storage fake
        $this->assertTrue(Storage::disk('s3')->exists($laudoData['url_arquivo']));

        // Verificar se foi salvo no banco
        $this->assertDatabaseHas('laudos', [
            'titulo' => 'Laudo de Teste',
            'descricao' => 'Descrição do laudo de teste',
            'usuario_id' => $admin->id,
            'url_arquivo' => $laudoData['url_arquivo']
        ]);
    }

    /** @test */
    public function deve_rejeitar_arquivo_que_nao_e_pdf()
    {
        // Arrange: Criar usuário admin
        $admin = Usuario::factory()->create([
            'tipo_usuario' => 'administrador'
        ]);

        $token = JWTAuth::fromUser($admin);

        // Criar arquivo que não é PDF
        $txtFile = UploadedFile::fake()->create('documento.txt', 100, 'text/plain');

        // Act: Tentar fazer upload
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/laudos', [
            'titulo' => 'Laudo Inválido',
            'arquivo' => $txtFile
        ]);

        // Assert: Deve ser rejeitado
        $response->assertStatus(422)
                ->assertJsonValidationErrors('arquivo');
    }

    /** @test */
    public function cliente_nao_pode_criar_laudo()
    {
        // Arrange: Criar usuário cliente
        $cliente = Usuario::factory()->create([
            'tipo_usuario' => 'usuario'
        ]);

        $token = JWTAuth::fromUser($cliente);
        $pdfFile = UploadedFile::fake()->create('laudo.pdf', 1024, 'application/pdf');

        // Act: Tentar criar laudo
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/laudos', [
            'titulo' => 'Laudo do Cliente',
            'arquivo' => $pdfFile
        ]);

        // Assert: Deve ser negado (403 Forbidden)
        $response->assertStatus(403);
    }

    /** @test */
    public function pode_fazer_download_de_laudo()
    {
        // Arrange: Criar laudo no banco
        $admin = Usuario::factory()->create(['tipo_usuario' => 'administrador']);
        $cliente = Usuario::factory()->create(['tipo_usuario' => 'usuario']);
        
        $laudo = \App\Models\Laudo::create([
            'usuario_id' => $admin->id,
            'titulo' => 'Laudo para Download',
            'descricao' => 'Teste de download',
            'url_arquivo' => 'laudos/2024/10/test-file.pdf',
            'ativo' => true
        ]);

        // Simular arquivo no S3
        Storage::disk('s3')->put('laudos/2024/10/test-file.pdf', 'fake pdf content');

        $token = JWTAuth::fromUser($cliente);

        // Act: Fazer download
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson("/api/laudos/{$laudo->id}/download");

        // Assert: Deve retornar URL de download
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'url',
                        'nome_arquivo',
                        'titulo',
                        'content_type'
                    ]
                ]);
    }
}