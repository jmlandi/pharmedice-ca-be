<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Executar migrations e seeders
        $this->artisan('migrate');
        $this->artisan('db:seed', ['--class' => 'PermissaoSeeder']);
    }

    /** @test */
    public function pode_fazer_login_com_credenciais_validas()
    {
        // Arrange: Criar usuário de teste
        $usuario = Usuario::create([
            'primeiro_nome' => 'João',
            'segundo_nome' => 'Silva',
            'apelido' => 'joao',
            'email' => 'joao@test.com',
            'senha' => 'password123', // O mutator irá hashar automaticamente
            'telefone' => '11999999999',
            'numero_documento' => '12345678901',
            'data_nascimento' => '1990-01-01',
            'tipo_usuario' => 'usuario',
            'aceite_comunicacoes_email' => true,
            'aceite_comunicacoes_sms' => false,
            'aceite_comunicacoes_whatsapp' => false,
            'ativo' => true
        ]);

        // Act: Tentar fazer login
        $response = $this->postJson('/api/auth/login', [
            'email' => 'joao@test.com',
            'senha' => 'password123'
        ]);

        // Assert: Login deve ser bem-sucedido
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'access_token',
                        'token_type',
                        'expires_in',
                        'user' => [
                            'id',
                            'primeiro_nome',
                            'segundo_nome',
                            'email',
                            'tipo_usuario'
                        ]
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('bearer', $response->json('data.token_type'));
        $this->assertNotNull($response->json('data.access_token'));
    }

    /** @test */
    public function nao_pode_fazer_login_com_credenciais_invalidas()
    {
        // Arrange: Criar usuário de teste
        Usuario::create([
            'primeiro_nome' => 'João',
            'segundo_nome' => 'Silva',
            'apelido' => 'joao',
            'email' => 'joao@test.com',
            'senha' => 'password123',
            'telefone' => '11999999999',
            'numero_documento' => '12345678901',
            'data_nascimento' => '1990-01-01',
            'tipo_usuario' => 'usuario',
            'aceite_comunicacoes_email' => true,
            'aceite_comunicacoes_sms' => false,
            'aceite_comunicacoes_whatsapp' => false,
            'ativo' => true
        ]);

        // Act: Tentar login com senha incorreta
        $response = $this->postJson('/api/auth/login', [
            'email' => 'joao@test.com',
            'senha' => 'senha_errada'
        ]);

        // Assert: Login deve falhar
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Credenciais inválidas'
                ]);
    }

    /** @test */
    public function nao_pode_fazer_login_com_usuario_inativo()
    {
        // Arrange: Criar usuário inativo
        Usuario::create([
            'primeiro_nome' => 'João',
            'segundo_nome' => 'Silva',
            'apelido' => 'joao',
            'email' => 'joao@test.com',
            'senha' => 'password123',
            'telefone' => '11999999999',
            'numero_documento' => '12345678901',
            'data_nascimento' => '1990-01-01',
            'tipo_usuario' => 'usuario',
            'aceite_comunicacoes_email' => true,
            'aceite_comunicacoes_sms' => false,
            'aceite_comunicacoes_whatsapp' => false,
            'ativo' => false // Usuário inativo
        ]);

        // Act: Tentar fazer login
        $response = $this->postJson('/api/auth/login', [
            'email' => 'joao@test.com',
            'senha' => 'password123'
        ]);

        // Assert: Login deve ser negado
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Usuário inativo'
                ]);
    }

    /** @test */
    public function pode_obter_dados_do_usuario_autenticado()
    {
        // Arrange: Criar usuário e token
        $usuario = Usuario::create([
            'primeiro_nome' => 'Maria',
            'segundo_nome' => 'Santos',
            'apelido' => 'maria',
            'email' => 'maria@test.com',
            'senha' => 'password123',
            'telefone' => '11888888888',
            'numero_documento' => '98765432101',
            'data_nascimento' => '1985-05-15',
            'tipo_usuario' => 'administrador',
            'aceite_comunicacoes_email' => true,
            'aceite_comunicacoes_sms' => true,
            'aceite_comunicacoes_whatsapp' => true,
            'ativo' => true
        ]);

        $token = JWTAuth::fromUser($usuario);

        // Act: Fazer requisição autenticada
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/auth/me');

        // Assert: Deve retornar dados do usuário
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $usuario->id,
                        'primeiro_nome' => 'Maria',
                        'segundo_nome' => 'Santos',
                        'email' => 'maria@test.com',
                        'tipo_usuario' => 'administrador'
                    ]
                ]);
    }

    /** @test */
    public function nao_pode_acessar_rotas_protegidas_sem_token()
    {
        // Act: Tentar acessar rota protegida sem token
        $response = $this->getJson('/api/auth/me');

        // Assert: Deve ser negado
        $response->assertStatus(401);
    }

    /** @test */
    public function pode_fazer_logout()
    {
        // Arrange: Criar usuário e fazer login
        $usuario = Usuario::create([
            'primeiro_nome' => 'Carlos',
            'segundo_nome' => 'Lima',
            'apelido' => 'carlos',
            'email' => 'carlos@test.com',
            'senha' => 'password123',
            'telefone' => '11777777777',
            'numero_documento' => '11122233344',
            'data_nascimento' => '1992-12-25',
            'tipo_usuario' => 'usuario',
            'aceite_comunicacoes_email' => false,
            'aceite_comunicacoes_sms' => false,
            'aceite_comunicacoes_whatsapp' => false,
            'ativo' => true
        ]);

        $token = JWTAuth::fromUser($usuario);

        // Act: Fazer logout
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/auth/logout');

        // Assert: Logout deve ser bem-sucedido
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Logout realizado com sucesso'
                ]);

        // Verificar que o token não funciona mais
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    /** @test */
    public function pode_renovar_token()
    {
        // Arrange: Criar usuário
        $usuario = Usuario::create([
            'primeiro_nome' => 'Ana',
            'segundo_nome' => 'Costa',
            'apelido' => 'ana',
            'email' => 'ana@test.com',
            'senha' => 'password123',
            'telefone' => '11666666666',
            'numero_documento' => '55566677788',
            'data_nascimento' => '1988-08-08',
            'tipo_usuario' => 'usuario',
            'aceite_comunicacoes_email' => true,
            'aceite_comunicacoes_sms' => false,
            'aceite_comunicacoes_whatsapp' => true,
            'ativo' => true
        ]);

        $originalToken = JWTAuth::fromUser($usuario);

        // Act: Renovar token
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$originalToken}",
        ])->postJson('/api/auth/refresh');

        // Assert: Deve retornar novo token
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'access_token',
                        'token_type',
                        'expires_in'
                    ]
                ]);

        $newToken = $response->json('data.access_token');
        $this->assertNotEquals($originalToken, $newToken);

        // Verificar que o novo token funciona
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$newToken}",
        ])->getJson('/api/auth/me');

        $response->assertStatus(200);
    }
}