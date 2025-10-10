<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class UsuarioCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $admin;
    protected Usuario $cliente;
    protected string $adminToken;
    protected string $clienteToken;

    protected function setUp(): void
    {
        parent::setUp();
        
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
    public function admin_pode_listar_usuarios()
    {
        // Act: Listar usuários como admin
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
        ])->getJson('/api/usuarios');

        // Assert: Deve retornar lista de usuários
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'primeiro_nome',
                                'segundo_nome',
                                'email',
                                'tipo_usuario',
                                'ativo',
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
    public function cliente_nao_pode_listar_usuarios()
    {
        // Act: Tentar listar usuários como cliente
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->clienteToken}",
        ])->getJson('/api/usuarios');

        // Assert: Deve ser negado
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_pode_criar_usuario()
    {
        // Arrange: Dados do novo usuário
        $dadosUsuario = [
            'primeiro_nome' => 'Novo',
            'segundo_nome' => 'Usuario',
            'apelido' => 'novousuario',
            'email' => 'novo@test.com',
            'senha' => 'senha123',
            'telefone' => '11777777777',
            'numero_documento' => '11122233344',
            'data_nascimento' => '1995-03-20',
            'tipo_usuario' => 'usuario',
            'aceite_comunicacoes_email' => true,
            'aceite_comunicacoes_sms' => false,
            'aceite_comunicacoes_whatsapp' => true
        ];

        // Act: Criar usuário
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
        ])->postJson('/api/usuarios', $dadosUsuario);

        // Assert: Usuário deve ser criado
        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Usuário criado com sucesso'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'primeiro_nome',
                        'segundo_nome',
                        'email',
                        'tipo_usuario',
                        'ativo'
                    ]
                ]);

        // Verificar no banco
        $this->assertDatabaseHas('usuarios', [
            'email' => 'novo@test.com',
            'primeiro_nome' => 'Novo',
            'segundo_nome' => 'Usuario',
            'tipo_usuario' => 'usuario',
            'ativo' => true
        ]);
    }

    /** @test */
    public function cliente_nao_pode_criar_usuario()
    {
        // Arrange: Dados do usuário
        $dadosUsuario = [
            'primeiro_nome' => 'Tentativa',
            'segundo_nome' => 'Negada',
            'email' => 'tentativa@test.com',
            'senha' => 'senha123',
            'telefone' => '11666666666',
            'numero_documento' => '99988877766',
            'data_nascimento' => '1992-07-10',
            'tipo_usuario' => 'usuario'
        ];

        // Act: Tentar criar usuário como cliente
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->clienteToken}",
        ])->postJson('/api/usuarios', $dadosUsuario);

        // Assert: Deve ser negado
        $response->assertStatus(403);
    }

    /** @test */
    public function deve_validar_campos_obrigatorios_ao_criar_usuario()
    {
        // Act: Tentar criar usuário sem dados obrigatórios
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
        ])->postJson('/api/usuarios', []);

        // Assert: Deve retornar erros de validação
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'primeiro_nome',
                    'segundo_nome',
                    'email',
                    'senha',
                    'telefone',
                    'numero_documento',
                    'data_nascimento'
                ]);
    }

    /** @test */
    public function deve_validar_email_unico()
    {
        // Arrange: Dados com email já existente
        $dadosUsuario = [
            'primeiro_nome' => 'Email',
            'segundo_nome' => 'Duplicado',
            'apelido' => 'emaildup',
            'email' => 'admin@test.com', // Email já existe
            'senha' => 'senha123',
            'telefone' => '11555555555',
            'numero_documento' => '44455566677',
            'data_nascimento' => '1988-11-22',
            'tipo_usuario' => 'usuario'
        ];

        // Act: Tentar criar usuário
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
        ])->postJson('/api/usuarios', $dadosUsuario);

        // Assert: Deve falhar por email duplicado
        $response->assertStatus(422)
                ->assertJsonValidationErrors('email');
    }

    /** @test */
    public function admin_pode_visualizar_usuario_especifico()
    {
        // Act: Visualizar usuário específico
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
        ])->getJson("/api/usuarios/{$this->cliente->id}");

        // Assert: Deve retornar dados do usuário
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $this->cliente->id,
                        'primeiro_nome' => 'Cliente',
                        'segundo_nome' => 'Teste',
                        'email' => 'cliente@test.com',
                        'tipo_usuario' => 'usuario'
                    ]
                ]);
    }

    /** @test */
    public function admin_pode_atualizar_usuario()
    {
        // Arrange: Dados para atualização
        $dadosAtualizacao = [
            'primeiro_nome' => 'Cliente Atualizado',
            'segundo_nome' => 'Nome Novo',
            'telefone' => '11999888777'
        ];

        // Act: Atualizar usuário
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
        ])->putJson("/api/usuarios/{$this->cliente->id}", $dadosAtualizacao);

        // Assert: Usuário deve ser atualizado
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Usuário atualizado com sucesso',
                    'data' => [
                        'primeiro_nome' => 'Cliente Atualizado',
                        'segundo_nome' => 'Nome Novo',
                        'telefone' => '11999888777'
                    ]
                ]);

        // Verificar no banco
        $this->assertDatabaseHas('usuarios', [
            'id' => $this->cliente->id,
            'primeiro_nome' => 'Cliente Atualizado',
            'segundo_nome' => 'Nome Novo',
            'telefone' => '11999888777'
        ]);
    }

    /** @test */
    public function cliente_nao_pode_atualizar_outros_usuarios()
    {
        // Act: Tentar atualizar outro usuário como cliente
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->clienteToken}",
        ])->putJson("/api/usuarios/{$this->admin->id}", [
            'primeiro_nome' => 'Tentativa de Hack'
        ]);

        // Assert: Deve ser negado
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_pode_remover_usuario()
    {
        // Act: Remover usuário
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
        ])->deleteJson("/api/usuarios/{$this->cliente->id}");

        // Assert: Usuário deve ser removido (soft delete)
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Usuário removido com sucesso'
                ]);

        // Verificar soft delete
        $this->assertDatabaseHas('usuarios', [
            'id' => $this->cliente->id,
            'ativo' => false
        ]);
    }

    /** @test */
    public function usuario_pode_alterar_propria_senha()
    {
        // Arrange: Nova senha
        $dadosSenha = [
            'senha_atual' => 'cliente123',
            'nova_senha' => 'novasenha456',
            'nova_senha_confirmation' => 'novasenha456'
        ];

        // Act: Alterar senha
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->clienteToken}",
        ])->putJson('/api/usuarios/alterar-senha', $dadosSenha);

        // Assert: Senha deve ser alterada
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Senha alterada com sucesso'
                ]);

        // Verificar se a nova senha funciona
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'cliente@test.com',
            'password' => 'novasenha456'
        ]);

        $loginResponse->assertStatus(200);
    }

    /** @test */
    public function deve_validar_senha_atual_ao_alterar_senha()
    {
        // Arrange: Dados com senha atual incorreta
        $dadosSenha = [
            'senha_atual' => 'senha_errada',
            'nova_senha' => 'novasenha456',
            'nova_senha_confirmation' => 'novasenha456'
        ];

        // Act: Tentar alterar senha
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->clienteToken}",
        ])->putJson('/api/usuarios/alterar-senha', $dadosSenha);

        // Assert: Deve falhar
        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Senha atual incorreta'
                ]);
    }

    /** @test */
    public function deve_validar_confirmacao_de_senha()
    {
        // Arrange: Dados com confirmação incorreta
        $dadosSenha = [
            'senha_atual' => 'cliente123',
            'nova_senha' => 'novasenha456',
            'nova_senha_confirmation' => 'senhadiferente'
        ];

        // Act: Tentar alterar senha
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->clienteToken}",
        ])->putJson('/api/usuarios/alterar-senha', $dadosSenha);

        // Assert: Deve falhar na validação
        $response->assertStatus(422)
                ->assertJsonValidationErrors('nova_senha');
    }

    /** @test */
    public function nao_pode_acessar_usuarios_sem_autenticacao()
    {
        // Act: Tentar acessar rotas de usuários sem token
        $responses = [
            $this->getJson('/api/usuarios'),
            $this->postJson('/api/usuarios', []),
            $this->getJson("/api/usuarios/{$this->cliente->id}"),
            $this->putJson("/api/usuarios/{$this->cliente->id}", []),
            $this->deleteJson("/api/usuarios/{$this->cliente->id}"),
        ];

        // Assert: Todas devem ser negadas
        foreach ($responses as $response) {
            $response->assertStatus(401);
        }
    }

    /** @test */
    public function pode_filtrar_usuarios_por_tipo()
    {
        // Arrange: Criar mais usuários de diferentes tipos
        Usuario::create([
            'primeiro_nome' => 'Admin2',
            'segundo_nome' => 'Segundo',
            'apelido' => 'admin2',
            'email' => 'admin2@test.com',
            'senha' => 'password',
            'telefone' => '11444444444',
            'numero_documento' => '33344455566',
            'data_nascimento' => '1975-12-01',
            'tipo_usuario' => 'administrador',
            'aceite_comunicacoes_email' => true,
            'aceite_comunicacoes_sms' => false,
            'aceite_comunicacoes_whatsapp' => false,
            'ativo' => true
        ]);

        // Act: Filtrar apenas administradores
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
        ])->getJson('/api/usuarios?tipo_usuario=administrador');

        // Assert: Deve retornar apenas admins
        $response->assertStatus(200);
        
        $usuarios = $response->json('data.data');
        foreach ($usuarios as $usuario) {
            $this->assertEquals('administrador', $usuario['tipo_usuario']);
        }
    }
}