<?php

namespace App\Services;

use App\DTOs\LoginDTO;
use App\DTOs\SignupDTO;
use App\Mail\EmailVerificationMail;
use App\Mail\PasswordResetMail;
use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function login(LoginDTO $loginDTO): array
    {
        // Busca o usuário pelo email
        $usuario = Usuario::where('email', $loginDTO->email)->first();

        // Verifica se usuário existe
        if (!$usuario) {
            throw new \Exception('Credenciais inválidas', 401);
        }

        // Verifica se usuário está ativo
        if (!$usuario->ativo) {
            throw new \Exception('Usuário inativo', 401);
        }

        // Verifica a senha
        if (!Hash::check($loginDTO->senha, $usuario->senha)) {
            throw new \Exception('Credenciais inválidas', 401);
        }

        // Verifica se o email foi verificado
        if (!$usuario->hasVerifiedEmail()) {
            throw new \Exception('Email não verificado. Verifique sua caixa de entrada e clique no link de verificação enviado no momento do cadastro.', 403);
        }

        // Gera o token JWT
        $token = JWTAuth::fromUser($usuario);

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'usuario' => [
                'id' => $usuario->id,
                'primeiro_nome' => $usuario->primeiro_nome,
                'segundo_nome' => $usuario->segundo_nome,
                'email' => $usuario->email,
                'tipo_usuario' => $usuario->tipo_usuario,
                'is_admin' => $usuario->is_admin,
                'email_verificado' => $usuario->hasVerifiedEmail(),
            ]
        ];
    }

    public function logout(): void
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }

    public function refresh(): array
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());
        
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ];
    }

    public function me(): array
    {
        $usuario = JWTAuth::parseToken()->authenticate();
        
        return [
            'id' => $usuario->id,
            'nome_completo' => $usuario->nome_completo,
            'primeiro_nome' => $usuario->primeiro_nome,
            'segundo_nome' => $usuario->segundo_nome,
            'apelido' => $usuario->apelido,
            'email' => $usuario->email,
            'telefone' => $usuario->telefone,
            'numero_documento' => $usuario->numero_documento,
            'data_nascimento' => $usuario->data_nascimento?->format('Y-m-d'),
            'tipo_usuario' => $usuario->tipo_usuario,
            'is_admin' => $usuario->is_admin,
            'email_verificado' => $usuario->hasVerifiedEmail(),
            'email_verificado_em' => $usuario->email_verified_at?->format('Y-m-d H:i:s'),
            'aceite_comunicacoes_email' => $usuario->aceite_comunicacoes_email,
            'aceite_comunicacoes_sms' => $usuario->aceite_comunicacoes_sms,
            'aceite_comunicacoes_whatsapp' => $usuario->aceite_comunicacoes_whatsapp,
            'ativo' => $usuario->ativo,
            'avatar' => $usuario->avatar,
            'provider' => $usuario->provider,
        ];
    }

    /**
     * Registra um novo usuário no sistema
     * 
     * @param SignupDTO $dadosRegistro Dados do usuário para registro
     * @return array Dados do usuário criado com token JWT
     * @throws \Exception
     */
    public function registrarUsuario(SignupDTO $dadosRegistro): array
    {
        // Verifica se as senhas coincidem
        if (!$dadosRegistro->senhasCoicidem()) {
            throw new \Exception('As senhas não coincidem', 422);
        }

        // Verifica se o email já está em uso por um usuário ativo
        $usuarioExistente = Usuario::where('email', $dadosRegistro->email)
            ->where('ativo', true)
            ->first();
        if ($usuarioExistente) {
            throw new \Exception('Este email já está sendo utilizado', 409);
        }

        // Verifica se existe um usuário inativo com este documento para reativar
        $usuarioInativo = Usuario::where('numero_documento', $dadosRegistro->numero_documento)
            ->where('ativo', false)
            ->first();
        if ($usuarioInativo) {
            // Reativa o usuário existente com os novos dados
            $dadosUsuario = $dadosRegistro->toArray();
            $dadosUsuario['ativo'] = true;
            $dadosUsuario['email_verified_at'] = null; // Reset verificação de email
            
            $usuarioInativo->update($dadosUsuario);
            $novoUsuario = $usuarioInativo->refresh();
            
            // Envia email de verificação para o usuário reativado
            $this->enviarEmailVerificacao($novoUsuario);

            // Gera token JWT para o usuário reativado
            $token = JWTAuth::fromUser($novoUsuario);

            return [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
                'usuario' => [
                    'id' => $novoUsuario->id,
                    'primeiro_nome' => $novoUsuario->primeiro_nome,
                    'segundo_nome' => $novoUsuario->segundo_nome,
                    'email' => $novoUsuario->email,
                    'tipo_usuario' => $novoUsuario->tipo_usuario,
                    'email_verificado' => $novoUsuario->hasVerifiedEmail(),
                    'criado_em' => $novoUsuario->created_at,
                ],
                'mensagem_verificacao' => 'Sua conta foi reativada! Um email de verificação foi enviado para ' . $novoUsuario->email,
                'conta_reativada' => true
            ];
        }

        // Verifica se o número de documento já está em uso por um usuário ativo
        $documentoExistente = Usuario::where('numero_documento', $dadosRegistro->numero_documento)
            ->where('ativo', true)
            ->first();
        if ($documentoExistente) {
            throw new \Exception('Este número de documento já está sendo utilizado', 409);
        }

        // Cria o novo usuário
        $dadosUsuario = $dadosRegistro->toArray();
        $novoUsuario = Usuario::create($dadosUsuario);

        // Envia email de verificação
        $this->enviarEmailVerificacao($novoUsuario);

        // Gera token JWT para o usuário recém-criado
        $token = JWTAuth::fromUser($novoUsuario);

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'usuario' => [
                'id' => $novoUsuario->id,
                'primeiro_nome' => $novoUsuario->primeiro_nome,
                'segundo_nome' => $novoUsuario->segundo_nome,
                'email' => $novoUsuario->email,
                'tipo_usuario' => $novoUsuario->tipo_usuario,
                'email_verificado' => $novoUsuario->hasVerifiedEmail(),
                'criado_em' => $novoUsuario->created_at,
            ],
            'mensagem_verificacao' => 'Um email de verificação foi enviado para ' . $novoUsuario->email
        ];
    }

    /**
     * Envia email de verificação para o usuário
     * 
     * @param Usuario $usuario Usuário que receberá o email
     * @return void
     */
    private function enviarEmailVerificacao(Usuario $usuario): void
    {
        try {
            // URL do frontend baseada no tipo de usuário
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            
            // Define o caminho baseado no tipo de usuário
            if ($usuario->tipo_usuario === 'administrador') {
                $path = '/admin/verificar-email';
            } else {
                $path = '/cliente/verificar-email';
            }
            
            // Gera URL assinada temporária
            $signedUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $usuario->id,
                    'hash' => sha1($usuario->getEmailForVerification()),
                ]
            );
            
            // Extrai os parâmetros da URL assinada
            $parsedUrl = parse_url($signedUrl);
            parse_str($parsedUrl['query'], $params);
            
            // Cria a URL do frontend com os parâmetros necessários
            $verificationUrl = $frontendUrl . $path . 
                '?id=' . $usuario->id . 
                '&hash=' . sha1($usuario->getEmailForVerification()) .
                '&expires=' . $params['expires'] .
                '&signature=' . $params['signature'];

            // Envia o email de verificação usando o Mailable customizado
            Mail::to($usuario->email)->send(
                new EmailVerificationMail(
                    $verificationUrl,
                    $usuario->primeiro_nome,
                    60
                )
            );
            
            Log::info('Email de verificação enviado', [
                'usuario_id' => $usuario->id,
                'email' => $usuario->email
            ]);
        } catch (\Exception $e) {
            // Log do erro mas não interrompe o processo de registro
            Log::warning('Falha ao enviar email de verificação', [
                'usuario_id' => $usuario->id,
                'email' => $usuario->email,
                'erro' => $e->getMessage()
            ]);
        }
    }

    /**
     * Reenvia email de verificação para usuário autenticado
     * 
     * @return array
     * @throws \Exception
     */
    public function reenviarEmailVerificacao(): array
    {
        $usuario = JWTAuth::parseToken()->authenticate();

        // Verifica se o email já foi verificado
        if ($usuario->hasVerifiedEmail()) {
            throw new \Exception('Este email já foi verificado', 422);
        }

        // Envia novo email de verificação
        $this->enviarEmailVerificacao($usuario);

        return [
            'sucesso' => true,
            'mensagem' => 'Email de verificação reenviado para ' . $usuario->email
        ];
    }

    /**
     * Reenvia email de verificação para usuário não autenticado (via email público)
     * 
     * @param string $email Email do usuário
     * @return array
     * @throws \Exception
     */
    public function reenviarEmailVerificacaoPublico(string $email): array
    {
        $usuario = Usuario::where('email', $email)->first();

        if (!$usuario) {
            throw new \Exception('Usuário não encontrado', 404);
        }

        // Verifica se o email já foi verificado
        if ($usuario->hasVerifiedEmail()) {
            throw new \Exception('Este email já foi verificado', 422);
        }

        // Envia novo email de verificação
        $this->enviarEmailVerificacao($usuario);

        return [
            'sucesso' => true,
            'mensagem' => 'Email de verificação reenviado para ' . $usuario->email
        ];
    }

    /**
     * Verifica o email do usuário usando o hash fornecido
     * 
     * @param string $usuarioId ID do usuário
     * @param string $hash Hash de verificação
     * @return array
     * @throws \Exception
     */
    public function verificarEmail(string $usuarioId, string $hash): array
    {
        $usuario = Usuario::findOrFail($usuarioId);

        // Verifica se o email já foi verificado
        if ($usuario->hasVerifiedEmail()) {
            throw new \Exception('Este email já foi verificado', 422);
        }

        // Verifica se o hash é válido
        if (!hash_equals($hash, sha1($usuario->getEmailForVerification()))) {
            throw new \Exception('Link de verificação inválido', 422);
        }

        // Marca o email como verificado
        $usuario->markEmailAsVerified();

        return [
            'sucesso' => true,
            'mensagem' => 'Email verificado com sucesso!',
            'usuario' => [
                'email' => $usuario->email,
                'email_verificado' => true,
                'verificado_em' => $usuario->email_verified_at
            ]
        ];
    }

    /**
     * Envia email de recuperação de senha para o usuário
     * 
     * @param string $email Email do usuário
     * @return array
     * @throws \Exception
     */
    public function enviarEmailRecuperacaoSenha(string $email): array
    {
        $usuario = Usuario::where('email', $email)->first();

        if (!$usuario) {
            // Por segurança, não revelamos se o email existe ou não
            return [
                'sucesso' => true,
                'mensagem' => 'Se o cadastro existir em nosso sistema, você receberá um link de recuperação de senha no e-mail.'
            ];
        }

        // Verifica se o usuário está ativo
        if (!$usuario->ativo) {
            throw new \Exception('Usuário inativo', 403);
        }

        // Remove tokens antigos para este email
        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();

        // Gera um token único
        $token = Str::random(64);

        // Armazena o token no banco de dados
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // URL do frontend para redefinir senha baseada no tipo de usuário
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        
        // Define o caminho baseado no tipo de usuário
        if ($usuario->tipo_usuario === 'administrador') {
            // URL para administradores: /admin/redefinir-senha
            $path = '/admin/redefinir-senha';
        } else {
            // URL para clientes: /cliente/redefinir-senha
            $path = '/cliente/redefinir-senha';
        }
        
        $resetUrl = $frontendUrl . $path . '?token=' . $token . '&email=' . urlencode($email);

        try {
            // Envia o email de recuperação
            Mail::to($usuario->email)->send(
                new PasswordResetMail(
                    $resetUrl,
                    $usuario->primeiro_nome,
                    config('auth.passwords.usuarios.expire', 60)
                )
            );

            Log::info('Email de recuperação de senha enviado', [
                'usuario_id' => $usuario->id,
                'email' => $usuario->email
            ]);
        } catch (\Exception $e) {
            Log::error('Falha ao enviar email de recuperação de senha', [
                'usuario_id' => $usuario->id,
                'email' => $usuario->email,
                'erro' => $e->getMessage()
            ]);
            
            throw new \Exception('Falha ao enviar email de recuperação. Tente novamente mais tarde.', 500);
        }

        return [
            'sucesso' => true,
            'mensagem' => 'Se o email existir em nosso sistema, você receberá um link de recuperação de senha.'
        ];
    }

    /**
     * Redefine a senha do usuário usando o token de recuperação
     * 
     * @param string $email Email do usuário
     * @param string $token Token de recuperação
     * @param string $novaSenha Nova senha
     * @param string $confirmacaoSenha Confirmação da nova senha
     * @return array
     * @throws \Exception
     */
    public function redefinirSenha(string $email, string $token, string $novaSenha, string $confirmacaoSenha): array
    {
        // Verifica se as senhas coincidem
        if ($novaSenha !== $confirmacaoSenha) {
            throw new \Exception('As senhas não coincidem', 422);
        }

        // Busca o token no banco
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$resetRecord) {
            throw new \Exception('Token de recuperação inválido ou expirado', 422);
        }

        // Verifica se o token corresponde
        if (!Hash::check($token, $resetRecord->token)) {
            throw new \Exception('Token de recuperação inválido ou expirado', 422);
        }

        // Verifica se o token não expirou (padrão: 60 minutos)
        $expirationMinutes = config('auth.passwords.usuarios.expire', 60);
        $tokenCreatedAt = \Carbon\Carbon::parse($resetRecord->created_at);
        
        if ($tokenCreatedAt->addMinutes($expirationMinutes)->isPast()) {
            // Remove o token expirado
            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->delete();
                
            throw new \Exception('Token de recuperação expirado. Solicite um novo link de recuperação.', 422);
        }

        // Busca o usuário
        $usuario = Usuario::where('email', $email)->first();

        if (!$usuario) {
            throw new \Exception('Usuário não encontrado', 404);
        }

        // Atualiza a senha
        $usuario->senha = $novaSenha; // O mutator no model fará o hash automaticamente
        $usuario->save();

        // Remove o token usado
        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();

        // Invalida todos os tokens JWT existentes do usuário (opcional, mas recomendado)
        // Isso força o usuário a fazer login novamente
        
        Log::info('Senha redefinida com sucesso', [
            'usuario_id' => $usuario->id,
            'email' => $usuario->email
        ]);

        return [
            'sucesso' => true,
            'mensagem' => 'Senha redefinida com sucesso! Você já pode fazer login com sua nova senha.',
            'usuario' => [
                'email' => $usuario->email,
                'nome' => $usuario->primeiro_nome
            ]
        ];
    }

    /**
     * Retorna a URL de redirecionamento para autenticação com Google
     * 
     * @return string URL de redirecionamento
     */
    public function loginComGoogle(): string
    {
        /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
        $driver = \Laravel\Socialite\Facades\Socialite::driver('google');
        
        return $driver->stateless()
            ->redirect()
            ->getTargetUrl();
    }

    /**
     * Processa o callback do Google OAuth e autentica o usuário
     * 
     * @return array Dados do usuário com token JWT
     * @throws \Exception
     */
    public function callbackGoogle(): array
    {
        try {
            // Obtém os dados do usuário do Google
            /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
            $driver = \Laravel\Socialite\Facades\Socialite::driver('google');
            $googleUser = $driver->stateless()->user();

            Log::info('Callback Google recebido', [
                'google_id' => $googleUser->getId(),
                'email' => $googleUser->getEmail(),
            ]);

            // Verifica se já existe um usuário com este google_id (incluindo inativos)
            $usuario = Usuario::withoutGlobalScopes()->where('google_id', $googleUser->getId())->first();

            // Se não encontrou por google_id, tenta encontrar por email (incluindo inativos)
            if (!$usuario) {
                $usuario = Usuario::withoutGlobalScopes()->where('email', $googleUser->getEmail())->first();
                
                // Se encontrou um usuário com o email, vincula a conta Google
                if ($usuario) {
                    $usuario->google_id = $googleUser->getId();
                    $usuario->provider = 'google';
                    $usuario->avatar = $googleUser->getAvatar();
                    $usuario->email_verified_at = $usuario->email_verified_at ?? now();
                    
                    // Se o usuário estava inativo (deletado), reativa a conta
                    if (!$usuario->ativo) {
                        $usuario->ativo = true;
                        Log::info('Conta reativada via Google OAuth', [
                            'usuario_id' => $usuario->id,
                            'email' => $usuario->email,
                            'google_id' => $googleUser->getId()
                        ]);
                    }
                    
                    $usuario->save();
                    
                    Log::info('Conta Google vinculada a usuário existente', [
                        'usuario_id' => $usuario->id,
                        'email' => $usuario->email,
                        'foi_reativada' => !$usuario->getOriginal('ativo')
                    ]);
                }
            } else if ($usuario && !$usuario->ativo) {
                // Se encontrou por google_id mas está inativo, reativa a conta e atualiza informações
                $usuario->ativo = true;
                $usuario->avatar = $googleUser->getAvatar();
                $usuario->email_verified_at = $usuario->email_verified_at ?? now();
                
                // Atualiza nome se necessário (caso tenha mudado no Google)
                $googleName = $googleUser->getName();
                if (!empty($googleName)) {
                    $nameParts = explode(' ', trim($googleName), 2);
                    $primeiroNome = trim($nameParts[0]);
                    $segundoNome = isset($nameParts[1]) && !empty(trim($nameParts[1])) ? trim($nameParts[1]) : null;
                    
                    if (!empty($primeiroNome)) {
                        $usuario->primeiro_nome = $primeiroNome;
                        $usuario->segundo_nome = $segundoNome;
                    }
                }
                
                $usuario->save();
                
                Log::info('Conta existente reativada via Google OAuth', [
                    'usuario_id' => $usuario->id,
                    'email' => $usuario->email,
                    'google_id' => $googleUser->getId(),
                    'nome_atualizado' => !empty($primeiroNome)
                ]);
            }

            // Se ainda não encontrou, cria um novo usuário
            if (!$usuario) {
                // Extrai primeiro e último nome
                $googleName = $googleUser->getName();
                
                // Valida se o nome existe
                if (empty($googleName)) {
                    throw new \Exception('Nome do usuário não fornecido pelo Google', 400);
                }
                
                $nameParts = explode(' ', trim($googleName), 2);
                $primeiroNome = trim($nameParts[0]);
                $segundoNome = isset($nameParts[1]) && !empty(trim($nameParts[1])) ? trim($nameParts[1]) : null;
                
                // Garante que o primeiro nome não está vazio
                if (empty($primeiroNome)) {
                    throw new \Exception('Nome inválido fornecido pelo Google', 400);
                }

                // Determina o tipo de usuário baseado no domínio do email
                $email = $googleUser->getEmail();
                $tipoUsuario = 'usuario'; // Padrão para clientes
                
                // Se o email for @pharmedice.com.br, define como administrador
                if (str_ends_with(strtolower($email), '@pharmedice.com.br')) {
                    $tipoUsuario = 'administrador';
                }

                Log::info('Criando novo usuário via Google OAuth', [
                    'email' => $email,
                    'primeiro_nome' => $primeiroNome,
                    'segundo_nome' => $segundoNome,
                    'google_id' => $googleUser->getId(),
                    'tipo_usuario' => $tipoUsuario
                ]);

                $usuario = Usuario::create([
                    'primeiro_nome' => $primeiroNome,
                    'segundo_nome' => $segundoNome,
                    'apelido' => $primeiroNome,
                    'email' => $email,
                    'google_id' => $googleUser->getId(),
                    'provider' => 'google',
                    'avatar' => $googleUser->getAvatar(),
                    'tipo_usuario' => $tipoUsuario,
                    'email_verified_at' => now(), // Email verificado pelo Google
                    'ativo' => true,
                    'aceite_comunicacoes_email' => false,
                    'aceite_comunicacoes_sms' => false,
                    'aceite_comunicacoes_whatsapp' => false,
                ]);

                Log::info('Novo usuário criado via Google OAuth', [
                    'usuario_id' => $usuario->id,
                    'email' => $usuario->email,
                    'tipo_usuario' => $tipoUsuario
                ]);
            }

            // Gera o token JWT
            $token = JWTAuth::fromUser($usuario);

            return [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
                'usuario' => [
                    'id' => $usuario->id,
                    'primeiro_nome' => $usuario->primeiro_nome,
                    'segundo_nome' => $usuario->segundo_nome,
                    'email' => $usuario->email,
                    'tipo_usuario' => $usuario->tipo_usuario,
                    'is_admin' => $usuario->is_admin,
                    'email_verificado' => $usuario->hasVerifiedEmail(),
                    'avatar' => $usuario->avatar,
                ]
            ];

        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            Log::error('Erro de estado inválido no callback do Google', [
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Erro de autenticação com Google. Por favor, tente novamente.', 400);
        } catch (\Exception $e) {
            Log::error('Erro no callback do Google', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza o perfil do usuário autenticado
     * 
     * @param array $dados Dados para atualizar
     * @return array Dados atualizados do usuário
     * @throws \Exception
     */
    public function atualizarPerfil(array $dados): array
    {
        try {
            $usuario = JWTAuth::parseToken()->authenticate();

            if (!$usuario) {
                throw new \Exception('Usuário não encontrado', 404);
            }

            Log::info('Atualizando perfil do usuário', [
                'usuario_id' => $usuario->id,
                'email' => $usuario->email,
                'campos_alterados' => array_keys($dados)
            ]);

            // Remove campos que não devem ser alterados pelo próprio usuário
            $camposProibidos = ['tipo_usuario', 'ativo', 'email_verified_at', 'google_id', 'provider'];
            foreach ($camposProibidos as $campo) {
                if (isset($dados[$campo])) {
                    unset($dados[$campo]);
                    Log::warning('Campo proibido removido da atualização de perfil', [
                        'usuario_id' => $usuario->id,
                        'campo' => $campo
                    ]);
                }
            }

            // Se o email está sendo alterado, marca como não verificado
            if (isset($dados['email']) && $dados['email'] !== $usuario->email) {
                $dados['email_verified_at'] = null;
                Log::info('Email alterado, marcando como não verificado', [
                    'usuario_id' => $usuario->id,
                    'email_antigo' => $usuario->email,
                    'email_novo' => $dados['email']
                ]);
            }

            // Atualiza os dados
            $usuario->update($dados);
            $usuario->refresh();

            Log::info('Perfil atualizado com sucesso', [
                'usuario_id' => $usuario->id,
                'email' => $usuario->email
            ]);

            return [
                'id' => $usuario->id,
                'primeiro_nome' => $usuario->primeiro_nome,
                'segundo_nome' => $usuario->segundo_nome,
                'apelido' => $usuario->apelido,
                'email' => $usuario->email,
                'telefone' => $usuario->telefone,
                'numero_documento' => $usuario->numero_documento,
                'data_nascimento' => $usuario->data_nascimento?->format('Y-m-d'),
                'tipo_usuario' => $usuario->tipo_usuario,
                'is_admin' => $usuario->is_admin,
                'email_verificado' => $usuario->hasVerifiedEmail(),
                'avatar' => $usuario->avatar,
                'aceite_comunicacoes_email' => $usuario->aceite_comunicacoes_email,
                'aceite_comunicacoes_sms' => $usuario->aceite_comunicacoes_sms,
                'aceite_comunicacoes_whatsapp' => $usuario->aceite_comunicacoes_whatsapp,
                'ativo' => $usuario->ativo,
                'created_at' => $usuario->created_at,
                'updated_at' => $usuario->updated_at,
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar perfil', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
