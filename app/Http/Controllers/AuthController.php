<?php

namespace App\Http\Controllers;

use App\DTOs\LoginDTO;
use App\DTOs\SignupDTO;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function login(Request $request): JsonResponse
    {
        Log::info('AuthController::login - Tentativa de login', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'senha' => 'required|string|min:6',
            ], [
                'email.required' => 'Email é obrigatório',
                'email.email' => 'Email deve ter um formato válido',
                'senha.required' => 'Senha é obrigatória',
                'senha.min' => 'Senha deve ter no mínimo 6 caracteres',
            ]);

            if ($validator->fails()) {
                Log::warning('AuthController::login - Dados de validação inválidos', [
                    'email' => $request->input('email'),
                    'errors' => $validator->errors()
                ]);
                
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Dados inválidos',
                    'erros' => $validator->errors()
                ], 422);
            }

            $loginDTO = LoginDTO::fromRequest($request->all());
            $result = $this->authService->login($loginDTO);

            Log::info('AuthController::login - Login realizado com sucesso', [
                'email' => $request->input('email'),
                'user_id' => $result['usuario']['id'] ?? null
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Login realizado com sucesso',
                'dados' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('AuthController::login - Erro durante login', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Registra um novo usuário normal no sistema
     * 
     * @param Request $request Dados do formulário de registro
     * @return JsonResponse
     */
    public function registrarUsuario(Request $request): JsonResponse
    {
        Log::info('AuthController::registrarUsuario - Tentativa de registro de usuário', [
            'email' => $request->input('email'),
            'apelido' => $request->input('apelido'),
            'ip' => $request->ip()
        ]);

        try {
            // Regras de validação para registro de usuário
            $regras_validacao = [
                'primeiro_nome' => 'required|string|min:2|max:50|regex:/^[A-Za-zÀ-ÿ\s]+$/',
                'segundo_nome' => 'required|string|min:2|max:50|regex:/^[A-Za-zÀ-ÿ\s]+$/',
                'apelido' => 'required|string|min:3|max:30|alpha_num',
                'email' => 'required|email|max:255|unique:usuarios,email',
                'senha' => 'required|string|min:8|max:50|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                'senha_confirmation' => 'required|string',
                'confirmacao_senha' => 'required|string|same:senha',
                'telefone' => 'required|string|regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/',
                'numero_documento' => 'required|string|digits:11|unique:usuarios,numero_documento',
                'data_nascimento' => 'required|date|before:today|after:1900-01-01',
                'aceite_comunicacoes_email' => 'sometimes|boolean',
                'aceite_comunicacoes_sms' => 'sometimes|boolean',
                'aceite_comunicacoes_whatsapp' => 'sometimes|boolean',
                'aceite_termos_uso' => 'required|accepted',
                'aceite_politica_privacidade' => 'required|accepted',
            ];

            // Mensagens de erro personalizadas em português
            $mensagens_erro = [
                'primeiro_nome.required' => 'O primeiro nome é obrigatório',
                'primeiro_nome.min' => 'O primeiro nome deve ter pelo menos 2 caracteres',
                'primeiro_nome.max' => 'O primeiro nome deve ter no máximo 50 caracteres',
                'primeiro_nome.regex' => 'O primeiro nome deve conter apenas letras e espaços',
                
                'segundo_nome.required' => 'O segundo nome é obrigatório',
                'segundo_nome.min' => 'O segundo nome deve ter pelo menos 2 caracteres',
                'segundo_nome.max' => 'O segundo nome deve ter no máximo 50 caracteres',
                'segundo_nome.regex' => 'O segundo nome deve conter apenas letras e espaços',
                
                'apelido.required' => 'O apelido é obrigatório',
                'apelido.min' => 'O apelido deve ter pelo menos 3 caracteres',
                'apelido.max' => 'O apelido deve ter no máximo 30 caracteres',
                'apelido.alpha_num' => 'O apelido deve conter apenas letras e números',
                
                'email.required' => 'O email é obrigatório',
                'email.email' => 'O email deve ter um formato válido',
                'email.max' => 'O email deve ter no máximo 255 caracteres',
                'email.unique' => 'Este email já está sendo utilizado',
                
                'senha.required' => 'A senha é obrigatória',
                'senha.min' => 'A senha deve ter pelo menos 8 caracteres',
                'senha.max' => 'A senha deve ter no máximo 50 caracteres',
                'senha.confirmed' => 'A confirmação da senha não confere',
                'senha.regex' => 'A senha deve conter pelo menos: 1 letra minúscula, 1 maiúscula, 1 número e 1 caractere especial (@$!%*?&)',
                
                'confirmacao_senha.required' => 'A confirmação da senha é obrigatória',
                'confirmacao_senha.same' => 'A confirmação da senha deve ser igual à senha',
                
                'telefone.required' => 'O telefone é obrigatório',
                'telefone.regex' => 'O telefone deve estar no formato (XX) XXXXX-XXXX',
                
                'numero_documento.required' => 'O CPF é obrigatório',
                'numero_documento.digits' => 'O CPF deve conter exatamente 11 dígitos',
                'numero_documento.unique' => 'Este CPF já está sendo utilizado',
                
                'data_nascimento.required' => 'A data de nascimento é obrigatória',
                'data_nascimento.date' => 'A data de nascimento deve ser uma data válida',
                'data_nascimento.before' => 'A data de nascimento deve ser anterior a hoje',
                'data_nascimento.after' => 'A data de nascimento deve ser posterior a 1900',
                
                'aceite_termos_uso.required' => 'É obrigatório aceitar os termos de uso',
                'aceite_termos_uso.accepted' => 'É obrigatório aceitar os termos de uso',
                
                'aceite_politica_privacidade.required' => 'É obrigatório aceitar a política de privacidade',
                'aceite_politica_privacidade.accepted' => 'É obrigatório aceitar a política de privacidade',
            ];

            // Executa a validação
            $validador = Validator::make($request->all(), $regras_validacao, $mensagens_erro);

            if ($validador->fails()) {
                Log::warning('AuthController::registrarUsuario - Dados de validação inválidos', [
                    'email' => $request->input('email'),
                    'apelido' => $request->input('apelido'),
                    'errors' => $validador->errors()
                ]);

                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Dados inválidos fornecidos',
                    'erros' => $validador->errors()
                ], 422);
            }

            // Cria o DTO com os dados validados e define como usuário normal
            $dadosRegistro = SignupDTO::fromRequest(array_merge($request->all(), ['tipo_usuario' => 'usuario']));
            
            // Chama o serviço para registrar o usuário
            $resultado = $this->authService->registrarUsuario($dadosRegistro);

            Log::info('AuthController::registrarUsuario - Usuário registrado com sucesso', [
                'email' => $request->input('email'),
                'apelido' => $request->input('apelido'),
                'user_id' => $resultado['usuario']['id'] ?? null
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Usuário registrado com sucesso! Verifique seu email para ativar a conta.',
                'dados' => $resultado
            ], 201);

        } catch (\Exception $e) {
            Log::error('AuthController::registrarUsuario - Erro durante registro', [
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

    /**
     * Registra um novo administrador no sistema
     * 
     * @param Request $request Dados do formulário de registro
     * @return JsonResponse
     */
    public function registrarAdmin(Request $request): JsonResponse
    {
        Log::info('AuthController::registrarAdmin - Tentativa de registro de administrador', [
            'email' => $request->input('email'),
            'apelido' => $request->input('apelido'),
            'ip' => $request->ip()
        ]);

        try {
            // Regras de validação para registro de administrador (mesmas do usuário)
            $regras_validacao = [
                'primeiro_nome' => 'required|string|min:2|max:50|regex:/^[A-Za-zÀ-ÿ\s]+$/',
                'segundo_nome' => 'required|string|min:2|max:50|regex:/^[A-Za-zÀ-ÿ\s]+$/',
                'apelido' => 'required|string|min:3|max:30|alpha_num',
                'email' => 'required|email|max:255|unique:usuarios,email',
                'senha' => 'required|string|min:8|max:50|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                'senha_confirmation' => 'required|string',
                'confirmacao_senha' => 'required|string|same:senha',
                'telefone' => 'required|string|regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/',
                'numero_documento' => 'required|string|digits:11|unique:usuarios,numero_documento',
                'data_nascimento' => 'required|date|before:today|after:1900-01-01',
                'aceite_comunicacoes_email' => 'sometimes|boolean',
                'aceite_comunicacoes_sms' => 'sometimes|boolean',
                'aceite_comunicacoes_whatsapp' => 'sometimes|boolean',
                'aceite_termos_uso' => 'required|accepted',
                'aceite_politica_privacidade' => 'required|accepted',
            ];

            // Mensagens de erro personalizadas em português
            $mensagens_erro = [
                'primeiro_nome.required' => 'O primeiro nome é obrigatório',
                'primeiro_nome.min' => 'O primeiro nome deve ter pelo menos 2 caracteres',
                'primeiro_nome.max' => 'O primeiro nome deve ter no máximo 50 caracteres',
                'primeiro_nome.regex' => 'O primeiro nome deve conter apenas letras e espaços',
                
                'segundo_nome.required' => 'O segundo nome é obrigatório',
                'segundo_nome.min' => 'O segundo nome deve ter pelo menos 2 caracteres',
                'segundo_nome.max' => 'O segundo nome deve ter no máximo 50 caracteres',
                'segundo_nome.regex' => 'O segundo nome deve conter apenas letras e espaços',
                
                'apelido.required' => 'O apelido é obrigatório',
                'apelido.min' => 'O apelido deve ter pelo menos 3 caracteres',
                'apelido.max' => 'O apelido deve ter no máximo 30 caracteres',
                'apelido.alpha_num' => 'O apelido deve conter apenas letras e números',
                
                'email.required' => 'O email é obrigatório',
                'email.email' => 'O email deve ter um formato válido',
                'email.max' => 'O email deve ter no máximo 255 caracteres',
                'email.unique' => 'Este email já está sendo utilizado',
                
                'senha.required' => 'A senha é obrigatória',
                'senha.min' => 'A senha deve ter pelo menos 8 caracteres',
                'senha.max' => 'A senha deve ter no máximo 50 caracteres',
                'senha.confirmed' => 'A confirmação da senha não confere',
                'senha.regex' => 'A senha deve conter pelo menos: 1 letra minúscula, 1 maiúscula, 1 número e 1 caractere especial (@$!%*?&)',
                
                'confirmacao_senha.required' => 'A confirmação da senha é obrigatória',
                'confirmacao_senha.same' => 'A confirmação da senha deve ser igual à senha',
                
                'telefone.required' => 'O telefone é obrigatório',
                'telefone.regex' => 'O telefone deve estar no formato (XX) XXXXX-XXXX',
                
                'numero_documento.required' => 'O CPF é obrigatório',
                'numero_documento.digits' => 'O CPF deve conter exatamente 11 dígitos',
                'numero_documento.unique' => 'Este CPF já está sendo utilizado',
                
                'data_nascimento.required' => 'A data de nascimento é obrigatória',
                'data_nascimento.date' => 'A data de nascimento deve ser uma data válida',
                'data_nascimento.before' => 'A data de nascimento deve ser anterior a hoje',
                'data_nascimento.after' => 'A data de nascimento deve ser posterior a 1900',
                
                'aceite_termos_uso.required' => 'É obrigatório aceitar os termos de uso',
                'aceite_termos_uso.accepted' => 'É obrigatório aceitar os termos de uso',
                
                'aceite_politica_privacidade.required' => 'É obrigatório aceitar a política de privacidade',
                'aceite_politica_privacidade.accepted' => 'É obrigatório aceitar a política de privacidade',
            ];

            // Executa a validação
            $validador = Validator::make($request->all(), $regras_validacao, $mensagens_erro);

            if ($validador->fails()) {
                Log::warning('AuthController::registrarAdmin - Dados de validação inválidos', [
                    'email' => $request->input('email'),
                    'apelido' => $request->input('apelido'),
                    'errors' => $validador->errors()
                ]);

                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Dados inválidos fornecidos',
                    'erros' => $validador->errors()
                ], 422);
            }

            // Cria o DTO com os dados validados e define como administrador
            $dadosRegistro = SignupDTO::fromRequest(array_merge($request->all(), ['tipo_usuario' => 'administrador']));
            
            // Chama o serviço para registrar o administrador
            $resultado = $this->authService->registrarUsuario($dadosRegistro);

            Log::info('AuthController::registrarAdmin - Administrador registrado com sucesso', [
                'email' => $request->input('email'),
                'apelido' => $request->input('apelido'),
                'user_id' => $resultado['usuario']['id'] ?? null
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Administrador registrado com sucesso! Verifique seu email para ativar a conta.',
                'dados' => $resultado
            ], 201);

        } catch (\Exception $e) {
            Log::error('AuthController::registrarAdmin - Erro durante registro', [
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

    /**
     * Reenvia email de verificação para o usuário autenticado
     * 
     * @return JsonResponse
     */
    public function reenviarVerificacaoEmail(): JsonResponse
    {
        Log::info('AuthController::reenviarVerificacaoEmail - Solicitação de reenvio de email de verificação');

        try {
            $resultado = $this->authService->reenviarEmailVerificacao();

            Log::info('AuthController::reenviarVerificacaoEmail - Email de verificação reenviado com sucesso');

            return response()->json([
                'sucesso' => true,
                'mensagem' => $resultado['mensagem']
            ]);

        } catch (\Exception $e) {
            Log::error('AuthController::reenviarVerificacaoEmail - Erro ao reenviar email de verificação', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Reenvia email de verificação para usuário não autenticado (usado quando o login é bloqueado)
     * 
     * @param Request $request Contém o email do usuário
     * @return JsonResponse
     */
    public function reenviarVerificacaoEmailPublico(Request $request): JsonResponse
    {
        Log::info('AuthController::reenviarVerificacaoEmailPublico - Solicitação de reenvio de email', [
            'email' => $request->input('email'),
            'ip' => $request->ip()
        ]);

        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:usuarios,email',
            ], [
                'email.required' => 'Email é obrigatório',
                'email.email' => 'Email deve ter um formato válido',
                'email.exists' => 'Email não encontrado no sistema',
            ]);

            if ($validator->fails()) {
                Log::warning('AuthController::reenviarVerificacaoEmailPublico - Dados de validação inválidos', [
                    'email' => $request->input('email'),
                    'errors' => $validator->errors()
                ]);

                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Dados inválidos',
                    'erros' => $validator->errors()
                ], 422);
            }

            $resultado = $this->authService->reenviarEmailVerificacaoPublico($request->input('email'));

            Log::info('AuthController::reenviarVerificacaoEmailPublico - Email de verificação reenviado com sucesso', [
                'email' => $request->input('email')
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => $resultado['mensagem']
            ]);

        } catch (\Exception $e) {
            Log::error('AuthController::reenviarVerificacaoEmailPublico - Erro ao reenviar email de verificação', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Verifica o email do usuário através do link enviado por email
     * 
     * @param Request $request Contém parâmetros de verificação (id, hash, expires, signature)
     * @return JsonResponse
     */
    public function verificarEmail(Request $request): JsonResponse
    {
        try {
            // Validar parâmetros necessários
            $request->validate([
                'id' => 'required|string',
                'hash' => 'required|string',
                'expires' => 'required|integer',
                'signature' => 'required|string',
            ]);

            Log::info('AuthController::verificarEmail - Tentativa de verificação de email', [
                'user_id' => $request->id,
                'ip' => $request->ip()
            ]);

            // Verificar se expirou
            if (now()->timestamp > $request->expires) {
                Log::warning('AuthController::verificarEmail - Link de verificação expirado', [
                    'user_id' => $request->id,
                    'expires' => $request->expires
                ]);

                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Este link de verificação expirou.',
                    'codigo' => 'LINK_INVALIDO'
                ], 422);
            }

            // Validar a assinatura
            // Laravel gera os parâmetros em ordem alfabética: expires, hash, id
            // Precisamos reconstruir a URL exatamente como ela foi gerada
            $baseUrl = URL::route('verification.verify', [], true);
            
            // Construir a query string na ordem alfabética correta
            $queryString = http_build_query([
                'expires' => $request->expires,
                'hash' => $request->hash,
                'id' => $request->id,
            ]);
            
            $urlToValidate = $baseUrl . '?' . $queryString;
            
            // Calcular a assinatura esperada
            $expectedSignature = hash_hmac('sha256', $urlToValidate, config('app.key'));
            
            // Comparar as assinaturas
            if (!hash_equals($expectedSignature, $request->signature)) {
                Log::warning('AuthController::verificarEmail - Assinatura inválida', [
                    'user_id' => $request->id,
                    'expected' => $expectedSignature,
                    'received' => $request->signature,
                    'url' => $urlToValidate
                ]);

                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Este link de verificação é inválido.',
                    'codigo' => 'LINK_INVALIDO'
                ], 422);
            }

            $resultado = $this->authService->verificarEmail($request->id, $request->hash);

            Log::info('AuthController::verificarEmail - Email verificado com sucesso', [
                'user_id' => $request->id
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Email verificado com sucesso!',
                'dados' => $resultado
            ], 200);

        } catch (\Exception $e) {
            Log::error('AuthController::verificarEmail - Erro durante verificação de email', [
                'user_id' => $request->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $isAlreadyVerified = strpos($e->getMessage(), 'já foi verificado') !== false;
            $statusCode = $isAlreadyVerified ? 422 : ($e->getCode() ?: 500);
            
            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage(),
                'codigo' => $isAlreadyVerified ? 'JA_VERIFICADO' : 'ERRO_VERIFICACAO'
            ], $statusCode);
        }
    }

    public function logout(): JsonResponse
    {
        Log::info('AuthController::logout - Solicitação de logout');

        try {
            $this->authService->logout();

            Log::info('AuthController::logout - Logout realizado com sucesso');

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Logout realizado com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('AuthController::logout - Erro durante logout', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function refresh(): JsonResponse
    {
        Log::info('AuthController::refresh - Solicitação de renovação de token');

        try {
            $result = $this->authService->refresh();

            Log::info('AuthController::refresh - Token renovado com sucesso');

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Token renovado com sucesso',
                'dados' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('AuthController::refresh - Erro durante renovação de token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function me(): JsonResponse
    {
        Log::info('AuthController::me - Solicitação de dados do usuário autenticado');

        try {
            $result = $this->authService->me();

            Log::info('AuthController::me - Dados do usuário recuperados com sucesso', [
                'user_id' => $result['id'] ?? null
            ]);

            return response()->json([
                'sucesso' => true,
                'dados' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('AuthController::me - Erro ao recuperar dados do usuário', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Envia email de recuperação de senha
     * 
     * @param Request $request Contém o email do usuário
     * @return JsonResponse
     */
    public function solicitarRecuperacaoSenha(Request $request): JsonResponse
    {
        Log::info('AuthController::solicitarRecuperacaoSenha - Solicitação de recuperação de senha', [
            'email' => $request->input('email'),
            'ip' => $request->ip()
        ]);

        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ], [
                'email.required' => 'Email é obrigatório',
                'email.email' => 'Email deve ter um formato válido',
            ]);

            if ($validator->fails()) {
                Log::warning('AuthController::solicitarRecuperacaoSenha - Dados de validação inválidos', [
                    'email' => $request->input('email'),
                    'errors' => $validator->errors()
                ]);

                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Dados inválidos',
                    'erros' => $validator->errors()
                ], 422);
            }

            $resultado = $this->authService->enviarEmailRecuperacaoSenha($request->input('email'));

            Log::info('AuthController::solicitarRecuperacaoSenha - Email de recuperação processado', [
                'email' => $request->input('email')
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => $resultado['mensagem']
            ]);

        } catch (\Exception $e) {
            Log::error('AuthController::solicitarRecuperacaoSenha - Erro ao processar recuperação de senha', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Redefine a senha do usuário
     * 
     * @param Request $request Contém o token, email e nova senha
     * @return JsonResponse
     */
    public function redefinirSenha(Request $request): JsonResponse
    {
        Log::info('AuthController::redefinirSenha - Tentativa de redefinição de senha', [
            'email' => $request->input('email'),
            'ip' => $request->ip()
        ]);

        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'token' => 'required|string',
                'senha' => 'required|string|min:8|max:50|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                'confirmacao_senha' => 'required|string|same:senha',
            ], [
                'email.required' => 'Email é obrigatório',
                'email.email' => 'Email deve ter um formato válido',
                'token.required' => 'Token é obrigatório',
                'senha.required' => 'A senha é obrigatória',
                'senha.min' => 'A senha deve ter pelo menos 8 caracteres',
                'senha.max' => 'A senha deve ter no máximo 50 caracteres',
                'senha.regex' => 'A senha deve conter pelo menos: 1 letra minúscula, 1 maiúscula, 1 número e 1 caractere especial (@$!%*?&)',
                'confirmacao_senha.required' => 'A confirmação da senha é obrigatória',
                'confirmacao_senha.same' => 'A confirmação da senha deve ser igual à senha',
            ]);

            if ($validator->fails()) {
                Log::warning('AuthController::redefinirSenha - Dados de validação inválidos', [
                    'email' => $request->input('email'),
                    'errors' => $validator->errors()
                ]);

                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Dados inválidos',
                    'erros' => $validator->errors()
                ], 422);
            }

            $resultado = $this->authService->redefinirSenha(
                $request->input('email'),
                $request->input('token'),
                $request->input('senha'),
                $request->input('confirmacao_senha')
            );

            Log::info('AuthController::redefinirSenha - Senha redefinida com sucesso', [
                'email' => $request->input('email')
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => $resultado['mensagem'],
                'dados' => $resultado['usuario']
            ]);

        } catch (\Exception $e) {
            Log::error('AuthController::redefinirSenha - Erro ao redefinir senha', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Inicia o processo de autenticação com Google OAuth
     * Redireciona diretamente para o Google
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function loginComGoogle()
    {
        Log::info('AuthController::loginComGoogle - Iniciando autenticação com Google');

        try {
            $redirectUrl = $this->authService->loginComGoogle();

            Log::info('AuthController::loginComGoogle - Redirecionando para Google OAuth', [
                'redirect_url' => $redirectUrl
            ]);

            // Redireciona diretamente para o Google
            return redirect()->away($redirectUrl);

        } catch (\Exception $e) {
            Log::error('AuthController::loginComGoogle - Erro ao iniciar autenticação com Google', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Em caso de erro, redireciona para página de login do frontend com mensagem de erro
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
            $errorMessage = urlencode($e->getMessage());
            
            return redirect()->away("{$frontendUrl}/login?error={$errorMessage}");
        }
    }

    /**
     * Processa o callback do Google OAuth e redireciona para o frontend
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function googleCallback()
    {
        Log::info('AuthController::googleCallback - Processando callback do Google');

        try {
            $result = $this->authService->callbackGoogle();

            Log::info('AuthController::googleCallback - Autenticação com Google realizada com sucesso', [
                'user_id' => $result['usuario']['id'] ?? null,
                'email' => $result['usuario']['email'] ?? null,
                'is_admin' => $result['usuario']['is_admin'] ?? false,
            ]);

            // Monta URL do frontend baseada no tipo de usuário
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
            
            // Define o caminho baseado no tipo de usuário
            $path = $result['usuario']['is_admin'] ? '/admin/painel' : '/cliente/painel';
            
            // Codifica o token e dados do usuário para passar via query string
            $queryParams = http_build_query([
                'token' => $result['access_token'],
                'user' => json_encode($result['usuario']),
                'expires_in' => $result['expires_in'],
            ]);

            $redirectUrl = $frontendUrl . $path . '?' . $queryParams;

            Log::info('AuthController::googleCallback - Redirecionando para frontend', [
                'redirect_url' => $redirectUrl,
                'is_admin' => $result['usuario']['is_admin'],
            ]);

            // Redireciona para o frontend
            return redirect()->away($redirectUrl);

        } catch (\Exception $e) {
            Log::error('AuthController::googleCallback - Erro no callback do Google', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Em caso de erro, redireciona para página de login do frontend com mensagem de erro
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
            $errorMessage = urlencode($e->getMessage());
            
            return redirect()->away("{$frontendUrl}/login?error={$errorMessage}");
        }
    }

    /**
     * Atualiza o perfil do usuário autenticado
     * 
     * @param Request $request Dados do perfil a serem atualizados
     * @return JsonResponse
     */
    public function atualizarPerfil(Request $request): JsonResponse
    {
        Log::info('AuthController::atualizarPerfil - Atualização de perfil solicitada');

        try {
            // Obtém o usuário autenticado para validações de uniqueness
            $usuarioAtual = JWTAuth::parseToken()->authenticate();
            
            $validator = Validator::make($request->all(), [
                'primeiro_nome' => 'sometimes|required|string|min:2|max:50|regex:/^[A-Za-zÀ-ÿ\s]+$/',
                'segundo_nome' => 'sometimes|string|min:2|max:50|regex:/^[A-Za-zÀ-ÿ\s]+$|nullable',
                'apelido' => 'sometimes|required|string|min:3|max:30|alpha_num',
                'email' => 'sometimes|required|email|max:255|unique:usuarios,email,' . $usuarioAtual->id,
                'telefone' => 'sometimes|string|max:20|nullable',
                'numero_documento' => 'sometimes|string|max:20|unique:usuarios,numero_documento,' . $usuarioAtual->id . '|nullable',
                'data_nascimento' => 'sometimes|date|nullable',
                'aceite_comunicacoes_email' => 'sometimes|boolean',
                'aceite_comunicacoes_sms' => 'sometimes|boolean',
                'aceite_comunicacoes_whatsapp' => 'sometimes|boolean',
            ], [
                'primeiro_nome.required' => 'Primeiro nome é obrigatório',
                'primeiro_nome.min' => 'Primeiro nome deve ter no mínimo 2 caracteres',
                'primeiro_nome.max' => 'Primeiro nome deve ter no máximo 50 caracteres',
                'primeiro_nome.regex' => 'Primeiro nome deve conter apenas letras e espaços',
                'segundo_nome.min' => 'Segundo nome deve ter no mínimo 2 caracteres',
                'segundo_nome.max' => 'Segundo nome deve ter no máximo 50 caracteres',
                'segundo_nome.regex' => 'Segundo nome deve conter apenas letras e espaços',
                'apelido.required' => 'Apelido é obrigatório',
                'apelido.min' => 'Apelido deve ter no mínimo 3 caracteres',
                'apelido.max' => 'Apelido deve ter no máximo 30 caracteres',
                'apelido.alpha_num' => 'Apelido deve conter apenas letras e números',
                'email.required' => 'Email é obrigatório',
                'email.email' => 'Email deve ter um formato válido',
                'email.unique' => 'Este email já está sendo usado por outro usuário',
                'telefone.max' => 'Telefone deve ter no máximo 20 caracteres',
                'numero_documento.max' => 'Número do documento deve ter no máximo 20 caracteres',
                'numero_documento.unique' => 'Este documento já está sendo usado por outro usuário',
                'data_nascimento.date' => 'Data de nascimento deve ser uma data válida',
                'aceite_comunicacoes_email.boolean' => 'Aceite de comunicações por email deve ser verdadeiro ou falso',
                'aceite_comunicacoes_sms.boolean' => 'Aceite de comunicações por SMS deve ser verdadeiro ou falso',
                'aceite_comunicacoes_whatsapp.boolean' => 'Aceite de comunicações por WhatsApp deve ser verdadeiro ou falso',
            ]);

            if ($validator->fails()) {
                Log::warning('AuthController::atualizarPerfil - Dados de validação inválidos', [
                    'errors' => $validator->errors()
                ]);

                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Dados inválidos',
                    'erros' => $validator->errors()
                ], 422);
            }

            $result = $this->authService->atualizarPerfil($request->all());

            Log::info('AuthController::atualizarPerfil - Perfil atualizado com sucesso', [
                'user_id' => $result['id'] ?? null,
                'email' => $result['email'] ?? null
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Perfil atualizado com sucesso',
                'dados' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('AuthController::atualizarPerfil - Erro ao atualizar perfil', [
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