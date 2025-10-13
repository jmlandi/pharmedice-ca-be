<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SignupTest extends TestCase
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
    public function pode_registrar_novo_usuario_com_dados_validos()
    {
        // Arrange: Dados válidos para registro
        $dadosRegistro = [
            'primeiro_nome' => 'João',
            'segundo_nome' => 'Silva',
            'apelido' => 'joaosilva123',
            'email' => 'joao.silva@exemplo.com',
            'senha' => 'MinhaSenh@123',
            'senha_confirmation' => 'MinhaSenh@123',
            'confirmacao_senha' => 'MinhaSenh@123',
            'telefone' => '(11) 99999-9999',
            'numero_documento' => '12345678901',
            'data_nascimento' => '1990-05-15',
            'aceite_comunicacoes_email' => true,
            'aceite_comunicacoes_sms' => false,
            'aceite_comunicacoes_whatsapp' => true,
            'aceite_termos_uso' => true,
            'aceite_politica_privacidade' => true,
        ];

        // Act: Fazer requisição de registro
        $response = $this->postJson('/api/auth/registrar-usuario', $dadosRegistro);

        // Assert: Verificar resposta de sucesso
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'sucesso',
                    'mensagem',
                    'dados' => [
                        'access_token',
                        'token_type',
                        'expires_in',
                        'usuario' => [
                            'id',
                            'primeiro_nome',
                            'segundo_nome',
                            'email',
                            'tipo_usuario',
                            'email_verificado',
                            'criado_em'
                        ],
                        'mensagem_verificacao'
                    ]
                ]);

        $this->assertTrue($response->json('sucesso'));
        
        // Verificar se usuário foi salvo no banco
        $this->assertDatabaseHas('usuarios', [
            'email' => 'joao.silva@exemplo.com',
            'primeiro_nome' => 'João',
            'segundo_nome' => 'Silva',
            'apelido' => 'joaosilva123',
            'tipo_usuario' => 'usuario',
            'ativo' => true
        ]);

        // Verificar se email de verificação seria enviado
        // Mail::assertQueued(VerifyEmail::class);
    }

    /** @test */
    public function nao_pode_registrar_com_email_duplicado()
    {
        // Arrange: Criar usuário existente
        Usuario::create([
            'primeiro_nome' => 'Usuário',
            'segundo_nome' => 'Existente',
            'apelido' => 'usuarioexistente',
            'email' => 'existente@exemplo.com',
            'senha' => 'senha123',
            'telefone' => '(11) 88888-8888',
            'numero_documento' => '98765432100',
            'data_nascimento' => '1985-01-01',
            'tipo_usuario' => 'usuario',
            'ativo' => true
        ]);

        $dadosRegistro = [
            'primeiro_nome' => 'João',
            'segundo_nome' => 'Silva',
            'apelido' => 'joaosilva',
            'email' => 'existente@exemplo.com', // Email já existe
            'senha' => 'MinhaSenh@123',
            'senha_confirmation' => 'MinhaSenh@123',
            'confirmacao_senha' => 'MinhaSenh@123',
            'telefone' => '(11) 99999-9999',
            'numero_documento' => '12345678901',
            'data_nascimento' => '1990-05-15',
            'aceite_termos_uso' => true,
            'aceite_politica_privacidade' => true,
        ];

        // Act: Tentar registrar com email duplicado
        $response = $this->postJson('/api/auth/registrar-usuario', $dadosRegistro);

        // Assert: Deve falhar com erro de validação
        $response->assertStatus(422)
                ->assertJsonStructure([
                    'sucesso',
                    'mensagem',
                    'erros' => ['email']
                ]);

        $erros = $response->json('erros');
        $this->assertArrayHasKey('email', $erros);
    }

    /** @test */
    public function nao_pode_registrar_com_cpf_duplicado()
    {
        // Arrange: Criar usuário com CPF existente
        Usuario::create([
            'primeiro_nome' => 'Usuário',
            'segundo_nome' => 'Existente',
            'apelido' => 'usuarioexistente',
            'email' => 'existente@exemplo.com',
            'senha' => 'senha123',
            'telefone' => '(11) 88888-8888',
            'numero_documento' => '12345678901', // CPF que será duplicado
            'data_nascimento' => '1985-01-01',
            'tipo_usuario' => 'usuario',
            'ativo' => true
        ]);

        $dadosRegistro = [
            'primeiro_nome' => 'João',
            'segundo_nome' => 'Silva',
            'apelido' => 'joaosilva',
            'email' => 'joao@exemplo.com',
            'senha' => 'MinhaSenh@123',
            'senha_confirmation' => 'MinhaSenh@123',
            'confirmacao_senha' => 'MinhaSenh@123',
            'telefone' => '(11) 99999-9999',
            'numero_documento' => '12345678901', // CPF duplicado
            'data_nascimento' => '1990-05-15',
            'aceite_termos_uso' => true,
            'aceite_politica_privacidade' => true,
        ];

        // Act: Tentar registrar com CPF duplicado
        $response = $this->postJson('/api/auth/registrar-usuario', $dadosRegistro);

        // Assert: Deve falhar com erro de validação
        $response->assertStatus(422)
                ->assertJsonStructure([
                    'sucesso',
                    'mensagem', 
                    'erros' => ['numero_documento']
                ]);

        $erros = $response->json('erros');
        $this->assertArrayHasKey('numero_documento', $erros);
    }

    /** @test */
    public function deve_validar_campos_obrigatorios()
    {
        // Act: Tentar registrar sem dados obrigatórios
        $response = $this->postJson('/api/auth/registrar-usuario', []);

        // Assert: Deve falhar com múltiplos erros de validação
        $response->assertStatus(422)
                ->assertJsonStructure([
                    'sucesso',
                    'mensagem',
                    'erros' => [
                        'primeiro_nome',
                        'segundo_nome',
                        'apelido',
                        'email',
                        'senha',
                        'confirmacao_senha',
                        'telefone',
                        'numero_documento',
                        'data_nascimento',
                        'aceite_termos_uso',
                        'aceite_politica_privacidade'
                    ]
                ])
                ->assertJson([
                    'sucesso' => false,
                    'mensagem' => 'Dados inválidos fornecidos'
                ]);

        // Verificar se os erros de validação específicos existem
        $erros = $response->json('erros');
        $this->assertArrayHasKey('primeiro_nome', $erros);
        $this->assertArrayHasKey('segundo_nome', $erros);
        $this->assertArrayHasKey('apelido', $erros);
        $this->assertArrayHasKey('email', $erros);
        $this->assertArrayHasKey('senha', $erros);
    }

    /** @test */
    public function deve_validar_formato_de_senha_forte()
    {
        $dadosRegistro = [
            'primeiro_nome' => 'João',
            'segundo_nome' => 'Silva',
            'apelido' => 'joaosilva',
            'email' => 'joao@exemplo.com',
            'senha' => 'senha123', // Senha fraca - sem maiúscula e caractere especial
            'senha_confirmation' => 'senha123',
            'confirmacao_senha' => 'senha123',
            'telefone' => '(11) 99999-9999',
            'numero_documento' => '12345678901',
            'data_nascimento' => '1990-05-15',
            'aceite_termos_uso' => true,
            'aceite_politica_privacidade' => true,
        ];

        // Act: Tentar registrar com senha fraca
        $response = $this->postJson('/api/auth/registrar-usuario', $dadosRegistro);

        // Assert: Deve falhar com erro de validação de senha
        $response->assertStatus(422);
        $erros = $response->json('erros');
        $this->assertArrayHasKey('senha', $erros);
    }

    /** @test */
    public function deve_validar_formato_de_telefone()
    {
        $dadosRegistro = [
            'primeiro_nome' => 'João',
            'segundo_nome' => 'Silva',
            'apelido' => 'joaosilva',
            'email' => 'joao@exemplo.com',
            'senha' => 'MinhaSenh@123',
            'senha_confirmation' => 'MinhaSenh@123',
            'confirmacao_senha' => 'MinhaSenh@123',
            'telefone' => '11999999999', // Formato inválido - sem parênteses e hífen
            'numero_documento' => '12345678901',
            'data_nascimento' => '1990-05-15',
            'aceite_termos_uso' => true,
            'aceite_politica_privacidade' => true,
        ];

        // Act: Tentar registrar com telefone em formato inválido
        $response = $this->postJson('/api/auth/registrar-usuario', $dadosRegistro);

        // Assert: Deve falhar com erro de validação de telefone
        $response->assertStatus(422);
        $erros = $response->json('erros');
        $this->assertArrayHasKey('telefone', $erros);
    }

    /** @test */
    public function deve_validar_cpf_com_11_digitos()
    {
        $dadosRegistro = [
            'primeiro_nome' => 'João',
            'segundo_nome' => 'Silva',
            'apelido' => 'joaosilva',
            'email' => 'joao@exemplo.com',
            'senha' => 'MinhaSenh@123',
            'senha_confirmation' => 'MinhaSenh@123',
            'confirmacao_senha' => 'MinhaSenh@123',
            'telefone' => '(11) 99999-9999',
            'numero_documento' => '123456789', // CPF com menos de 11 dígitos
            'data_nascimento' => '1990-05-15',
            'aceite_termos_uso' => true,
            'aceite_politica_privacidade' => true,
        ];

        // Act: Tentar registrar com CPF inválido
        $response = $this->postJson('/api/auth/registrar-usuario', $dadosRegistro);

        // Assert: Deve falhar com erro de validação de CPF
        $response->assertStatus(422);
        $erros = $response->json('erros');
        $this->assertArrayHasKey('numero_documento', $erros);
    }

    /** @test */
    public function deve_validar_data_nascimento()
    {
        $dadosRegistro = [
            'primeiro_nome' => 'João',
            'segundo_nome' => 'Silva',
            'apelido' => 'joaosilva',
            'email' => 'joao@exemplo.com',
            'senha' => 'MinhaSenh@123',
            'senha_confirmation' => 'MinhaSenh@123',
            'confirmacao_senha' => 'MinhaSenh@123',
            'telefone' => '(11) 99999-9999',
            'numero_documento' => '12345678901',
            'data_nascimento' => '2030-01-01', // Data futura
            'aceite_termos_uso' => true,
            'aceite_politica_privacidade' => true,
        ];

        // Act: Tentar registrar com data de nascimento futura
        $response = $this->postJson('/api/auth/registrar-usuario', $dadosRegistro);

        // Assert: Deve falhar com erro de validação de data
        $response->assertStatus(422);
        $erros = $response->json('erros');
        $this->assertArrayHasKey('data_nascimento', $erros);
    }

    /** @test */
    public function deve_exigir_aceite_dos_termos()
    {
        $dadosRegistro = [
            'primeiro_nome' => 'João',
            'segundo_nome' => 'Silva',
            'apelido' => 'joaosilva',
            'email' => 'joao@exemplo.com',
            'senha' => 'MinhaSenh@123',
            'senha_confirmation' => 'MinhaSenh@123',
            'confirmacao_senha' => 'MinhaSenh@123',
            'telefone' => '(11) 99999-9999',
            'numero_documento' => '12345678901',
            'data_nascimento' => '1990-05-15',
            'aceite_termos_uso' => false, // Não aceita termos
            'aceite_politica_privacidade' => true,
        ];

        // Act: Tentar registrar sem aceitar termos
        $response = $this->postJson('/api/auth/registrar-usuario', $dadosRegistro);

        // Assert: Deve falhar com erro de validação
        $response->assertStatus(422);
        $erros = $response->json('erros');
        $this->assertArrayHasKey('aceite_termos_uso', $erros);
    }
}