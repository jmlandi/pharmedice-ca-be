# Fix: Token Inválido e Erro 500 no /auth/me

## 🐛 Problemas Identificados

### 1. Erro 500 no Endpoint `/auth/me`
Após login com Google, a requisição `GET /api/auth/me` retornava erro 500.

**Causa:**
O método `me()` no `AuthService` tentava acessar `$usuario->data_nascimento->format('Y-m-d')`, mas usuários criados via Google têm `data_nascimento` como `null`, causando erro ao tentar chamar `format()` em null.

### 2. Token Aparentemente Inválido
O token JWT estava sendo gerado corretamente, mas o endpoint `/auth/me` falhava, fazendo parecer que o token estava inválido.

## 🔍 Detalhes Técnicos

### Erro no AuthService::me()

**Código Original (com problema):**
```php
public function me(): array
{
    $usuario = JWTAuth::parseToken()->authenticate();
    
    return [
        // ... outros campos ...
        'data_nascimento' => $usuario->data_nascimento->format('Y-m-d'), // ❌ Erro aqui!
        // ... outros campos ...
    ];
}
```

**Problema:** Usuários do Google não têm `data_nascimento`, `telefone`, nem `numero_documento`. Ao tentar chamar `->format()` em null, PHP lança erro fatal.

### Problema no Accessor nome_completo

**Código Original:**
```php
public function getNomeCompletoAttribute()
{
    return trim($this->primeiro_nome . ' ' . $this->segundo_nome);
}
```

**Problema:** Se `segundo_nome` for null, concatena "João null" ao invés de "João".

## ✅ Soluções Implementadas

### 1. Null-Safe Operator em AuthService::me()

```php
public function me(): array
{
    $usuario = JWTAuth::parseToken()->authenticate();
    
    return [
        'id' => $usuario->id,
        'nome_completo' => $usuario->nome_completo,
        'primeiro_nome' => $usuario->primeiro_nome,
        'segundo_nome' => $usuario->segundo_nome,
        'apelido' => $usuario->apelido,
        'email' => $usuario->email,
        'telefone' => $usuario->telefone,
        'numero_documento' => $usuario->numero_documento,
        'data_nascimento' => $usuario->data_nascimento?->format('Y-m-d'), // ✅ Null-safe
        'tipo_usuario' => $usuario->tipo_usuario,
        'is_admin' => $usuario->is_admin,
        'email_verificado' => $usuario->hasVerifiedEmail(),
        'email_verificado_em' => $usuario->email_verified_at?->format('Y-m-d H:i:s'),
        'aceite_comunicacoes_email' => $usuario->aceite_comunicacoes_email,
        'aceite_comunicacoes_sms' => $usuario->aceite_comunicacoes_sms,
        'aceite_comunicacoes_whatsapp' => $usuario->aceite_comunicacoes_whatsapp,
        'ativo' => $usuario->ativo,
        'avatar' => $usuario->avatar,           // ✅ Adicionado
        'provider' => $usuario->provider,       // ✅ Adicionado
    ];
}
```

**Mudanças:**
- ✅ Usa `?->` (null-safe operator) para `data_nascimento` e `email_verified_at`
- ✅ Adiciona campos `avatar` e `provider` ao retorno
- ✅ Campos nullable retornam `null` ao invés de causar erro

### 2. Accessor nome_completo Melhorado

```php
public function getNomeCompletoAttribute()
{
    $nome = $this->primeiro_nome;
    if ($this->segundo_nome) {
        $nome .= ' ' . $this->segundo_nome;
    }
    return trim($nome);
}
```

**Mudanças:**
- ✅ Verifica se `segundo_nome` existe antes de concatenar
- ✅ Evita "João null" ou "João  " (espaços extras)

## 📋 Campos Nullable para Google OAuth

Após o fix, os seguintes campos podem ser `null` para usuários do Google:

