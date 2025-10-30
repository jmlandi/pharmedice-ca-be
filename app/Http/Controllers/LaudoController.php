<?php

namespace App\Http\Controllers;

use App\DTOs\LaudoDTO;
use App\Services\LaudoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class LaudoController extends Controller
{
    public function __construct(
        private readonly LaudoService $laudoService
    ) {}

    public function index(Request $request): JsonResponse
    {
        Log::info('LaudoController::index - Listagem de laudos solicitada', [
            'filters' => $request->only(['titulo', 'nome_arquivo', 'busca', 'data_inicio', 'data_fim']),
            'per_page' => $request->get('per_page', 15)
        ]);

        try {
            $usuario = JWTAuth::parseToken()->authenticate();
            $perPage = $request->get('per_page', 15);
            $filtros = $request->only(['titulo', 'nome_arquivo', 'busca', 'data_inicio', 'data_fim']);

            // Qualquer usuário autenticado pode ver todos os laudos
            $laudos = $this->laudoService->listar($perPage, $filtros);

            Log::info('LaudoController::index - Laudos listados com sucesso', [
                'user_id' => $usuario->id,
                'total_found' => $laudos->total(),
                'current_page' => $laudos->currentPage()
            ]);

            return response()->json([
                'sucesso' => true,
                'dados' => $laudos
            ]);

        } catch (\Exception $e) {
            Log::error('LaudoController::index - Erro ao listar laudos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        Log::info('LaudoController::show - Visualização de laudo solicitada', [
            'laudo_id' => $id
        ]);

        try {
            $usuario = JWTAuth::parseToken()->authenticate();
            // Qualquer usuário autenticado pode ver qualquer laudo
            $laudo = $this->laudoService->buscarPorId($id);

            Log::info('LaudoController::show - Laudo visualizado com sucesso', [
                'laudo_id' => $id,
                'user_id' => $usuario->id,
                'laudo_title' => $laudo->titulo
            ]);

            return response()->json([
                'sucesso' => true,
                'dados' => $laudo
            ]);

        } catch (\Exception $e) {
            Log::error('LaudoController::show - Erro ao visualizar laudo', [
                'laudo_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        Log::info('LaudoController::store - Criação de laudo solicitada', [
            'titulo' => $request->input('titulo'),
            'has_file' => $request->hasFile('arquivo')
        ]);

        try {
            $validator = Validator::make($request->all(), [
                'titulo' => 'required|string|max:255',
                'descricao' => 'nullable|string',
                'arquivo' => 'required|file|mimes:pdf|max:10240', // 10MB max
            ], [
                'titulo.required' => 'Título é obrigatório',
                'titulo.max' => 'Título deve ter no máximo 255 caracteres',
                'arquivo.required' => 'Arquivo PDF é obrigatório',
                'arquivo.file' => 'Deve ser um arquivo válido',
                'arquivo.mimes' => 'Arquivo deve ser um PDF',
                'arquivo.max' => 'Arquivo deve ter no máximo 10MB',
            ]);

            if ($validator->fails()) {
                Log::warning('LaudoController::store - Dados de validação inválidos', [
                    'titulo' => $request->input('titulo'),
                    'errors' => $validator->errors()
                ]);

                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Dados inválidos',
                    'erros' => $validator->errors()
                ], 422);
            }

            $usuario = JWTAuth::parseToken()->authenticate();

            $laudoDTO = LaudoDTO::fromRequest($request->all());
            $laudo = $this->laudoService->criar($laudoDTO);

            Log::info('LaudoController::store - Laudo criado com sucesso', [
                'laudo_id' => $laudo->id,
                'titulo' => $laudo->titulo,
                'created_by' => $usuario->id
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Laudo criado com sucesso',
                'dados' => $laudo
            ], 201);

        } catch (\Exception $e) {
            Log::error('LaudoController::store - Erro ao criar laudo', [
                'titulo' => $request->input('titulo'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        Log::info('LaudoController::update - Atualização de laudo solicitada', [
            'laudo_id' => $id,
            'titulo' => $request->input('titulo'),
            'has_file' => $request->hasFile('arquivo')
        ]);

        try {
            $rules = [
                'titulo' => 'required|string|max:255',
                'descricao' => 'nullable|string',
            ];

            // Arquivo é opcional na atualização
            if ($request->hasFile('arquivo')) {
                $rules['arquivo'] = 'file|mimes:pdf|max:10240'; // 10MB max
            }

            $validator = Validator::make($request->all(), $rules, [
                'titulo.required' => 'Título é obrigatório',
                'titulo.max' => 'Título deve ter no máximo 255 caracteres',
                'arquivo.file' => 'Deve ser um arquivo válido',
                'arquivo.mimes' => 'Arquivo deve ser um PDF',
                'arquivo.max' => 'Arquivo deve ter no máximo 10MB',
            ]);

            if ($validator->fails()) {
                Log::warning('LaudoController::update - Dados de validação inválidos', [
                    'laudo_id' => $id,
                    'errors' => $validator->errors()
                ]);

                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Dados inválidos',
                    'erros' => $validator->errors()
                ], 422);
            }

            $usuario = JWTAuth::parseToken()->authenticate();
            
            // Verifica se o laudo existe
            $laudoExistente = $this->laudoService->buscarPorId($id);

            $laudoDTO = LaudoDTO::fromRequest($request->all());
            $laudo = $this->laudoService->atualizar($id, $laudoDTO);

            Log::info('LaudoController::update - Laudo atualizado com sucesso', [
                'laudo_id' => $id,
                'titulo' => $laudo->titulo,
                'updated_by' => $usuario->id
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Laudo atualizado com sucesso',
                'dados' => $laudo
            ]);

        } catch (\Exception $e) {
            Log::error('LaudoController::update - Erro ao atualizar laudo', [
                'laudo_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Se for erro de banco de dados, usa status code 500
            $statusCode = 500;
            if ($e->getCode() && $e->getCode() >= 400 && $e->getCode() < 600) {
                $statusCode = $e->getCode();
            }
            
            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        Log::info('LaudoController::destroy - Remoção de laudo solicitada', [
            'laudo_id' => $id
        ]);

        try {
            $usuario = JWTAuth::parseToken()->authenticate();
            $this->laudoService->deletar($id);

            Log::info('LaudoController::destroy - Laudo removido com sucesso', [
                'laudo_id' => $id,
                'deleted_by' => $usuario->id
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Laudo removido com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('LaudoController::destroy - Erro ao remover laudo', [
                'laudo_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], 500);
        }
    }

    public function consultarPublico(string $id): JsonResponse
    {
        Log::info('LaudoController::consultarPublico - Consulta pública de laudo solicitada', [
            'laudo_id' => $id
        ]);

        try {
            $laudo = $this->laudoService->buscarPorId($id);
            
            Log::info('LaudoController::consultarPublico - Consulta pública realizada com sucesso', [
                'laudo_id' => $id,
                'titulo' => $laudo->titulo
            ]);

            return response()->json([
                'sucesso' => true,
                'dados' => [
                    'id' => $laudo->id,
                    'titulo' => $laudo->titulo,
                    'descricao' => $laudo->descricao,
                    'nome_arquivo' => $laudo->nome_arquivo,
                    'created_at' => $laudo->created_at,
                    'updated_at' => $laudo->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('LaudoController::consultarPublico - Erro na consulta pública', [
                'laudo_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], 500);
        }
    }

    public function download(string $id)
    {
        Log::info('LaudoController::download - Download de laudo solicitado', [
            'laudo_id' => $id
        ]);

        try {
            $usuario = JWTAuth::parseToken()->authenticate();
            // Qualquer usuário autenticado pode fazer download de qualquer laudo
            $downloadInfo = $this->laudoService->downloadLaudo($id);

            Log::info('LaudoController::download - Download realizado com sucesso', [
                'laudo_id' => $id,
                'user_id' => $usuario->id,
                'filename' => $downloadInfo['nome_arquivo'] ?? null
            ]);

            // Stream do arquivo direto do S3 com headers de download
            $laudo = $this->laudoService->buscarPorId($id);
            $fileContents = Storage::disk('s3')->get($laudo->url_arquivo);
            
            return response($fileContents, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $downloadInfo['nome_arquivo_original'] . '"')
                ->header('Content-Length', strlen($fileContents));

        } catch (\Exception $e) {
            Log::error('LaudoController::download - Erro ao realizar download', [
                'laudo_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], 500);
        }
    }

    public function visualizar(string $id)
    {
        Log::info('LaudoController::visualizar - Visualização de laudo solicitada', [
            'laudo_id' => $id
        ]);

        try {
            $usuario = JWTAuth::parseToken()->authenticate();
            // Qualquer usuário autenticado pode visualizar qualquer laudo
            $laudo = $this->laudoService->buscarPorId($id);

            if (!$laudo->url_arquivo || !Storage::disk('s3')->exists($laudo->url_arquivo)) {
                throw new \Exception('Arquivo não encontrado', 404);
            }

            Log::info('LaudoController::visualizar - Visualização realizada com sucesso', [
                'laudo_id' => $id,
                'user_id' => $usuario->id
            ]);

            // Stream do arquivo direto do S3 com headers para visualização inline
            $fileContents = Storage::disk('s3')->get($laudo->url_arquivo);
            
            return response($fileContents, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $laudo->nome_arquivo_original . '"')
                ->header('Content-Length', strlen($fileContents));

        } catch (\Exception $e) {
            Log::error('LaudoController::visualizar - Erro ao visualizar', [
                'laudo_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], 500);
        }
    }

    public function meusLaudos(Request $request): JsonResponse
    {
        // DEPRECATED: Este endpoint retorna todos os laudos já que laudos não são mais associados a usuários específicos
        Log::info('LaudoController::meusLaudos - DEPRECATED - Redirecionando para listagem geral', [
            'filters' => $request->only(['titulo', 'data_inicio', 'data_fim']),
            'per_page' => $request->get('per_page', 15)
        ]);

        // Redireciona para o método index (listagem geral)
        return $this->index($request);
    }

    public function buscar(Request $request): JsonResponse
    {
        Log::info('LaudoController::buscar - Busca de laudos solicitada', [
            'search_term' => $request->get('busca'),
            'filters' => $request->only(['data_inicio', 'data_fim']),
            'per_page' => $request->get('per_page', 15)
        ]);

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
                Log::warning('LaudoController::buscar - Dados de validação inválidos', [
                    'search_term' => $request->get('busca'),
                    'errors' => $validator->errors()
                ]);

                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Dados inválidos',
                    'erros' => $validator->errors()
                ], 422);
            }

            $filtros = [
                'busca' => $request->get('busca'),
            ];

            // Adicionar filtros opcionais (removido usuario_id)
            $filtrosOpcionais = $request->only(['data_inicio', 'data_fim']);
            $filtros = array_merge($filtros, array_filter($filtrosOpcionais));

            $laudos = $this->laudoService->listar($perPage, $filtros);

            Log::info('LaudoController::buscar - Busca realizada com sucesso', [
                'search_term' => $request->get('busca'),
                'user_id' => $usuario->id,
                'total_found' => $laudos->total(),
                'current_page' => $laudos->currentPage()
            ]);

            return response()->json([
                'sucesso' => true,
                'dados' => $laudos,
                'meta' => [
                    'termo_busca' => $request->get('busca'),
                    'total_encontrado' => $laudos->total()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('LaudoController::buscar - Erro durante busca', [
                'search_term' => $request->get('busca'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], 500);
        }
    }
}