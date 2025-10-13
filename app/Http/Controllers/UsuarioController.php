<?php

namespace App\Http\Controllers;

use App\DTOs\UsuarioDTO;
use App\Services\UsuarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class UsuarioController extends Controller
{
    public function __construct(
        private readonly UsuarioService $usuarioService
    ) {}

    public function index(Request $request): JsonResponse
    {
        Log::info('UsuarioController::index - Listagem de usuários solicitada', [
            'filters' => $request->only(['tipo_usuario', 'email', 'nome']),
            'per_page' => $request->get('per_page', 15)
        ]);

        try {
            $perPage = $request->get('per_page', 15);
            $filtros = $request->only(['tipo_usuario', 'email', 'nome']);

            $usuarios = $this->usuarioService->listar($perPage, $filtros);

            Log::info('UsuarioController::index - Usuários listados com sucesso', [
                'total_found' => $usuarios->total(),
                'current_page' => $usuarios->currentPage()
            ]);

            return response()->json([
                'sucesso' => true,
                'dados' => $usuarios
            ]);

        } catch (\Exception $e) {
            Log::error('UsuarioController::index - Erro ao listar usuários', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        Log::info('UsuarioController::show - Visualização de usuário solicitada', [
            'user_id' => $id
        ]);

        try {
            $usuario = $this->usuarioService->buscarPorId($id);

            Log::info('UsuarioController::show - Usuário visualizado com sucesso', [
                'user_id' => $id,
                'email' => $usuario->email,
                'apelido' => $usuario->apelido
            ]);

            return response()->json([
                'sucesso' => true,
                'dados' => $usuario
            ]);

        } catch (\Exception $e) {
            Log::error('UsuarioController::show - Erro ao visualizar usuário', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        Log::info('UsuarioController::store - Criação de usuário solicitada', [
            'email' => $request->input('email'),
            'apelido' => $request->input('apelido'),
            'tipo_usuario' => $request->input('tipo_usuario')
        ]);

        try {
            $validator = Validator::make($request->all(), [
                'primeiro_nome' => 'required|string|max:255',
                'segundo_nome' => 'required|string|max:255',
                'apelido' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:usuarios',
                'senha' => 'required|string|min:6',
                'telefone' => 'required|string|max:20',
                'numero_documento' => 'required|string|max:20|unique:usuarios',
                'data_nascimento' => 'required|date',
                'tipo_usuario' => 'in:administrador,usuario',
                'aceite_comunicacoes_email' => 'boolean',
                'aceite_comunicacoes_sms' => 'boolean',
                'aceite_comunicacoes_whatsapp' => 'boolean',
            ], [
                'primeiro_nome.required' => 'Primeiro nome é obrigatório',
                'segundo_nome.required' => 'Segundo nome é obrigatório',
                'apelido.required' => 'Apelido é obrigatório',
                'email.required' => 'Email é obrigatório',
                'email.email' => 'Email deve ter um formato válido',
                'email.unique' => 'Email já cadastrado',
                'senha.required' => 'Senha é obrigatória',
                'senha.min' => 'Senha deve ter no mínimo 6 caracteres',
                'telefone.required' => 'Telefone é obrigatório',
                'numero_documento.required' => 'Número do documento é obrigatório',
                'numero_documento.unique' => 'Número do documento já cadastrado',
                'data_nascimento.required' => 'Data de nascimento é obrigatória',
                'data_nascimento.date' => 'Data de nascimento deve ser uma data válida',
            ]);

            if ($validator->fails()) {
                Log::warning('UsuarioController::store - Dados de validação inválidos', [
                    'email' => $request->input('email'),
                    'apelido' => $request->input('apelido'),
                    'errors' => $validator->errors()
                ]);

                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Dados inválidos',
                    'erros' => $validator->errors()
                ], 422);
            }

            $usuarioDTO = UsuarioDTO::fromRequest($request->all());
            $usuario = $this->usuarioService->criar($usuarioDTO);

            Log::info('UsuarioController::store - Usuário criado com sucesso', [
                'user_id' => $usuario->id,
                'email' => $usuario->email,
                'apelido' => $usuario->apelido,
                'tipo_usuario' => $usuario->tipo_usuario
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Usuário criado com sucesso',
                'dados' => $usuario
            ], 201);

        } catch (\Exception $e) {
            Log::error('UsuarioController::store - Erro ao criar usuário', [
                'email' => $request->input('email'),
                'apelido' => $request->input('apelido'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        Log::info('UsuarioController::update - Atualização de usuário solicitada', [
            'user_id' => $id,
            'email' => $request->input('email'),
            'apelido' => $request->input('apelido')
        ]);

        try {
            $rules = [
                'primeiro_nome' => 'sometimes|required|string|max:255',
                'segundo_nome' => 'sometimes|required|string|max:255',
                'apelido' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|max:255|unique:usuarios,email,' . $id,
                'telefone' => 'sometimes|required|string|max:20',
                'numero_documento' => 'sometimes|required|string|max:20|unique:usuarios,numero_documento,' . $id,
                'data_nascimento' => 'sometimes|required|date',
                'tipo_usuario' => 'sometimes|in:administrador,usuario',
                'aceite_comunicacoes_email' => 'sometimes|boolean',
                'aceite_comunicacoes_sms' => 'sometimes|boolean',
                'aceite_comunicacoes_whatsapp' => 'sometimes|boolean',
            ];

            // Senha é opcional na atualização
            if ($request->has('senha')) {
                $rules['senha'] = 'string|min:6';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                Log::warning('UsuarioController::update - Dados de validação inválidos', [
                    'user_id' => $id,
                    'errors' => $validator->errors()
                ]);

                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Dados inválidos',
                    'erros' => $validator->errors()
                ], 422);
            }

            $usuarioDTO = UsuarioDTO::fromRequest($request->all());
            $usuario = $this->usuarioService->atualizar($id, $usuarioDTO);

            Log::info('UsuarioController::update - Usuário atualizado com sucesso', [
                'user_id' => $id,
                'email' => $usuario->email,
                'apelido' => $usuario->apelido
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Usuário atualizado com sucesso',
                'dados' => $usuario
            ]);

        } catch (\Exception $e) {
            Log::error('UsuarioController::update - Erro ao atualizar usuário', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        Log::info('UsuarioController::destroy - Remoção de usuário solicitada', [
            'user_id' => $id
        ]);

        try {
            $this->usuarioService->deletar($id);

            Log::info('UsuarioController::destroy - Usuário removido com sucesso', [
                'user_id' => $id
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Usuário removido com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('UsuarioController::destroy - Erro ao remover usuário', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function alterarSenha(Request $request): JsonResponse
    {
        Log::info('UsuarioController::alterarSenha - Alteração de senha solicitada');

        try {
            $validator = Validator::make($request->all(), [
                'senha_atual' => 'required|string',
                'nova_senha' => 'required|string|min:6|confirmed',
            ], [
                'senha_atual.required' => 'Senha atual é obrigatória',
                'nova_senha.required' => 'Nova senha é obrigatória',
                'nova_senha.min' => 'Nova senha deve ter no mínimo 6 caracteres',
                'nova_senha.confirmed' => 'Confirmação da senha não confere',
            ]);

            if ($validator->fails()) {
                Log::warning('UsuarioController::alterarSenha - Dados de validação inválidos', [
                    'errors' => $validator->errors()
                ]);

                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Dados inválidos',
                    'erros' => $validator->errors()
                ], 422);
            }

            $usuario = JWTAuth::parseToken()->authenticate();
            
            // Verifica senha atual
            if (!password_verify($request->senha_atual, $usuario->senha)) {
                Log::warning('UsuarioController::alterarSenha - Senha atual incorreta', [
                    'user_id' => $usuario->id
                ]);

                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Senha atual incorreta'
                ], 400);
            }

            $this->usuarioService->alterarSenha($usuario->id, $request->nova_senha);

            Log::info('UsuarioController::alterarSenha - Senha alterada com sucesso', [
                'user_id' => $usuario->id
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Senha alterada com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('UsuarioController::alterarSenha - Erro ao alterar senha', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }
}