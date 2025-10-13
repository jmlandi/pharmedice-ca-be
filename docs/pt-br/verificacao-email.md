# Verificação de Email

## Visão Geral

Sistema completo de verificação de email que permite aos usuários confirmarem suas contas através de um link enviado por e-mail após o cadastro.

## Fluxo de Verificação

### Diagrama do Fluxo

```
Usuário                    Backend                     Email                    Frontend
   |                          |                          |                          |
   |--1. POST /registrar----->|                          |                          |
   |   (dados cadastro)       |                          |                          |
   |                          |                          |                          |
   |                          |--2. Cria usuário-------->|                          |
   |                          |    e envia email         |                          |
   |                          |                          |                          |
   |<------3. Resposta--------|                          |                          |
   |   "Cadastro realizado"   |                          |                          |
   |   + token JWT            |                          |                          |
   |                          |                          |                          |
   |<-----------4. Recebe email com link----------------|                          |
   |                          |                          |                          |
   |--5. Clica no link---------------------------------------->|                   |
   |   (URL varia por tipo)   |                          |    /cliente/verificar   |
   |                          |                          |    ou /admin/verificar  |
   |                          |                          |                          |
   |--6. Frontend captura parâmetros-------------------------------->|             |
   |   (id, hash, expires,    |                          |                          |
   |    signature)            |                          |                          |
   |                          |                          |                          |
   |<-7. POST /verificar-email (parâmetros)-------------------|                   |
   |                          |                          |                          |
   |<------8. Email verificado|                          |                          |
   |   "Sucesso!"             |                          |                          |
   |                          |                          |                          |
   |--9. Redireciona para dashboard ou login-------------------->|                   |
```

## API Endpoints

### 1. Registro de Usuário

Durante o registro, o usuário recebe automaticamente um email de verificação.

**Endpoint:** `POST /api/auth/registrar-usuario`

**Body:**
```json
{
  "primeiro_nome": "João",
  "segundo_nome": "Silva",
  "apelido": "joaosilva",
  "email": "joao@exemplo.com",
  "senha": "MinhaSenh@123",
  "senha_confirmation": "MinhaSenh@123",
  "confirmacao_senha": "MinhaSenh@123",
  "telefone": "(11) 99999-9999",
  "numero_documento": "12345678901",
  "data_nascimento": "1990-05-15",
  "aceite_comunicacoes_email": true,
  "aceite_comunicacoes_sms": false,
  "aceite_comunicacoes_whatsapp": true,
  "aceite_termos_uso": true,
  "aceite_politica_privacidade": true
}
```

**Resposta de Sucesso (201):**
```json
{
  "sucesso": true,
  "mensagem": "Usuário registrado com sucesso",
  "dados": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "bearer",
    "expires_in": 3600,
    "usuario": {
      "id": "01HXXXXX...",
      "primeiro_nome": "João",
      "segundo_nome": "Silva",
      "email": "joao@exemplo.com",
      "tipo_usuario": "usuario",
      "email_verificado": false,
      "criado_em": "2025-10-13T10:30:00Z"
    },
    "mensagem_verificacao": "Um email de verificação foi enviado para joao@exemplo.com"
  }
}
```

**Notas:**
- O email de verificação é enviado automaticamente após o cadastro
- O usuário pode fazer login mesmo sem verificar o email, mas terá acesso limitado
- O link de verificação expira em 60 minutos
- **A URL no e-mail varia conforme o tipo de usuário:**
  - **Clientes** (`tipo_usuario = 'usuario'`): `/cliente/verificar-email`
  - **Administradores** (`tipo_usuario = 'administrador'`): `/admin/verificar-email`

### 2. Verificar Email

Após receber o e-mail e clicar no link, o frontend captura os parâmetros da URL e chama este endpoint.

**Endpoint:** `POST /api/auth/verificar-email`

**Body:**
```json
{
  "id": "01HXXXXX...",
  "hash": "abc123def456...",
  "expires": 1697234567,
  "signature": "xyz789..."
}
```

**Resposta de Sucesso (200):**
```json
{
  "sucesso": true,
  "mensagem": "Email verificado com sucesso!",
  "dados": {
    "sucesso": true,
    "mensagem": "Email verificado com sucesso!",
    "usuario": {
      "email": "joao@exemplo.com",
      "email_verificado": true,
      "verificado_em": "2025-10-13T10:35:00.000000Z"
    }
  }
}
```

**Erros Possíveis:**

- **422 - Link Inválido ou Expirado:**
```json
{
  "sucesso": false,
  "mensagem": "Este link de verificação é inválido ou expirou.",
  "codigo": "LINK_INVALIDO"
}
```

- **422 - Email Já Verificado:**
```json
{
  "sucesso": false,
  "mensagem": "Este email já foi verificado",
  "codigo": "JA_VERIFICADO"
}
```

