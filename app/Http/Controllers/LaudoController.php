<?php

namespace App\Http\Controllers;

use App\DTOs\LaudoDTO;
use App\Services\LaudoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class LaudoController extends Controller
{
    public function __construct(
        private readonly LaudoService $laudoService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $usuario = JWTAuth::parseToken()->authenticate();
            $perPage = $request->get('per_page', 15);
            $filtros = $request->only(['usuario_id', 'titulo', 'nome_arquivo', 'busca', 'data_inicio', 'data_fim']);

            // Qualquer usuário autenticado pode ver todos os laudos
            $laudos = $this->laudoService->listar($perPage, $filtros, $usuario->id);

            return response()->json([
                'success' => true,
                'data' => $laudos
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
            $usuario = JWTAuth::parseToken()->authenticate();
            // Qualquer usuário autenticado pode ver qualquer laudo
            $laudo = $this->laudoService->buscarPorId($id, $usuario->id);

            return response()->json([
                'success' => true,
                'data' => $laudo
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
                'usuario_id' => 'nullable|exists:usuarios,id', // ID do usuário criador (opcional)
                'titulo' => 'required|string|max:255',
                'descricao' => 'nullable|string',
                'arquivo' => 'required|file|mimes:pdf|max:10240', // 10MB max
            ], [
                'usuario_id.exists' => 'Usuário criador não encontrado',
                'titulo.required' => 'Título é obrigatório',
                'titulo.max' => 'Título deve ter no máximo 255 caracteres',
                'arquivo.required' => 'Arquivo PDF é obrigatório',
                'arquivo.file' => 'Deve ser um arquivo válido',
                'arquivo.mimes' => 'Arquivo deve ser um PDF',
                'arquivo.max' => 'Arquivo deve ter no máximo 10MB',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $usuario = JWTAuth::parseToken()->authenticate();
            
            // Se não é admin e está tentando criar como outro usuário
            if (!$usuario->isAdmin() && $request->usuario_id && $request->usuario_id != $usuario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você só pode criar laudos em seu próprio nome'
                ], 403);
            }

            // Se não foi especificado usuário criador, usa o usuário logado como criador
            if (!$request->usuario_id) {
                $request->merge(['usuario_id' => $usuario->id]);
            }

            $laudoDTO = LaudoDTO::fromRequest($request->all());
            $laudo = $this->laudoService->criar($laudoDTO);

            return response()->json([
                'success' => true,
                'message' => 'Laudo criado com sucesso',
                'data' => $laudo
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
                'usuario_id' => 'nullable|exists:usuarios,id',
                'titulo' => 'required|string|max:255',
                'descricao' => 'nullable|string',
            ];

            // Arquivo é opcional na atualização
            if ($request->hasFile('arquivo')) {
                $rules['arquivo'] = 'file|mimes:pdf|max:10240'; // 10MB max
            }

            $validator = Validator::make($request->all(), $rules, [
                'usuario_id.exists' => 'Usuário não encontrado',
                'titulo.required' => 'Título é obrigatório',
                'titulo.max' => 'Título deve ter no máximo 255 caracteres',
                'arquivo.file' => 'Deve ser um arquivo válido',
                'arquivo.mimes' => 'Arquivo deve ser um PDF',
                'arquivo.max' => 'Arquivo deve ter no máximo 10MB',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $usuario = JWTAuth::parseToken()->authenticate();
            
            // Verifica se o laudo existe e se o usuário tem permissão
            $laudoExistente = $this->laudoService->buscarPorId($id, $usuario->id);
            
            // Se não é admin e está tentando alterar para outro usuário
            if (!$usuario->isAdmin() && $request->usuario_id && $request->usuario_id != $usuario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você só pode atualizar laudos próprios'
                ], 403);
            }

            $laudoDTO = LaudoDTO::fromRequest($request->all());
            $laudo = $this->laudoService->atualizar($id, $laudoDTO, $usuario->id);

            return response()->json([
                'success' => true,
                'message' => 'Laudo atualizado com sucesso',
                'data' => $laudo
            ]);

        } catch (\Exception $e) {
            // Se for erro de banco de dados, usa status code 500
            $statusCode = 500;
            if ($e->getCode() && $e->getCode() >= 400 && $e->getCode() < 600) {
                $statusCode = $e->getCode();
            }
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $usuario = JWTAuth::parseToken()->authenticate();
            $this->laudoService->deletar($id, $usuario->id);

            return response()->json([
                'success' => true,
                'message' => 'Laudo removido com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function consultarPublico(string $id): JsonResponse
    {
        try {
            $laudo = $this->laudoService->buscarPorId($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $laudo->id,
                    'titulo' => $laudo->titulo,
                    'descricao' => $laudo->descricao,
                    'nome_arquivo' => $laudo->nome_arquivo,
                    'created_at' => $laudo->created_at,
                    'updated_at' => $laudo->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function download(string $id): JsonResponse
    {
        try {
            $usuario = JWTAuth::parseToken()->authenticate();
            // Qualquer usuário autenticado pode fazer download de qualquer laudo
            $downloadInfo = $this->laudoService->downloadLaudo($id, $usuario->id);

            return response()->json([
                'success' => true,
                'data' => $downloadInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function meusLaudos(Request $request): JsonResponse
    {
        try {
            $usuario = JWTAuth::parseToken()->authenticate();
            $perPage = $request->get('per_page', 15);
            $filtros = $request->only(['titulo', 'data_inicio', 'data_fim']);
            
            // Força o filtro pelo usuário atual
            $filtros['usuario_id'] = $usuario->id;

            $laudos = $this->laudoService->listar($perPage, $filtros, $usuario->id);

            return response()->json([
                'success' => true,
                'data' => $laudos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function buscar(Request $request): JsonResponse
    {
        try {
            $usuario = JWTAuth::parseToken()->authenticate();
            $perPage = $request->get('per_page', 15);
            
            $validator = Validator::make($request->all(), [
                'busca' => 'required|string|min:2',
            ], [
                'busca.required' => 'Termo de busca é obrigatório',
                'busca.min' => 'Termo de busca deve ter no mínimo 2 caracteres',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filtros = [
                'busca' => $request->get('busca'),
            ];

            // Adicionar filtros opcionais
            $filtrosOpcionais = $request->only(['usuario_id', 'data_inicio', 'data_fim']);
            $filtros = array_merge($filtros, array_filter($filtrosOpcionais));

            $laudos = $this->laudoService->listar($perPage, $filtros, $usuario->id);

            return response()->json([
                'success' => true,
                'data' => $laudos,
                'meta' => [
                    'termo_busca' => $request->get('busca'),
                    'total_encontrado' => $laudos->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }
}