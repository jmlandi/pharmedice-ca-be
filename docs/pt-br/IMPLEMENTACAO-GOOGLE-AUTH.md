# Autenticação com Google - Resumo da Implementação

## 📋 Arquivos Modificados/Criados

### Backend

#### 1. **Database**
- ✅ `database/migrations/2025_10_14_172343_add_social_login_fields_to_usuarios_table.php`
  - Adiciona campos: `google_id`, `provider`, `avatar`
  - Torna opcionais: `senha`, `telefone`, `numero_documento`, `data_nascimento`, `apelido`

#### 2. **Models**
- ✅ `app/Models/Usuario.php`
  - Adicionados campos `google_id`, `provider`, `avatar` no `$fillable`
  - Modificado mutator `setSenhaAttribute` para aceitar valores nulos

#### 3. **Services**
- ✅ `app/Services/AuthService.php`
  - Método `loginComGoogle()`: Retorna URL de redirecionamento do Google
  - Método `callbackGoogle()`: Processa callback e autentica/cria usuário

#### 4. **Controllers**
- ✅ `app/Http/Controllers/AuthController.php`
  - Método `loginComGoogle()`: Endpoint para iniciar autenticação
  - Método `googleCallback()`: Endpoint para processar callback

#### 5. **Routes**
- ✅ `routes/api.php`
  - `GET /api/auth/google`: Inicia autenticação
  - `GET /api/auth/google/callback`: Processa callback

#### 6. **Configuration**
- ✅ `config/services.php`: Configuração do Google OAuth
- ✅ `.env.example`: Variáveis de ambiente necessárias
- ✅ `composer.json`: Laravel Socialite instalado

#### 7. **Documentation**
- ✅ `docs/pt-br/autenticacao-google.md`: Guia completo em português
- ✅ `docs/en/google-authentication.md`: Guia completo em inglês

---

## 🚀 Como Usar

### 1. Configurar Variáveis de Ambiente

Adicione ao arquivo `.env`:

```env
GOOGLE_CLIENT_ID=seu-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=seu-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback
FRONTEND_URL=http://localhost:3000
```

**Importante:** 
- Altere a `GOOGLE_REDIRECT_URI` para a URL de produção quando fazer deploy.
- Altere a `FRONTEND_URL` para a URL do seu frontend em produção.

### 2. Executar Migration

```bash
php artisan migrate
```

### 3. Configurar Google Cloud Console

