<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminMiddleware
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
            
            if (!$user || !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado. Apenas administradores podem acessar este recurso.'
                ], 403);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido ou expirado'
            ], 401);
        }

        return $next($request);
    }
}