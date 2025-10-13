<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Executar migrations
        $this->artisan('migrate');
        
        // Configurar Mail fake para não enviar emails reais durante os testes
        Mail::fake();
    }

    /** @test */
    public function usuario_pode_verificar_email_com_link_valido()
    {
        // Arrange: Criar usuário sem email verificado
        $usuario = Usuario::create([
            'primeiro_nome' => 'João',
            'segundo_nome' => 'Silva',
            'apelido' => 'joaosilva',
            'email' => 'joao@exemplo.com',
            'senha' => bcrypt('MinhaSenh@123'),
            'telefone' => '(11) 99999-9999',
            'numero_documento' => '12345678901',
            'data_nascimento' => '1990-05-15',
            'tipo_usuario' => 'usuario',
            'ativo' => true,
            'email_verified_at' => null // Email não verificado
        ]);

        // Gerar URL de verificação válida para extrair parâmetros
        // A URL assinada deve incluir id e hash como parte da URL assinada
        $urlVerificacao = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $usuario->id,
                'hash' => sha1($usuario->email)
            ]
        );

        // Extrair parâmetros da URL
        $parsedUrl = parse_url($urlVerificacao);
        parse_str($parsedUrl['query'], $params);

        // Act: Verificar email usando POST com parâmetros incluindo id e hash
        $response = $this->postJson('/api/auth/verificar-email', [
            'id' => $params['id'] ?? $usuario->id,
            'hash' => $params['hash'] ?? sha1($usuario->email),
            'expires' => $params['expires'],
            'signature' => $params['signature']
        ]);

        // Assert: Verificação deve ser bem-sucedida
        $response->assertStatus(200)
                ->assertJson([
                    'sucesso' => true,
                    'mensagem' => 'Email verificado com sucesso!'
                ]);

        // Verificar se email foi marcado como verificado no banco
        $usuario->refresh();
        $this->assertNotNull($usuario->email_verified_at);
    }

    /** @test */
    public function nao_pode_verificar_email_com_link_expirado()
    {
        // Arrange: Criar usuário sem email verificado
        $usuario = Usuario::create([
            'primeiro_nome' => 'João',
            'segundo_nome' => 'Silva',
            'apelido' => 'joaosilva',
            'email' => 'joao@exemplo.com',
            'senha' => bcrypt('MinhaSenh@123'),
            'telefone' => '(11) 99999-9999',
            'numero_documento' => '12345678901',
            'data_nascimento' => '1990-05-15',
            'tipo_usuario' => 'usuario',
            'ativo' => true,
            'email_verified_at' => null
        ]);

        // Gerar URL de verificação expirada (data no passado)
        $urlVerificacao = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinutes(10), // 10 minutos no passado
            ['id' => $usuario->id, 'hash' => sha1($usuario->email)]
        );

        // Extrair parâmetros da URL
        $parsedUrl = parse_url($urlVerificacao);
        parse_str($parsedUrl['query'], $params);

        // Act: Tentar verificar com URL expirada
        $response = $this->postJson('/api/auth/verificar-email', [
            'id' => $usuario->id,
            'hash' => sha1($usuario->email),
            'expires' => $params['expires'],
            'signature' => $params['signature']
        ]);

        // Assert: Deve falhar com erro de link inválido/expirado
        $response->assertStatus(422)
                ->assertJson([
                    'sucesso' => false,
                    'codigo' => 'LINK_INVALIDO'
                ]);

        // Verificar que email ainda não foi verificado
        $usuario->refresh();
        $this->assertNull($usuario->email_verified_at);
    }

    /** @test */
    public function pode_reenviar_email_de_verificacao()
    {
        // Arrange: Criar usuário sem email verificado
        $usuario = Usuario::create([
            'primeiro_nome' => 'João',
            'segundo_nome' => 'Silva',
            'apelido' => 'joaosilva',
            'email' => 'joao@exemplo.com',
            'senha' => bcrypt('MinhaSenh@123'),
            'telefone' => '(11) 99999-9999',
            'numero_documento' => '12345678901',
            'data_nascimento' => '1990-05-15',
            'tipo_usuario' => 'usuario',
            'ativo' => true,
            'email_verified_at' => null
        ]);

        // Gerar token JWT diretamente para o usuário
        $token = JWTAuth::fromUser($usuario);

        // Act: Reenviar email de verificação
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/reenviar-verificacao-email');

        // Assert: Deve ser bem-sucedido
        $response->assertStatus(200)
                ->assertJson([
                    'sucesso' => true
                ]);

        // Verificar que a mensagem contém o email
        $this->assertStringContainsString('joao@exemplo.com', $response->json('mensagem'));

        // Verificar que email de verificação seria enviado novamente
        // Mail::assertQueued(VerifyEmail::class);
    }

    /** @test */
    public function nao_pode_reenviar_se_email_ja_verificado()
    {
        // Arrange: Criar usuário com email já verificado
        $usuario = Usuario::create([
            'primeiro_nome' => 'João',
            'segundo_nome' => 'Silva',
            'apelido' => 'joaosilva',
            'email' => 'joao@exemplo.com',
            'senha' => bcrypt('MinhaSenh@123'),
            'telefone' => '(11) 99999-9999',
            'numero_documento' => '12345678901',
            'data_nascimento' => '1990-05-15',
            'tipo_usuario' => 'usuario',
            'ativo' => true,
            'email_verified_at' => now() // Email já verificado
        ]);

        // Garantir que o usuário tenha o email verificado
        $usuario->markEmailAsVerified();

        // Gerar token JWT diretamente para o usuário
        $token = JWTAuth::fromUser($usuario);

        // Act: Tentar reenviar email quando já está verificado
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/reenviar-verificacao-email');

        // Assert: Deve retornar que já está verificado
        $response->assertStatus(422)
                ->assertJson([
                    'sucesso' => false
                ]);

        // A mensagem deve indicar que o email já está verificado
        $this->assertStringContainsString('já foi verificado', $response->json('mensagem'));
    }

    /** @test */
    public function deve_exigir_autenticacao_para_reenviar_verificacao()
    {
        // Act: Tentar reenviar sem estar autenticado
        $response = $this->postJson('/api/auth/reenviar-verificacao-email');

        // Assert: Deve falhar por falta de autenticação
        $response->assertStatus(401);
    }

        /** @test */
    public function nao_pode_verificar_email_com_link_nao_assinado()
    {
        // Arrange: Criar usuário sem email verificado
        $usuario = Usuario::create([
            'primeiro_nome' => 'João',
            'segundo_nome' => 'Silva',
            'apelido' => 'joao',
            'email' => 'joao@test.com',
            'senha' => 'password123',
            'telefone' => '11999999999',
            'numero_documento' => '12345678901',
            'data_nascimento' => '1990-05-15',
            'tipo_usuario' => 'usuario',
            'ativo' => true,
            'email_verified_at' => null
        ]);

        // Act: Tentar verificar com parâmetros sem assinatura válida
        $response = $this->postJson('/api/auth/verificar-email', [
            'id' => $usuario->id,
            'hash' => sha1($usuario->email),
            'expires' => now()->addMinutes(60)->timestamp,
            'signature' => 'invalid_signature'
        ]);

        // Assert: Deve retornar erro de link inválido
        $response->assertStatus(422)
                ->assertJson([
                    'sucesso' => false,
                    'codigo' => 'LINK_INVALIDO'
                ]);
    }
}