1. Criar projeto no [Google Cloud Console](https://console.cloud.google.com/)
2. Ativar Google+ API
3. Criar credenciais OAuth 2.0
4. Configurar URIs autorizados:
   - **Origem JavaScript**: `http://localhost:3000`
   - **URI de redirecionamento**: `http://localhost:8000/api/auth/google/callback`

---

## 📝 Endpoints Criados

### 1. Iniciar Autenticação com Google

```http
GET /api/auth/google
```

**Resposta:**
```json
{
  "sucesso": true,
  "mensagem": "Redirecione o usuário para a URL fornecida",
  "dados": {
    "redirect_url": "https://accounts.google.com/o/oauth2/auth?..."
  }
}
```

### 2. Callback do Google

```http
GET /api/auth/google/callback?code=AUTHORIZATION_CODE&state=...
```

**Comportamento:**
- Valida o código de autorização
- Autentica/cria usuário
- Gera JWT token
- **Redireciona automaticamente para o frontend:**
  - **Admin**: `FRONTEND_URL/admin/painel?token=JWT&user=BASE64_ENCODED_USER&expires_in=3600`
  - **Cliente**: `FRONTEND_URL/cliente/painel?token=JWT&user=BASE64_ENCODED_USER&expires_in=3600`

**Em caso de erro:**
- Redireciona para: `FRONTEND_URL/login?error=MENSAGEM_DE_ERRO`

---

## 🎨 Integração no Frontend

### Next.js - Processar Callback Automático

O backend redireciona automaticamente para o frontend após autenticação. Crie páginas para processar os parâmetros:

#### 1. Página Admin (`app/admin/painel/page.tsx`)

```typescript
'use client';

import { useEffect } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';

export default function AdminPainelPage() {
  const router = useRouter();
  const searchParams = useSearchParams();

  useEffect(() => {
    // Verificar se há token nos parâmetros da URL (vindo do Google OAuth)
    const token = searchParams.get('token');
    const userEncoded = searchParams.get('user');
    const expiresIn = searchParams.get('expires_in');

    if (token && userEncoded) {
      try {
        // Decodificar dados do usuário
        const user = JSON.parse(atob(userEncoded));

        // Armazenar no localStorage
        localStorage.setItem('access_token', token);
        localStorage.setItem('user', JSON.stringify(user));
        localStorage.setItem('token_expires_at', String(Date.now() + (Number(expiresIn) * 1000)));

        // Limpar URL removendo parâmetros
        router.replace('/admin/painel');
        
        // Recarregar página ou atualizar estado
        window.location.reload();
      } catch (error) {
        console.error('Erro ao processar autenticação:', error);
        router.push('/login?error=Erro ao processar autenticação');
      }
    }
  }, [searchParams, router]);

  // Seu componente de painel continua aqui...
  return (
    <div>
      {/* Conteúdo do painel admin */}
    </div>
  );
}
```

#### 2. Página Cliente (`app/cliente/painel/page.tsx`)

```typescript
'use client';

import { useEffect } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';

export default function ClientePainelPage() {
  const router = useRouter();
  const searchParams = useSearchParams();

  useEffect(() => {
    const token = searchParams.get('token');
    const userEncoded = searchParams.get('user');
    const expiresIn = searchParams.get('expires_in');

    if (token && userEncoded) {
      try {
        const user = JSON.parse(atob(userEncoded));

        localStorage.setItem('access_token', token);
        localStorage.setItem('user', JSON.stringify(user));
        localStorage.setItem('token_expires_at', String(Date.now() + (Number(expiresIn) * 1000)));

        router.replace('/cliente/painel');
        window.location.reload();
      } catch (error) {
        console.error('Erro ao processar autenticação:', error);
        router.push('/login?error=Erro ao processar autenticação');
      }
    }
  }, [searchParams, router]);

  return (
    <div>
      {/* Conteúdo do painel cliente */}
    </div>
  );
}
```

#### 3. Página de Login com Tratamento de Erros

```typescript
'use client';

import { useEffect, useState } from 'react';
import { useSearchParams } from 'next/navigation';

export default function LoginPage() {
  const searchParams = useSearchParams();
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  useEffect(() => {
    const error = searchParams.get('error');
    if (error) {
      setErrorMessage(decodeURIComponent(error));
    }
  }, [searchParams]);

  const handleGoogleLogin = () => {
    // Redirecionar diretamente para o endpoint do Google
    window.location.href = 'http://localhost:8000/api/auth/google';
  };

  return (
    <div>
      {errorMessage && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          <strong>Erro:</strong> {errorMessage}
        </div>
      )}
      
      <button onClick={handleGoogleLogin} className="btn-google">
        Entrar com Google
      </button>
    </div>
  );
}
```

### Fluxo Simplificado

1. **Usuário clica em "Entrar com Google"**
   - Frontend: `window.location.href = 'http://localhost:8000/api/auth/google'`

2. **Backend retorna JSON com redirect_url**
   - Ou redireciona diretamente para o Google

3. **Google autentica e redireciona de volta**
   - Para: `http://localhost:8000/api/auth/google/callback?code=...`

4. **Backend processa e redireciona para frontend**
   - Admin: `http://localhost:3000/admin/painel?token=...&user=...&expires_in=...`
   - Cliente: `http://localhost:3000/cliente/painel?token=...&user=...&expires_in=...`

5. **Frontend extrai parâmetros e armazena**
   - Token no localStorage
   - Dados do usuário no localStorage
   - Remove parâmetros da URL
   - Recarrega ou atualiza estado

---

## 🔄 Fluxo de Autenticação

```
1. Frontend → GET /api/auth/google
2. Backend → Retorna redirect_url
3. Frontend → Redireciona usuário para Google
4. Google → Usuário autentica
5. Google → Redireciona para /api/auth/google/callback?code=...
6. Backend → Valida código e obtém dados do usuário
7. Backend → Cria/encontra usuário no banco
8. Backend → Gera JWT token
9. Backend → Redireciona para frontend com token
   - Admin: frontend_url/admin/painel?token=...
   - Cliente: frontend_url/cliente/painel?token=...
10. Frontend → Extrai token da URL e armazena
```

---

## 💡 Comportamentos Implementados

### Novo Usuário (Google)
- Cria conta automaticamente
- Email verificado por padrão
- Não requer senha
- Avatar do Google salvo

### Usuário Existente (por email)
- Vincula conta Google automaticamente
- Atualiza avatar
- Marca email como verificado

### Usuário Existente (por google_id)
- Login direto
- Atualiza dados se necessário

---

## 📚 Documentação Completa

Para exemplos de implementação no frontend e guias detalhados:

- **Português**: [`docs/pt-br/autenticacao-google.md`](./docs/pt-br/autenticacao-google.md)
- **English**: [`docs/en/google-authentication.md`](./docs/en/google-authentication.md)

---

## ✅ Checklist de Verificação

- [x] Laravel Socialite instalado
- [x] Migration criada e executada
- [x] Modelo Usuario atualizado
- [x] AuthService implementado
- [x] AuthController implementado
- [x] Rotas adicionadas
- [x] Config services.php atualizado
- [x] Variáveis .env.example adicionadas
- [x] Documentação em português criada
- [x] Documentação em inglês criada

---

## 🔐 Segurança

- ✅ Validação de estado OAuth (CSRF protection)
- ✅ Email automaticamente verificado
- ✅ Tokens JWT com expiração
- ✅ Logs de todas as tentativas de autenticação
- ✅ Verificação de usuário ativo

---

## 🧪 Como Testar

1. Configure as credenciais do Google no `.env`
2. Execute as migrations
3. No frontend, faça requisição para `/api/auth/google`
4. Redirecione para a URL recebida
5. Autentique com uma conta Google
6. Verifique se o callback retorna o JWT token

---

**Autor**: GitHub Copilot  
**Data**: Outubro 2025  
**Branch**: `feat/login-com-google`
