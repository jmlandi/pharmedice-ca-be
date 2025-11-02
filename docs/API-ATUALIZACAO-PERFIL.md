# API de Atualização de Perfil do Usuário

## 📋 Visão Geral

Esta documentação descreve como implementar a funcionalidade de **atualização de perfil próprio** pelos usuários clientes, permitindo que eles editem seus dados pessoais sem necessidade de permissão de administrador.

## 🔗 Endpoint

### Atualizar Perfil Próprio

```http
PUT /api/auth/perfil
```

**Autenticação**: ✅ Obrigatória (JWT Token)  
**Permissão**: ✅ Qualquer usuário autenticado  
**Content-Type**: `application/json`

---

## 📝 Campos Disponíveis para Edição

### ✅ Campos Editáveis

| Campo | Tipo | Obrigatório | Validação | Descrição |
|-------|------|-------------|-----------|-----------|
| `primeiro_nome` | `string` | Sim* | 2-50 chars, apenas letras | Nome do usuário |
| `segundo_nome` | `string` | Não | 2-50 chars, apenas letras, nullable | Sobrenome |
| `apelido` | `string` | Sim* | 3-30 chars, alfanumérico | Nome de exibição |
| `email` | `string` | Sim* | Email válido, único | Email do usuário |
| `telefone` | `string` | Não | Máx 20 chars, nullable | Número de telefone |
| `numero_documento` | `string` | Não | Máx 20 chars, único, nullable | CPF/RG/etc |
| `data_nascimento` | `date` | Não | Formato: YYYY-MM-DD, nullable | Data de nascimento |
| `aceite_comunicacoes_email` | `boolean` | Não | true/false | Aceita emails promocionais |
| `aceite_comunicacoes_sms` | `boolean` | Não | true/false | Aceita SMS promocionais |
| `aceite_comunicacoes_whatsapp` | `boolean` | Não | true/false | Aceita WhatsApp promocional |

**\*Obrigatório apenas se for enviado no request (validação `sometimes|required`)**

### ❌ Campos Protegidos (Não Editáveis)

Estes campos são **automaticamente removidos** da requisição por segurança:

- `tipo_usuario` - Tipo do usuário (usuario/administrador)
- `ativo` - Status ativo/inativo da conta
- `email_verified_at` - Data de verificação do email*
- `google_id` - ID do Google OAuth
- `provider` - Provedor OAuth

**\*Observação**: Se o email for alterado, `email_verified_at` é automaticamente definido como `null`, exigindo nova verificação.

---

## 🚀 Exemplos de Uso

### Exemplo 1: Atualizar Nome e Email

**Request:**
```http
PUT /api/auth/perfil
Content-Type: application/json
Authorization: Bearer SEU_TOKEN_JWT

{
  "primeiro_nome": "João",
  "segundo_nome": "Silva Santos",
  "email": "joao.silva@exemplo.com"
}
```

**Response (200):**
```json
{
  "sucesso": true,
  "mensagem": "Perfil atualizado com sucesso",
  "dados": {
    "id": "01HN2P3Q4R5S6T7U8V9W0X1Y2Z",
    "primeiro_nome": "João",
    "segundo_nome": "Silva Santos",
    "apelido": "joao123",
    "email": "joao.silva@exemplo.com",
    "telefone": null,
    "numero_documento": null,
    "data_nascimento": null,
    "tipo_usuario": "usuario",
    "is_admin": false,
    "email_verificado": false,
    "avatar": null,
    "aceite_comunicacoes_email": true,
    "aceite_comunicacoes_sms": false,
    "aceite_comunicacoes_whatsapp": false,
    "ativo": true,
    "created_at": "2025-10-15T10:30:00.000000Z",
    "updated_at": "2025-11-02T14:25:00.000000Z"
  }
}
```

### Exemplo 2: Atualizar Preferências de Comunicação