### 3. Reenviar Email de Verificação (Autenticado)

Para usuários logados que ainda não verificaram o email.

**Endpoint:** `POST /api/auth/reenviar-verificacao-email`

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Resposta de Sucesso (200):**
```json
{
  "sucesso": true,
  "mensagem": "Email de verificação reenviado para joao@exemplo.com"
}
```

### 4. Reenviar Email de Verificação (Público)

Para usuários que não estão logados.

**Endpoint:** `POST /api/auth/reenviar-verificacao-email-publico`

**Body:**
```json
{
  "email": "joao@exemplo.com"
}
```

**Resposta de Sucesso (200):**
```json
{
  "sucesso": true,
  "mensagem": "Email de verificação reenviado para joao@exemplo.com"
}
```

## E-mail de Verificação

O e-mail enviado possui:

- 🎨 Design moderno e responsivo
- ✉️ Ícone de confirmação de email
- 🎉 Mensagem de boas-vindas
- ⏱️ Informação sobre expiração do link (60 minutos)
- 📋 Lista de benefícios após verificação
- ⚠️ Aviso de segurança
- 🔗 Botão principal + link alternativo
- 📧 Informações da empresa
- 🎯 **URL personalizada baseada no tipo de usuário**

**Template:** `resources/views/emails/email-verification.blade.php`

### Lógica de Roteamento de URLs

O sistema determina automaticamente a URL correta baseada no campo `tipo_usuario` do banco de dados:

| Tipo de Usuário | Valor no BD | URL Gerada |
|----------------|-------------|------------|
| Cliente | `'usuario'` | `{FRONTEND_URL}/cliente/verificar-email?id=...&hash=...&expires=...&signature=...` |
| Administrador | `'administrador'` | `{FRONTEND_URL}/admin/verificar-email?id=...&hash=...&expires=...&signature=...` |

**Código em `AuthService.php`:**
```php
// Define o caminho baseado no tipo de usuário
if ($usuario->tipo_usuario === 'administrador') {
    $path = '/admin/verificar-email';
} else {
    $path = '/cliente/verificar-email';
}

// Cria a URL do frontend com os parâmetros necessários
$verificationUrl = $frontendUrl . $path . 
    '?id=' . $usuario->id . 
    '&hash=' . sha1($usuario->getEmailForVerification()) .
    '&expires=' . $params['expires'] .
    '&signature=' . $params['signature'];
```

## Frontend - Página de Verificação

O frontend deve criar páginas de verificação em rotas diferentes baseadas no tipo de usuário:

### Para Clientes (tipo_usuario = 'usuario'):
**Rota:** `/cliente/verificar-email`

### Para Administradores (tipo_usuario = 'administrador'):
**Rota:** `/admin/verificar-email`

Ambas as páginas devem:

1. **Capturar os parâmetros da URL:**
   - `id`: ID do usuário
   - `hash`: Hash de verificação
   - `expires`: Timestamp de expiração
   - `signature`: Assinatura criptográfica

2. **Fazer a verificação automaticamente:**
   - Ao carregar a página, chamar imediatamente `POST /api/auth/verificar-email`
   - Enviar os 4 parâmetros no body da requisição

3. **Exibir feedback ao usuário:**
   - Mostrar loading durante a verificação
   - Sucesso: Exibir mensagem de confirmação + botão para ir ao dashboard/login
   - Erro (link expirado): Mostrar botão para reenviar email
   - Erro (já verificado): Mostrar mensagem informativa + botão para login

**Exemplo de Implementação (React/Next.js):**

```typescript
// /cliente/verificar-email/page.tsx
'use client';

import { useEffect, useState } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';

export default function VerificarEmail() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const [status, setStatus] = useState<'loading' | 'success' | 'error'>('loading');
  const [mensagem, setMensagem] = useState('');
  const [codigo, setCodigo] = useState('');

  useEffect(() => {
    const verificarEmail = async () => {
      try {
        const response = await fetch('/api/auth/verificar-email', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id: searchParams.get('id'),
            hash: searchParams.get('hash'),
            expires: searchParams.get('expires'),
            signature: searchParams.get('signature'),
          }),
        });

        const data = await response.json();

        if (data.sucesso) {
          setStatus('success');
          setMensagem(data.mensagem);
          // Redirecionar após 3 segundos
          setTimeout(() => router.push('/cliente/dashboard'), 3000);
        } else {
          setStatus('error');
          setMensagem(data.mensagem);
          setCodigo(data.codigo);
        }
      } catch (error) {
        setStatus('error');
        setMensagem('Erro ao verificar email. Tente novamente.');
      }
    };

    verificarEmail();
  }, [searchParams, router]);

  if (status === 'loading') {
    return <div>Verificando seu email...</div>;
  }

  if (status === 'success') {
    return (
      <div>
        <h1>✓ Email Verificado!</h1>
        <p>{mensagem}</p>
        <p>Redirecionando para o dashboard...</p>
      </div>
    );
  }

  return (
    <div>
      <h1>✗ Erro na Verificação</h1>
      <p>{mensagem}</p>
      {codigo === 'LINK_INVALIDO' && (
        <button onClick={() => router.push('/cliente/reenviar-verificacao')}>
          Reenviar Email de Verificação
        </button>
      )}
      {codigo === 'JA_VERIFICADO' && (
        <button onClick={() => router.push('/login')}>
          Ir para Login
        </button>
      )}
    </div>
  );
}
```