| Campo | Obrigatório Login Tradicional | Obrigatório Google OAuth |
|-------|------------------------------|-------------------------|
| `senha` | ✅ | ❌ null |
| `telefone` | ✅ | ❌ null |
| `numero_documento` | ✅ | ❌ null |
| `data_nascimento` | ✅ | ❌ null |
| `segundo_nome` | ✅ | ❌ null |
| `apelido` | ✅ | ✅ (usa primeiro_nome) |

## 🧪 Testando o Fix

### 1. Criar conta com Google
```bash
# Frontend: Clicar em "Entrar com Google"
# Backend: Logs devem mostrar:
# "Novo usuário criado via Google OAuth"
```

### 2. Testar /auth/me
```bash
# Requisição
GET /api/auth/me
Authorization: Bearer {token}

# Resposta esperada (200 OK)
{
  "sucesso": true,
  "dados": {
    "id": "01HN...",
    "nome_completo": "João",
    "primeiro_nome": "João",
    "segundo_nome": null,
    "apelido": "João",
    "email": "joao@gmail.com",
    "telefone": null,
    "numero_documento": null,
    "data_nascimento": null,
    "tipo_usuario": "usuario",
    "is_admin": false,
    "email_verificado": true,
    "email_verificado_em": "2025-10-14 15:30:00",
    "aceite_comunicacoes_email": false,
    "aceite_comunicacoes_sms": false,
    "aceite_comunicacoes_whatsapp": false,
    "ativo": true,
    "avatar": "https://lh3.googleusercontent.com/...",
    "provider": "google"
  }
}
```

## 🔄 Arquivos Modificados

1. **app/Services/AuthService.php**
   - Método `me()` - Null-safe operators

2. **app/Models/Usuario.php**
   - Accessor `getNomeCompletoAttribute()` - Tratamento de null

## 📊 Exemplo de Fluxo Corrigido

```
Frontend                 Backend              Google
   |                        |                   |
   | 1. Click "Google"      |                   |
   |----------------------->|                   |
   |                        | 2. Redirect       |
   |                        |------------------>|
   |                        |                   |
   |                        | 3. User data      |
   |                        |<------------------|
   |                        |                   |
   |                        | 4. Create user    |
   |                        |   (campos null)   |
   |                        |                   |
   | 5. Redirect + token    |                   |
   |<-----------------------|                   |
   |                        |                   |
   | 6. GET /auth/me        |                   |
   |----------------------->|                   |
   |                        | 7. ✅ Retorna    |
   | 8. User data (200 OK)  |    com nulls      |
   |<-----------------------|                   |
```

## 🚀 Para Aplicar no Staging

```bash
# 1. Pull das mudanças
git pull origin fix/criar-conta-com-google

# 2. Executar migration (se ainda não executou)
php artisan migrate

# 3. Testar fluxo completo
# - Login com Google (novo usuário)
# - Verificar se /auth/me funciona
# - Verificar se nome aparece corretamente no frontend
```

## ⚠️ Importante para o Frontend

O frontend deve estar preparado para receber `null` nos seguintes campos:
- `segundo_nome`
- `telefone`
- `numero_documento`
- `data_nascimento`

**Exemplo de tratamento:**
```typescript
// ✅ Bom
const nomeCompleto = user.nome_completo || user.primeiro_nome;
const telefone = user.telefone || 'Não informado';
const dataNascimento = user.data_nascimento 
  ? new Date(user.data_nascimento).toLocaleDateString()
  : 'Não informado';

// ❌ Ruim - pode causar erro
const telefone = user.telefone.format(); // Erro se null!
```

## 🎯 Resultado Final

✅ Login com Google funciona  
✅ Cadastro com Google funciona  
✅ Token JWT é válido  
✅ Endpoint `/auth/me` retorna 200 OK  
✅ Campos nullable são tratados corretamente  
✅ Frontend recebe dados completos do usuário  