**Request:**
```http
PUT /api/auth/perfil
Content-Type: application/json
Authorization: Bearer SEU_TOKEN_JWT

{
  "aceite_comunicacoes_email": false,
  "aceite_comunicacoes_sms": true,
  "aceite_comunicacoes_whatsapp": true
}
```

**Response (200):**
```json
{
  "sucesso": true,
  "mensagem": "Perfil atualizado com sucesso",
  "dados": {
    "id": "01HN2P3Q4R5S6T7U8V9W0X1Y2Z",
    "primeiro_nome": "João",
    "segundo_nome": "Silva Santos",
    "apelido": "joao123",
    "email": "joao.silva@exemplo.com",
    "telefone": null,
    "numero_documento": null,
    "data_nascimento": null,
    "tipo_usuario": "usuario",
    "is_admin": false,
    "email_verificado": false,
    "avatar": null,
    "aceite_comunicacoes_email": false,
    "aceite_comunicacoes_sms": true,
    "aceite_comunicacoes_whatsapp": true,
    "ativo": true,
    "created_at": "2025-10-15T10:30:00.000000Z",
    "updated_at": "2025-11-02T14:26:00.000000Z"
  }
}
```

### Exemplo 3: Atualizar Dados Pessoais Completos

**Request:**
```http
PUT /api/auth/perfil
Content-Type: application/json
Authorization: Bearer SEU_TOKEN_JWT

{
  "primeiro_nome": "Maria",
  "segundo_nome": "da Silva",
  "apelido": "maria2025",
  "email": "maria.silva@novodominio.com",
  "telefone": "(11) 98765-4321",
  "numero_documento": "12345678901",
  "data_nascimento": "1985-03-15"
}
```

---

## ❌ Tratamento de Erros

### Erro de Validação (422)

**Response:**
```json
{
  "sucesso": false,
  "mensagem": "Dados inválidos",
  "erros": {
    "email": [
      "Este email já está sendo usado por outro usuário"
    ],
    "primeiro_nome": [
      "Primeiro nome deve conter apenas letras e espaços"
    ],
    "apelido": [
      "Apelido deve ter no mínimo 3 caracteres"
    ]
  }
}
```

### Token Inválido (401)

**Response:**
```json
{
  "sucesso": false,
  "mensagem": "Token inválido ou expirado"
}
```

### Usuário Inativo (403)

**Response:**
```json
{
  "sucesso": false,
  "mensagem": "Usuário inativo"
}
```

### Erro Interno (500)

**Response:**
```json
{
  "sucesso": false,
  "mensagem": "Erro interno do servidor"
}
```

---

## 🔧 Implementação no Frontend

### JavaScript/TypeScript

```typescript
interface AtualizarPerfilData {
  primeiro_nome?: string;
  segundo_nome?: string;
  apelido?: string;
  email?: string;
  telefone?: string;
  numero_documento?: string;
  data_nascimento?: string; // YYYY-MM-DD
  aceite_comunicacoes_email?: boolean;
  aceite_comunicacoes_sms?: boolean;
  aceite_comunicacoes_whatsapp?: boolean;
}

interface ApiResponse<T> {
  sucesso: boolean;
  mensagem: string;
  dados?: T;
  erros?: Record<string, string[]>;
}

async function atualizarPerfil(dados: AtualizarPerfilData): Promise<ApiResponse<Usuario>> {
  const token = localStorage.getItem('access_token');
  
  const response = await fetch('/api/auth/perfil', {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`,
    },
    body: JSON.stringify(dados),
  });

  return response.json();
}

// Exemplo de uso
async function exemploAtualizacao() {
  try {
    const resultado = await atualizarPerfil({
      primeiro_nome: 'João',
      email: 'joao@exemplo.com',
      aceite_comunicacoes_email: false,
    });

    if (resultado.sucesso) {
      console.log('Perfil atualizado:', resultado.dados);
      // Atualizar estado/contexto do usuário no frontend
    } else {
      console.error('Erro de validação:', resultado.erros);
      // Exibir erros no formulário
    }
  } catch (error) {
    console.error('Erro na requisição:', error);
  }
}
```

### React Hook Exemplo

```typescript
import { useState } from 'react';

