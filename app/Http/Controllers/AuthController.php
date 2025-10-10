<?php

namespace App\Http\Controllers;

use App\DTOs\LoginDTO;
use App\DTOs\SignupDTO;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function login(Request $request): JsonResponse
    {
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
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $loginDTO = LoginDTO::fromRequest($request->all());
            $result = $this->authService->login($loginDTO);

            return response()->json([
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Registra um novo usuário no sistema
     * 
     * @param Request $request Dados do formulário de registro
     * @return JsonResponse
     */
    public function registrar(Request $request): JsonResponse
    {
        try {
            // Regras de validação para registro de usuário
            $regras_validacao = [
                'primeiro_nome' => 'required|string|min:2|max:50|regex:/^[A-Za-zÀ-ÿ\s]+$/',
                'segundo_nome' => 'required|string|min:2|max:50|regex:/^[A-Za-zÀ-ÿ\s]+$/',
                'apelido' => 'required|string|min:3|max:30|alpha_num|unique:usuarios,apelido',
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
                'apelido.unique' => 'Este apelido já está sendo utilizado',
                
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
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Dados inválidos fornecidos',
                    'erros' => $validador->errors()
                ], 422);
            }

            // Cria o DTO com os dados validados
            $dadosRegistro = SignupDTO::fromRequest($request->all());
            
            // Chama o serviço para registrar o usuário
            $resultado = $this->authService->registrarUsuario($dadosRegistro);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Usuário registrado com sucesso! Verifique seu email para ativar a conta.',
                'dados' => $resultado
            ], 201);

        } catch (\Exception $e) {
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
        try {
            $resultado = $this->authService->reenviarEmailVerificacao();

            return response()->json([
                'sucesso' => true,
                'mensagem' => $resultado['mensagem']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Verifica o email do usuário através do link enviado por email
     * 
     * @param Request $request Contém parâmetros de verificação da URL
     * @param string $id ID do usuário (parâmetro da rota)
     * @param string $hash Hash de verificação (parâmetro da rota)
     * @return JsonResponse
     */
    public function verificarEmail(Request $request, $id, $hash): JsonResponse
    {
        try {
            // Verificar se a URL foi assinada corretamente
            if (!$request->hasValidSignature()) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Link de verificação inválido ou expirado.'
                ], 400);
            }

            $resultado = $this->authService->verificarEmail($id, $hash);

            return response()->json([
                'sucesso' => true,
                'mensagem' => $resultado['mensagem'],
                'dados' => $resultado['usuario']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $this->authService->logout();

            return response()->json([
                'success' => true,
                'message' => 'Logout realizado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function refresh(): JsonResponse
    {
        try {
            $result = $this->authService->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Token renovado com sucesso',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function me(): JsonResponse
    {
        try {
            $result = $this->authService->me();

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }
}