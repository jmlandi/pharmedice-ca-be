<?php

namespace App\Http\Controllers;

use App\DTOs\UsuarioDTO;
use App\Services\UsuarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class UsuarioController extends Controller
{
    public function __construct(
        private readonly UsuarioService $usuarioService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filtros = $request->only(['tipo_usuario', 'email', 'nome']);

            $usuarios = $this->usuarioService->listar($perPage, $filtros);

            return response()->json([
                'success' => true,
                'data' => $usuarios
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $usuario = $this->usuarioService->buscarPorId($id);

            return response()->json([
                'success' => true,
                'data' => $usuario
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
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
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $usuarioDTO = UsuarioDTO::fromRequest($request->all());
            $usuario = $this->usuarioService->criar($usuarioDTO);

            return response()->json([
                'success' => true,
                'message' => 'Usuário criado com sucesso',
                'data' => $usuario
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
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
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $usuarioDTO = UsuarioDTO::fromRequest($request->all());
            $usuario = $this->usuarioService->atualizar($id, $usuarioDTO);

            return response()->json([
                'success' => true,
                'message' => 'Usuário atualizado com sucesso',
                'data' => $usuario
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->usuarioService->deletar($id);

            return response()->json([
                'success' => true,
                'message' => 'Usuário removido com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function alterarSenha(Request $request): JsonResponse
    {
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
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $usuario = JWTAuth::parseToken()->authenticate();
            
            // Verifica senha atual
            if (!password_verify($request->senha_atual, $usuario->senha)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Senha atual incorreta'
                ], 400);
            }

            $this->usuarioService->alterarSenha($usuario->id, $request->nova_senha);

            return response()->json([
                'success' => true,
                'message' => 'Senha alterada com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }
}