interface UseAtualizarPerfilReturn {
  atualizarPerfil: (dados: AtualizarPerfilData) => Promise<void>;
  loading: boolean;
  error: string | null;
  validationErrors: Record<string, string[]> | null;
}

export function useAtualizarPerfil(): UseAtualizarPerfilReturn {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [validationErrors, setValidationErrors] = useState<Record<string, string[]> | null>(null);

  const atualizarPerfil = async (dados: AtualizarPerfilData) => {
    setLoading(true);
    setError(null);
    setValidationErrors(null);

    try {
      const response = await fetch('/api/auth/perfil', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
        },
        body: JSON.stringify(dados),
      });

      const resultado = await response.json();

      if (!resultado.sucesso) {
        if (response.status === 422) {
          setValidationErrors(resultado.erros);
        } else {
          setError(resultado.mensagem);
        }
        return;
      }

      // Sucesso - atualizar contexto do usuário
      // updateUserContext(resultado.dados);
      
    } catch (err) {
      setError('Erro de conexão com o servidor');
    } finally {
      setLoading(false);
    }
  };

  return { atualizarPerfil, loading, error, validationErrors };
}
```

---

## 🔒 Considerações de Segurança

### ✅ Proteções Implementadas

1. **Autenticação JWT**: Apenas usuários logados podem acessar
2. **Campos Protegidos**: Tipo de usuário e status não podem ser alterados
3. **Validação de Uniqueness**: Email e documento únicos no sistema
4. **Sanitização**: Remoção automática de campos proibidos
5. **Logs Detalhados**: Auditoria completa das alterações

### 📧 Verificação de Email

- Se o email for alterado, `email_verified_at` é resetado para `null`
- O usuário precisa verificar o novo email
- Use o endpoint `/api/auth/reenviar-verificacao-email` para reenviar verificação

### 🔄 Fluxo Recomendado

1. **Buscar dados atuais**: `GET /api/auth/me`
2. **Exibir formulário** pré-preenchido
3. **Validar frontend** antes de enviar
4. **Enviar alterações**: `PUT /api/auth/perfil`
5. **Tratar resposta** (sucesso/erro)
6. **Atualizar estado** local do usuário

---

## 📋 Checklist de Implementação

### Backend ✅
- [x] Método `atualizarPerfil` no `AuthService`
- [x] Controller `atualizarPerfil` no `AuthController`
- [x] Rota `PUT /api/auth/perfil`
- [x] Validações de segurança
- [x] Logs de auditoria

### Frontend (Para Implementar)
- [ ] Interface de edição de perfil
- [ ] Validações de formulário
- [ ] Tratamento de erros
- [ ] Feedback de sucesso
- [ ] Atualização do contexto do usuário
- [ ] Reenvio de verificação de email (se necessário)

---

## 🔄 Comparação com Endpoints Existentes

| Funcionalidade | Endpoint | Quem Pode Usar | Campos Editáveis |
|----------------|----------|----------------|------------------|
| **Ver próprios dados** | `GET /auth/me` | Qualquer usuário | N/A (apenas leitura) |
| **Alterar própria senha** | `PUT /usuarios/alterar-senha` | Qualquer usuário | Apenas senha |
| **✨ Editar próprio perfil** | `PUT /auth/perfil` | Qualquer usuário | Dados pessoais e preferências |
| **Editar outros usuários** | `PUT /usuarios/{id}` | Apenas admins | Todos os campos + tipo_usuario |

A nova funcionalidade preenche a lacuna que existia, permitindo que usuários comuns editem seus dados básicos sem precisar de um administrador.

---

**Data de Criação**: 2 de Novembro de 2025  
**Versão da API**: 1.1.0  
**Status**: ✅ Implementado e Pronto para Uso