**Exemplos de URL:**

Cliente:
```
http://localhost:3000/cliente/verificar-email?id=01HXXXXX&hash=abc123&expires=1697234567&signature=xyz789
```

Administrador:
```
http://localhost:3000/admin/verificar-email?id=01HXXXXX&hash=abc123&expires=1697234567&signature=xyz789
```

## Configuração

### Variáveis de Ambiente (.env)

Adicione ao seu arquivo `.env`:

```env
# URL do frontend (onde o usuário será redirecionado)
FRONTEND_URL=http://localhost:3000

# Configuração de e-mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=sua-senha-de-app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=seu-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Segurança

### Medidas Implementadas:

1. **URL Assinada:** A URL contém uma assinatura criptográfica que impede manipulação
2. **Expiração:** Links expiram em 60 minutos
3. **Hash do Email:** O hash é gerado baseado no email do usuário, garantindo unicidade
4. **Uso Único:** Email só pode ser verificado uma vez
5. **Rate Limiting:** Considere adicionar rate limiting no endpoint de reenvio
6. **Log de Auditoria:** Todas as tentativas de verificação são registradas

### Recomendações Adicionais:

1. **Rate Limiting:** Adicione throttle nas rotas de reenvio:
```php
Route::post('reenviar-verificacao-email-publico', [AuthController::class, 'reenviarVerificacaoEmailPublico'])
    ->middleware('throttle:3,60'); // 3 tentativas por hora
```

2. **Página de Reenvio:** Crie uma página dedicada para reenviar o email de verificação

3. **Notificação de Sucesso:** Considere enviar um email de confirmação após verificação bem-sucedida

4. **Acesso Limitado:** Implemente restrições para usuários não verificados

## Diferenças vs. Sistema Antigo

### ❌ Sistema Antigo (Laravel Nativo)
- Link abre diretamente no backend
- Backend renderiza página Blade
- Experiência desconectada do frontend
- Difícil customizar a experiência

### ✅ Sistema Novo (Integrado com Frontend)
- Link redireciona para o frontend
- Frontend controla toda a experiência
- Verificação via API REST
- Email HTML profissional e customizável
- Consistente com fluxo de redefinição de senha
- Melhor controle de UX e erros

## Fluxo de Experiência do Usuário

1. **Registro:** Usuário preenche formulário de cadastro
2. **Confirmação:** Sistema confirma cadastro e informa sobre email de verificação
3. **Email:** Usuário recebe email bonito e profissional
4. **Clique:** Usuário clica no botão "Confirmar Email"
5. **Redirecionamento:** Link abre página específica no frontend (/cliente ou /admin)
6. **Verificação Automática:** Frontend verifica automaticamente via API
7. **Feedback:** Usuário vê mensagem de sucesso ou erro
8. **Próximo Passo:** Sistema redireciona para dashboard ou login

## Testes

Execute os testes de verificação de email:

```bash
# Todos os testes de verificação
php artisan test --filter="EmailVerificationTest"

# Teste específico
php artisan test --filter="usuario_pode_verificar_email_com_link_valido"
```

## Troubleshooting

### Email não está sendo enviado
1. Verifique configurações de MAIL no .env
2. Verifique logs em `storage/logs/laravel.log`
3. Teste envio de email: `php artisan tinker` → `Mail::raw('Teste', function($m) { $m->to('seu-email@exemplo.com')->subject('Teste'); });`

### Link de verificação não funciona
1. Verifique se APP_URL está correto no .env
2. Verifique se FRONTEND_URL está configurado
3. Confirme que os parâmetros estão sendo capturados corretamente no frontend
4. Verifique logs de erro no backend

### Email verificado mas sistema não reconhece
1. Verifique campo `email_verified_at` no banco de dados
2. Limpe cache: `php artisan cache:clear`
3. Verifique se o método `hasVerifiedEmail()` está funcionando

---

Este sistema proporciona uma experiência moderna e profissional de verificação de email, totalmente integrada com o frontend e seguindo as melhores práticas de segurança e UX.
