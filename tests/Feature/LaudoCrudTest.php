<?php

namespace Tests\Feature;

use App\Models\Laudo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class LaudoCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $admin;
    protected Usuario $cliente;
    protected string $adminToken;
    protected string $clienteToken;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurar storage fake
        Storage::fake('s3');
        
        // Executar migrations e seeders
        $this->artisan('migrate');
        $this->artisan('db:seed', ['--class' => 'PermissaoSeeder']);
        
        // Criar usuários de teste
        $this->admin = Usuario::create([
            'primeiro_nome' => 'Admin',
            'segundo_nome' => 'Sistema',
            'apelido' => 'admin',
            'email' => 'admin@test.com',
            'senha' => 'admin123',
            'telefone' => '11999999999',
            'numero_documento' => '12345678901',
            'data_nascimento' => '1980-01-01',
            'tipo_usuario' => 'administrador',
            'aceite_comunicacoes_email' => true,
            'aceite_comunicacoes_sms' => false,
            'aceite_comunicacoes_whatsapp' => false,
            'ativo' => true
        ]);

        $this->cliente = Usuario::create([
            'primeiro_nome' => 'Cliente',
            'segundo_nome' => 'Teste',
            'apelido' => 'cliente',
            'email' => 'cliente@test.com',
            'senha' => 'cliente123',
            'telefone' => '11888888888',
            'numero_documento' => '98765432101',
            'data_nascimento' => '1990-06-15',
            'tipo_usuario' => 'usuario',
            'aceite_comunicacoes_email' => true,
            'aceite_comunicacoes_sms' => true,
            'aceite_comunicacoes_whatsapp' => true,
            'ativo' => true
        ]);

        $this->adminToken = JWTAuth::fromUser($this->admin);
        $this->clienteToken = JWTAuth::fromUser($this->cliente);
    }

    /** @test */
    public function admin_pode_criar_laudo_com_arquivo_pdf()
    {
        // Arrange: Criar arquivo PDF fake
        $pdfFile = UploadedFile::fake()->create('laudo_exame.pdf', 1024, 'application/pdf');

        // Act: Criar laudo
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
        ])->postJson('/api/laudos', [
            'titulo' => 'Exame de Sangue - João Silva',
            'descricao' => 'Resultado do exame de sangue completo',
            'arquivo' => $pdfFile
        ]);

        // Assert: Laudo deve ser criado com sucesso
        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Laudo criado com sucesso'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'titulo',
                        'descricao',
                        'url_arquivo',
                        'usuario_id',
                        'ativo',
                        'created_at'
                    ]
                ]);

        // Verificar se foi salvo no banco
        $this->assertDatabaseHas('laudos', [
            'titulo' => 'Exame de Sangue - João Silva',
            'descricao' => 'Resultado do exame de sangue completo',
            'usuario_id' => $this->admin->id,
            'ativo' => true
        ]);

        // Verificar se arquivo foi "enviado" para S3
        $laudoData = $response->json('data');
        $this->assertTrue(Storage::disk('s3')->exists($laudoData['url_arquivo']));
    }

    /** @test */
    public function cliente_nao_pode_criar_laudo()
    {
        // Arrange: Criar arquivo PDF fake
        $pdfFile = UploadedFile::fake()->create('laudo_teste.pdf', 1024, 'application/pdf');

        // Act: Tentar criar laudo como cliente
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->clienteToken}",
        ])->postJson('/api/laudos', [
            'titulo' => 'Tentativa de Laudo',
            'arquivo' => $pdfFile
        ]);

        // Assert: Deve ser negado
        $response->assertStatus(403);
    }

    /** @test */
    public function deve_validar_campos_obrigatorios_ao_criar_laudo()
    {
        // Act: Tentar criar laudo sem campos obrigatórios
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
        ])->postJson('/api/laudos', []);

        // Assert: Deve retornar erros de validação
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['titulo', 'arquivo']);
    }

    /** @test */
    public function deve_rejeitar_arquivo_que_nao_seja_pdf()
    {
        // Arrange: Criar arquivo que não é PDF
        $txtFile = UploadedFile::fake()->create('documento.txt', 100, 'text/plain');

        // Act: Tentar criar laudo com arquivo inválido
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
        ])->postJson('/api/laudos', [
            'titulo' => 'Laudo Inválido',
            'arquivo' => $txtFile
        ]);

        // Assert: Deve ser rejeitado
        $response->assertStatus(422)
                ->assertJsonValidationErrors('arquivo');
    }

    /** @test */
    public function pode_listar_laudos_autenticado()
    {
        // Arrange: Criar alguns laudos
        $laudo1 = Laudo::create([
            'usuario_id' => $this->admin->id,
            'titulo' => 'Exame Cardiológico',
            'descricao' => 'Resultado do eletrocardiograma',
            'url_arquivo' => 'laudos/2024/10/exame1.pdf',
            'ativo' => true
        ]);

        $laudo2 = Laudo::create([
            'usuario_id' => $this->admin->id,
            'titulo' => 'Raio-X Tórax',
            'descricao' => 'Radiografia do tórax',
            'url_arquivo' => 'laudos/2024/10/exame2.pdf',
            'ativo' => true
        ]);

        // Act: Listar laudos como cliente autenticado
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->clienteToken}",
        ])->getJson('/api/laudos');

        // Assert: Deve retornar lista de laudos
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'titulo',
                                'descricao',
                                'created_at'
                            ]
                        ],
                        'current_page',
                        'total'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertGreaterThanOrEqual(2, count($response->json('data.data')));
    }

    /** @test */
    public function nao_pode_listar_laudos_sem_autenticacao()
    {
        // Act: Tentar listar laudos sem token
        $response = $this->getJson('/api/laudos');

        // Assert: Deve ser negado
        $response->assertStatus(401);
    }

    /** @test */
    public function pode_visualizar_laudo_especifico()
    {
        // Arrange: Criar laudo
        $laudo = Laudo::create([
            'usuario_id' => $this->admin->id,
            'titulo' => 'Exame Neurológico',
            'descricao' => 'Ressonância magnética do crânio',
            'url_arquivo' => 'laudos/2024/10/neuro.pdf',
            'ativo' => true
        ]);

        // Act: Visualizar laudo específico
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->clienteToken}",
        ])->getJson("/api/laudos/{$laudo->id}");

        // Assert: Deve retornar dados do laudo
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $laudo->id,
                        'titulo' => 'Exame Neurológico',
                        'descricao' => 'Ressonância magnética do crânio'
                    ]
                ]);
    }

    /** @test */
    public function admin_pode_atualizar_laudo()
    {
        // Arrange: Criar laudo
        $laudo = Laudo::create([
            'usuario_id' => $this->admin->id,
            'titulo' => 'Título Original',
            'descricao' => 'Descrição Original',
            'url_arquivo' => 'laudos/2024/10/original.pdf',
            'ativo' => true
        ]);

        // Act: Atualizar laudo
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
        ])->putJson("/api/laudos/{$laudo->id}", [
            'titulo' => 'Título Atualizado',
            'descricao' => 'Nova descrição do laudo'
        ]);

        // Assert: Laudo deve ser atualizado
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Laudo atualizado com sucesso',
                    'data' => [
                        'titulo' => 'Título Atualizado',
                        'descricao' => 'Nova descrição do laudo'
                    ]
                ]);

        // Verificar no banco
        $this->assertDatabaseHas('laudos', [
            'id' => $laudo->id,
            'titulo' => 'Título Atualizado',
            'descricao' => 'Nova descrição do laudo'
        ]);
    }

    /** @test */
    public function cliente_nao_pode_atualizar_laudo()
    {
        // Arrange: Criar laudo
        $laudo = Laudo::create([
            'usuario_id' => $this->admin->id,
            'titulo' => 'Laudo Protegido',
            'url_arquivo' => 'laudos/2024/10/protegido.pdf',
            'ativo' => true
        ]);

        // Act: Tentar atualizar como cliente
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->clienteToken}",
        ])->putJson("/api/laudos/{$laudo->id}", [
            'titulo' => 'Tentativa de Alteração'
        ]);

        // Assert: Deve ser negado
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_pode_remover_laudo()
    {
        // Arrange: Criar laudo
        $laudo = Laudo::create([
            'usuario_id' => $this->admin->id,
            'titulo' => 'Laudo a ser removido',
            'url_arquivo' => 'laudos/2024/10/remover.pdf',
            'ativo' => true
        ]);

        // Act: Remover laudo
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
        ])->deleteJson("/api/laudos/{$laudo->id}");

        // Assert: Laudo deve ser removido (soft delete)
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Laudo removido com sucesso'
                ]);

        // Verificar soft delete
        $this->assertDatabaseHas('laudos', [
            'id' => $laudo->id,
            'ativo' => false
        ]);
    }

    /** @test */
    public function pode_fazer_download_de_laudo()
    {
        // Arrange: Criar laudo e simular arquivo no S3
        $laudo = Laudo::create([
            'usuario_id' => $this->admin->id,
            'titulo' => 'Laudo para Download',
            'descricao' => 'Teste de download',
            'url_arquivo' => 'laudos/2024/10/download-test.pdf',
            'ativo' => true
        ]);

        Storage::disk('s3')->put('laudos/2024/10/download-test.pdf', 'fake pdf content');

        // Act: Fazer download
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->clienteToken}",
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

    /** @test */
    public function pode_consultar_laudo_publicamente()
    {
        // Arrange: Criar laudo público
        $laudo = Laudo::create([
            'usuario_id' => $this->admin->id,
            'titulo' => 'Laudo Público',
            'descricao' => 'Este laudo pode ser consultado publicamente',
            'url_arquivo' => 'laudos/2024/10/publico.pdf',
            'ativo' => true
        ]);

        // Act: Consultar sem autenticação
        $response = $this->getJson("/api/laudos/consultar/{$laudo->id}");

        // Assert: Deve permitir acesso público
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $laudo->id,
                        'titulo' => 'Laudo Público',
                        'descricao' => 'Este laudo pode ser consultado publicamente'
                    ]
                ]);
    }

    /** @test */
    public function pode_buscar_laudos_por_termo()
    {
        // Arrange: Criar laudos com diferentes títulos
        $laudo1 = Laudo::create([
            'usuario_id' => $this->admin->id,
            'titulo' => 'Exame de Sangue Completo',
            'descricao' => 'Hemograma completo',
            'url_arquivo' => 'laudos/2024/10/sangue.pdf',
            'ativo' => true
        ]);

        $laudo2 = Laudo::create([
            'usuario_id' => $this->admin->id,
            'titulo' => 'Raio-X do Joelho',
            'descricao' => 'Radiografia articular',
            'url_arquivo' => 'laudos/2024/10/joelho.pdf',
            'ativo' => true
        ]);

        // Act: Buscar por "sangue"
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->clienteToken}",
        ])->getJson('/api/laudos/buscar?busca=sangue');

        // Assert: Deve encontrar apenas o laudo de sangue
        $response->assertStatus(200);
        
        $results = $response->json('data.data');
        $this->assertCount(1, $results);
        $this->assertEquals('Exame de Sangue Completo', $results[0]['titulo']);
    }
}