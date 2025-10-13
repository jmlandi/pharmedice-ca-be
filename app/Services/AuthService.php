<?php

namespace App\Services;

use App\DTOs\LoginDTO;
use App\DTOs\SignupDTO;
use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
            'data_nascimento' => $usuario->data_nascimento->format('Y-m-d'),
            'tipo_usuario' => $usuario->tipo_usuario,
            'is_admin' => $usuario->is_admin,
            'aceite_comunicacoes_email' => $usuario->aceite_comunicacoes_email,
            'aceite_comunicacoes_sms' => $usuario->aceite_comunicacoes_sms,
            'aceite_comunicacoes_whatsapp' => $usuario->aceite_comunicacoes_whatsapp,
            'ativo' => $usuario->ativo,
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

        // Verifica se o email já está em uso
        $usuarioExistente = Usuario::where('email', $dadosRegistro->email)->first();
        if ($usuarioExistente) {
            throw new \Exception('Este email já está sendo utilizado', 409);
        }

        // Verifica se o número de documento já está em uso
        $documentoExistente = Usuario::where('numero_documento', $dadosRegistro->numero_documento)->first();
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
            // Envia o email de verificação usando o sistema nativo do Laravel
            $usuario->sendEmailVerificationNotification();
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
            'success' => true,
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
            'success' => true,
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
            'success' => true,
            'mensagem' => 'Email verificado com sucesso!',
            'usuario' => [
                'email' => $usuario->email,
                'email_verificado' => true,
                'verificado_em' => $usuario->email_verified_at
            ]
        ];
    }
}