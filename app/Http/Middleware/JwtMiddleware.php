<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Usuário não encontrado'
                ], 404);
            }

            // Verifica se usuário está ativo
            if (!$user->ativo) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Usuário inativo'
                ], 403);
            }

        } catch (TokenExpiredException $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Token expirado'
            ], 401);

        } catch (TokenInvalidException $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Token inválido'
            ], 401);

        } catch (JWTException $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Token não fornecido'
            ], 401);
        }

        return $next($request);
    }
}