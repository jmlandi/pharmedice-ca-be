<?php

namespace App\Services;

use App\DTOs\LoginDTO;
use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function login(LoginDTO $loginDTO): array
    {
        $credentials = [
            'email' => $loginDTO->email,
            'password' => $loginDTO->senha
        ];

        // Tenta fazer login usando as credenciais
        $usuario = Usuario::where('email', $loginDTO->email)
            ->where('ativo', true)
            ->first();

        if (!$usuario || !Hash::check($loginDTO->senha, $usuario->senha)) {
            throw new \Exception('Credenciais inválidas', 401);
        }

        // Gera o token JWT
        $token = JWTAuth::fromUser($usuario);

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'usuario' => [
                'id' => $usuario->id,
                'nome_completo' => $usuario->nome_completo,
                'email' => $usuario->email,
                'tipo_usuario' => $usuario->tipo_usuario,
                'is_admin' => $usuario->is_admin,
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
}