<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LaudoController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// Rota raiz - status da API
Route::get('/', function () {
    return response()->json([
        'status' => 'online',
        'mensagem' => 'Pharmedice Customer Area API está online',
        'versao' => '1.0.0',
        'timestamp' => now()
    ]);
});

// Rotas públicas (sem autenticação)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('registrar-usuario', [AuthController::class, 'registrarUsuario']);
    Route::post('registrar-admin', [AuthController::class, 'registrarAdmin']);
    Route::post('reenviar-verificacao-email-publico', [AuthController::class, 'reenviarVerificacaoEmailPublico']);
    
    // Rotas de recuperação de senha
    Route::post('solicitar-recuperacao-senha', [AuthController::class, 'solicitarRecuperacaoSenha']);
    Route::post('redefinir-senha', [AuthController::class, 'redefinirSenha']);
    
    // Rota de verificação de email - pública com signed URL
    Route::post('verificar-email', [AuthController::class, 'verificarEmail'])
        ->name('verification.verify');
    
    // Rotas de autenticação com Google
    Route::get('google', [AuthController::class, 'loginComGoogle']);
    Route::get('google/callback', [AuthController::class, 'googleCallback']);
});

// Rota pública para consulta de laudos
Route::get('laudos/consultar/{id}', [LaudoController::class, 'consultarPublico']);

// Rotas que precisam de autenticação JWT
Route::middleware(['jwt.auth'])->group(function () {
    
    // Rotas de autenticação
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('reenviar-verificacao-email', [AuthController::class, 'reenviarVerificacaoEmail']);
    });

    // Rotas de usuários
    Route::prefix('usuarios')->group(function () {
        // Alterar própria senha (qualquer usuário autenticado)
        Route::put('alterar-senha', [UsuarioController::class, 'alterarSenha']);
        
        // Rotas que precisam de permissão de admin
        Route::middleware(['admin'])->group(function () {
            Route::get('/', [UsuarioController::class, 'index']);
            Route::post('/', [UsuarioController::class, 'store']);
            Route::get('{id}', [UsuarioController::class, 'show']);
            Route::put('{id}', [UsuarioController::class, 'update']);
            Route::delete('{id}', [UsuarioController::class, 'destroy']);
        });
    });

    // Rotas de laudos
    Route::prefix('laudos')->group(function () {
        // Rotas que qualquer usuário autenticado pode acessar
        Route::get('meus-laudos', [LaudoController::class, 'meusLaudos']);
        Route::get('buscar', [LaudoController::class, 'buscar']);
        Route::get('{id}/download', [LaudoController::class, 'download']);
        Route::get('{id}/visualizar', [LaudoController::class, 'visualizar']);
        
        // Rotas gerais de laudos (agora todos os usuários autenticados podem acessar)
        Route::get('/', [LaudoController::class, 'index']);
        Route::get('{id}', [LaudoController::class, 'show']);
        
        // Rotas que precisam de permissão de admin para gerenciar laudos
        Route::middleware(['admin'])->group(function () {
            Route::post('/', [LaudoController::class, 'store']);
            Route::put('{id}', [LaudoController::class, 'update']);
            Route::delete('{id}', [LaudoController::class, 'destroy']);
        });
    });
});