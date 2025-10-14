# Autenticação com Google OAuth

Este guia descreve como implementar a autenticação com Google no front-end da aplicação Pharmedice Customer Area.

## Sumário

- [Visão Geral](#visão-geral)
- [Configuração no Backend](#configuração-no-backend)
- [Fluxo de Autenticação](#fluxo-de-autenticação)
- [Integração no Frontend](#integração-no-frontend)
- [Exemplos de Implementação](#exemplos-de-implementação)
- [Tratamento de Erros](#tratamento-de-erros)
- [Perguntas Frequentes](#perguntas-frequentes)

---

## Visão Geral

A autenticação com Google OAuth permite que usuários façam login ou criem contas usando suas credenciais do Google, oferecendo:

- **Login rápido e seguro**: Sem necessidade de criar e lembrar senhas
- **Vinculação de contas**: Usuários existentes podem vincular suas contas Google
- **Email verificado automaticamente**: O Google já valida o email do usuário
- **Experiência familiar**: Interface de login que os usuários já conhecem

### Como Funciona

1. O usuário clica em "Entrar com Google" no frontend
2. O frontend solicita a URL de redirecionamento ao backend
3. O usuário é redirecionado para a página de login do Google
4. Após autenticação, o Google redireciona de volta para o callback do backend
5. O backend processa os dados e retorna um token JWT
6. O frontend armazena o token e o usuário está autenticado

---

## Configuração no Backend

### 1. Criar Projeto no Google Cloud Console

1. Acesse o [Google Cloud Console](https://console.cloud.google.com/)
2. Crie um novo projeto ou selecione um existente
3. Ative a **Google+ API**

### 2. Criar Credenciais OAuth 2.0

1. Vá para **APIs e Serviços > Credenciais**
2. Clique em **Criar Credenciais > ID do cliente OAuth**
3. Selecione **Aplicativo da Web**
4. Configure as URLs autorizadas:

   **Origens JavaScript autorizadas:**
   ```
   http://localhost:3000
   https://seu-dominio.com
   ```

   **URIs de redirecionamento autorizados:**
   ```
   http://localhost:8000/api/auth/google/callback
   https://api.seu-dominio.com/api/auth/google/callback
   ```

5. Copie o **Client ID** e o **Client Secret**

### 3. Configurar Variáveis de Ambiente

Adicione as seguintes variáveis ao arquivo `.env` do backend:

```env
GOOGLE_CLIENT_ID=seu-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=seu-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback
```

**Importante:** Altere a `GOOGLE_REDIRECT_URI` para a URL de produção quando fazer deploy.

### 4. Executar Migrações

Execute a migration para adicionar os campos necessários na tabela de usuários:

```bash
php artisan migrate
```

Esta migration adiciona os seguintes campos:
- `google_id`: Identificador único do usuário no Google
- `provider`: Provedor OAuth (ex: "google")
- `avatar`: URL do avatar do usuário
- Torna campos opcionais: `senha`, `telefone`, `numero_documento`, `data_nascimento`, `apelido`

---

## Fluxo de Autenticação

### Diagrama de Sequência

```
┌──────────┐         ┌──────────┐         ┌──────────┐         ┌──────────┐
│ Frontend │         │ Backend  │         │  Google  │         │ Database │
└────┬─────┘         └────┬─────┘         └────┬─────┘         └────┬─────┘
     │                    │                    │                    │
     │ 1. GET /auth/google│                    │                    │
     ├───────────────────>│                    │                    │
     │                    │                    │                    │
     │ 2. Redirect URL    │                    │                    │
     │<───────────────────┤                    │                    │
     │                    │                    │                    │
     │ 3. Redirect to Google Auth             │                    │
     ├────────────────────────────────────────>│                    │
     │                    │                    │                    │
     │ 4. User authenticates                   │                    │
     │<────────────────────────────────────────┤                    │
     │                    │                    │                    │
     │ 5. GET /auth/google/callback?code=...   │                    │
     │                    │<───────────────────┤                    │
     │                    │                    │                    │
     │                    │ 6. Exchange code for user data          │
     │                    ├────────────────────>│                    │
     │                    │                    │                    │
     │                    │ 7. User data       │                    │
     │                    │<────────────────────┤                    │
     │                    │                    │                    │
     │                    │ 8. Find/Create user│                    │
     │                    ├────────────────────────────────────────>│
     │                    │                    │                    │
     │                    │ 9. User record     │                    │
     │                    │<────────────────────────────────────────┤
     │                    │                    │                    │
     │ 10. JWT Token + User data              │                    │
     │<───────────────────┤                    │                    │
     │                    │                    │                    │
```

### Passo a Passo Detalhado

#### 1. Iniciar Autenticação

**Endpoint:** `GET /api/auth/google`

O frontend faz uma requisição para obter a URL de redirecionamento do Google.

**Resposta de Sucesso (200):**
```json
{
  "sucesso": true,
  "mensagem": "Redirecione o usuário para a URL fornecida",
  "dados": {
    "redirect_url": "https://accounts.google.com/o/oauth2/auth?..."
  }
}
```

#### 2. Redirecionamento para o Google

O frontend redireciona o usuário para a `redirect_url` recebida. O Google exibe sua interface de login/autorização.

#### 3. Callback do Google

Após o usuário autorizar, o Google redireciona para:
```
GET /api/auth/google/callback?code=AUTHORIZATION_CODE&state=...
```

O backend:
- Valida o código de autorização
- Obtém os dados do usuário do Google (nome, email, foto)
- Verifica se o usuário já existe:
  - **Se existe por `google_id`**: Faz login
  - **Se existe por `email`**: Vincula a conta Google e faz login
  - **Se não existe**: Cria nova conta e faz login

**Resposta de Sucesso (200):**
```json
{
  "sucesso": true,
  "mensagem": "Autenticação com Google realizada com sucesso",
  "dados": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "bearer",
    "expires_in": 3600,
    "usuario": {
      "id": "01HN2P3Q4R5S6T7U8V9W0X1Y2Z",
      "primeiro_nome": "João",
      "segundo_nome": "Silva",
      "email": "joao.silva@gmail.com",
      "tipo_usuario": "usuario",
      "is_admin": false,
      "email_verificado": true,
      "avatar": "https://lh3.googleusercontent.com/..."
    }
  }
}
```

---

## Integração no Frontend

### Next.js com React

#### 1. Criar Hook de Autenticação

```typescript
// hooks/useGoogleAuth.ts
import { useState } from 'react';
import { useRouter } from 'next/navigation';

interface GoogleAuthResponse {
  access_token: string;
  token_type: string;
  expires_in: number;
  usuario: {
    id: string;
    primeiro_nome: string;
    segundo_nome: string;
    email: string;
    tipo_usuario: string;
    is_admin: boolean;
    email_verificado: boolean;
    avatar?: string;
  };
}

export const useGoogleAuth = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const router = useRouter();
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

  const loginWithGoogle = async () => {
    try {
      setLoading(true);
      setError(null);

      // 1. Obter URL de redirecionamento do Google
      const response = await fetch(`${API_URL}/auth/google`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error('Erro ao iniciar autenticação com Google');
      }

      const data = await response.json();

      if (!data.sucesso) {
        throw new Error(data.mensagem || 'Erro ao iniciar autenticação');
      }

      // 2. Salvar estado para validação posterior (opcional mas recomendado)
      const state = Math.random().toString(36).substring(7);
      sessionStorage.setItem('oauth_state', state);

      // 3. Abrir janela popup com a URL do Google
      const width = 500;
      const height = 600;
      const left = window.screen.width / 2 - width / 2;
      const top = window.screen.height / 2 - height / 2;

      const popup = window.open(
        data.dados.redirect_url,
        'Google Login',
        `width=${width},height=${height},left=${left},top=${top}`
      );

      // 4. Escutar mensagem da janela popup
      window.addEventListener('message', async (event) => {
        // Verificar origem por segurança
        if (event.origin !== window.location.origin) {
          return;
        }

        if (event.data.type === 'GOOGLE_AUTH_SUCCESS') {
          const authData: GoogleAuthResponse = event.data.data;
          
          // Salvar token e dados do usuário
          localStorage.setItem('access_token', authData.access_token);
          localStorage.setItem('user', JSON.stringify(authData.usuario));

          // Fechar popup
          popup?.close();

          // Redirecionar para dashboard
          router.push('/dashboard');
        } else if (event.data.type === 'GOOGLE_AUTH_ERROR') {
          setError(event.data.message || 'Erro na autenticação');
          popup?.close();
        }
      });

    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro desconhecido');
    } finally {
      setLoading(false);
    }
  };

  return { loginWithGoogle, loading, error };
};
```

#### 2. Página de Callback

Crie uma página para processar o callback do Google:

```typescript
// app/auth/google/callback/page.tsx
'use client';

import { useEffect, useState } from 'react';
import { useSearchParams } from 'next/navigation';

export default function GoogleCallbackPage() {
  const searchParams = useSearchParams();
  const [status, setStatus] = useState<'loading' | 'success' | 'error'>('loading');
  const [message, setMessage] = useState('Processando autenticação...');

  useEffect(() => {
    const processCallback = async () => {
      try {
        const code = searchParams.get('code');
        const state = searchParams.get('state');

        if (!code) {
          throw new Error('Código de autorização não encontrado');
        }

        // Opcional: Validar state
        const savedState = sessionStorage.getItem('oauth_state');
        if (savedState && state !== savedState) {
          throw new Error('Estado inválido - possível ataque CSRF');
        }

        // Fazer requisição ao backend para processar o callback
        const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';
        const response = await fetch(
          `${API_URL}/auth/google/callback?code=${code}&state=${state}`,
          {
            method: 'GET',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
          }
        );

        if (!response.ok) {
          throw new Error('Erro ao processar autenticação');
        }

        const data = await response.json();

        if (!data.sucesso) {
          throw new Error(data.mensagem || 'Erro na autenticação');
        }

        // Enviar dados para a janela pai (opener)
        if (window.opener) {
          window.opener.postMessage(
            {
              type: 'GOOGLE_AUTH_SUCCESS',
              data: data.dados,
            },
            window.location.origin
          );
        }

        setStatus('success');
        setMessage('Autenticação realizada com sucesso! Você pode fechar esta janela.');

        // Fechar janela automaticamente após 2 segundos
        setTimeout(() => {
          window.close();
        }, 2000);

      } catch (err) {
        console.error('Erro no callback do Google:', err);
        setStatus('error');
        setMessage(err instanceof Error ? err.message : 'Erro desconhecido');

        // Enviar erro para a janela pai
        if (window.opener) {
          window.opener.postMessage(
            {
              type: 'GOOGLE_AUTH_ERROR',
              message: err instanceof Error ? err.message : 'Erro desconhecido',
            },
            window.location.origin
          );
        }

        // Fechar janela após 3 segundos
        setTimeout(() => {
          window.close();
        }, 3000);
      }
    };

    processCallback();
  }, [searchParams]);

  return (
    <div className="flex items-center justify-center min-h-screen bg-gray-100">
      <div className="bg-white p-8 rounded-lg shadow-md max-w-md w-full text-center">
        {status === 'loading' && (
          <>
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
            <p className="text-gray-700">{message}</p>
          </>
        )}
        
        {status === 'success' && (
          <>
            <div className="text-green-500 text-5xl mb-4">✓</div>
            <p className="text-gray-700">{message}</p>
          </>
        )}
        
        {status === 'error' && (
          <>
            <div className="text-red-500 text-5xl mb-4">✗</div>
            <p className="text-red-600 font-semibold">Erro na Autenticação</p>
            <p className="text-gray-700 mt-2">{message}</p>
            <button
              onClick={() => window.close()}
              className="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
            >
              Fechar
            </button>
          </>
        )}
      </div>
    </div>
  );
}
```

#### 3. Componente do Botão de Login

```typescript
// components/GoogleLoginButton.tsx
'use client';

import { useGoogleAuth } from '@/hooks/useGoogleAuth';

export default function GoogleLoginButton() {
  const { loginWithGoogle, loading, error } = useGoogleAuth();

  return (
    <div className="w-full">
      <button
        onClick={loginWithGoogle}
        disabled={loading}
        className="w-full flex items-center justify-center gap-3 px-4 py-3 border border-gray-300 rounded-lg shadow-sm bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        {loading ? (
          <>
            <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-gray-900"></div>
            <span className="text-gray-700 font-medium">Autenticando...</span>
          </>
        ) : (
          <>
            <svg className="w-5 h-5" viewBox="0 0 24 24">
              <path
                fill="#4285F4"
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
              />
              <path
                fill="#34A853"
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
              />
              <path
                fill="#FBBC05"
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
              />
              <path
                fill="#EA4335"
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
              />
            </svg>
            <span className="text-gray-700 font-medium">Continuar com Google</span>
          </>
        )}
      </button>
      
      {error && (
        <p className="mt-2 text-sm text-red-600 text-center">{error}</p>
      )}
    </div>
  );
}
```

#### 4. Uso na Página de Login

```typescript
// app/login/page.tsx
import GoogleLoginButton from '@/components/GoogleLoginButton';

export default function LoginPage() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50">
      <div className="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow">
        <div>
          <h2 className="text-center text-3xl font-bold text-gray-900">
            Entre na sua conta
          </h2>
        </div>
        
        {/* Formulário de login tradicional */}
        <form className="mt-8 space-y-6">
          {/* ... campos de email e senha ... */}
        </form>

        {/* Divisor */}
        <div className="relative my-6">
          <div className="absolute inset-0 flex items-center">
            <div className="w-full border-t border-gray-300"></div>
          </div>
          <div className="relative flex justify-center text-sm">
            <span className="px-2 bg-white text-gray-500">Ou</span>
          </div>
        </div>

        {/* Botão do Google */}
        <GoogleLoginButton />
      </div>
    </div>
  );
}
```

---

## Exemplos de Implementação

### React (Vite/CRA)

```typescript
// hooks/useGoogleAuth.ts
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';

export const useGoogleAuth = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const navigate = useNavigate();
  const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

  const loginWithGoogle = async () => {
    try {
      setLoading(true);
      setError(null);

      const response = await fetch(`${API_URL}/auth/google`);
      const data = await response.json();

      if (!data.sucesso) {
        throw new Error(data.mensagem);
      }

      // Abrir popup
      const width = 500;
      const height = 600;
      const left = window.screen.width / 2 - width / 2;
      const top = window.screen.height / 2 - height / 2;

      const popup = window.open(
        data.dados.redirect_url,
        'Google Login',
        `width=${width},height=${height},left=${left},top=${top}`
      );

      // Escutar mensagem
      window.addEventListener('message', (event) => {
        if (event.origin !== window.location.origin) return;

        if (event.data.type === 'GOOGLE_AUTH_SUCCESS') {
          localStorage.setItem('access_token', event.data.data.access_token);
          localStorage.setItem('user', JSON.stringify(event.data.data.usuario));
          popup?.close();
          navigate('/dashboard');
        } else if (event.data.type === 'GOOGLE_AUTH_ERROR') {
          setError(event.data.message);
          popup?.close();
        }
      });

    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro desconhecido');
    } finally {
      setLoading(false);
    }
  };

  return { loginWithGoogle, loading, error };
};
```

### Vue.js 3 (Composition API)

```typescript
// composables/useGoogleAuth.ts
import { ref } from 'vue';
import { useRouter } from 'vue-router';

export const useGoogleAuth = () => {
  const loading = ref(false);
  const error = ref<string | null>(null);
  const router = useRouter();
  const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

  const loginWithGoogle = async () => {
    try {
      loading.value = true;
      error.value = null;

      const response = await fetch(`${API_URL}/auth/google`);
      const data = await response.json();

      if (!data.sucesso) {
        throw new Error(data.mensagem);
      }

      const width = 500;
      const height = 600;
      const left = window.screen.width / 2 - width / 2;
      const top = window.screen.height / 2 - height / 2;

      const popup = window.open(
        data.dados.redirect_url,
        'Google Login',
        `width=${width},height=${height},left=${left},top=${top}`
      );

      window.addEventListener('message', (event) => {
        if (event.origin !== window.location.origin) return;

        if (event.data.type === 'GOOGLE_AUTH_SUCCESS') {
          localStorage.setItem('access_token', event.data.data.access_token);
          localStorage.setItem('user', JSON.stringify(event.data.data.usuario));
          popup?.close();
          router.push('/dashboard');
        } else if (event.data.type === 'GOOGLE_AUTH_ERROR') {
          error.value = event.data.message;
          popup?.close();
        }
      });

    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Erro desconhecido';
    } finally {
      loading.value = false;
    }
  };

  return { loginWithGoogle, loading, error };
};
```

---

## Tratamento de Erros

### Erros Comuns

| Erro | Causa | Solução |
|------|-------|---------|
| `Erro de autenticação com Google. Por favor, tente novamente.` | Estado OAuth inválido | Limpar cookies/sessão e tentar novamente |
| `Credenciais inválidas` | Client ID/Secret incorretos | Verificar configuração no `.env` |
| `Redirect URI mismatch` | URL de callback não autorizada | Adicionar URL no Google Cloud Console |
| `Usuário inativo` | Conta desativada no sistema | Contatar administrador |

### Exemplo de Tratamento no Frontend

```typescript
try {
  await loginWithGoogle();
} catch (error) {
  if (error.message.includes('inativo')) {
    showNotification('Sua conta foi desativada. Entre em contato com o suporte.');
  } else if (error.message.includes('Google')) {
    showNotification('Erro ao conectar com o Google. Tente novamente.');
  } else {
    showNotification('Erro desconhecido. Tente novamente mais tarde.');
  }
}
```

---

## Perguntas Frequentes

### 1. O que acontece se o usuário já tiver uma conta com o mesmo email?

Se um usuário já existir com o email retornado pelo Google, o sistema automaticamente vincula a conta Google ao usuário existente, atualizando os campos `google_id`, `provider` e `avatar`.

### 2. Os usuários criados via Google precisam definir senha?

Não. Usuários criados via Google podem fazer login exclusivamente através do Google. Se desejarem adicionar uma senha posteriormente, podem usar a funcionalidade de "esqueci minha senha".

### 3. O email é verificado automaticamente?

Sim. Como o Google já verifica o email dos usuários, o campo `email_verified_at` é automaticamente preenchido durante o cadastro via Google.

### 4. Posso vincular múltiplas contas Google ao mesmo usuário?

Não. Atualmente, cada usuário pode ter apenas uma conta Google vinculada. O campo `google_id` é único na tabela de usuários.

### 5. Como funciona em produção?

Em produção, certifique-se de:
1. Atualizar as variáveis de ambiente com as URLs de produção
2. Adicionar as URLs de produção no Google Cloud Console
3. Usar HTTPS (obrigatório para OAuth)
4. Configurar CORS adequadamente no backend

### 6. Posso desabilitar o login tradicional e usar apenas Google?

Sim, mas não é recomendado. É uma boa prática manter múltiplas opções de autenticação para dar flexibilidade aos usuários.

### 7. Como testo localmente?

Use `http://localhost:3000` no frontend e `http://localhost:8000` no backend. Certifique-se de que essas URLs estão configuradas no Google Cloud Console.

---

## Suporte

Para mais informações ou suporte, consulte:

- [Documentação da API](./README.md)
- [Exemplos de Frontend](./exemplos-frontend.md)
- [Google OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)
- Repositório: [GitHub](https://github.com/jmlandi/pharmedice-ca-be)

---

**Última atualização:** Outubro 2025
