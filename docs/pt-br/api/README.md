# Referência da API - Pharmedice Customer Area

> Documentação completa da API REST para o sistema backend da Área do Cliente Pharmedice.

## 🌐 URL Base

```
http://localhost:8000/api
```

## 🔐 Autenticação

Esta API usa **JWT (JSON Web Tokens)** para autenticação. A maioria dos endpoints requer um token JWT válido no cabeçalho de Authorization.

### Cabeçalho de Autenticação
```http
Authorization: Bearer SEU_TOKEN_JWT_AQUI
```

### Ciclo de Vida do Token
- **Login**: Obter token JWT via `/auth/login`
- **Refresh**: Renovar token via `/auth/refresh`  
- **Expiração**: Tokens expiram após 60 minutos
- **Logout**: Invalidar token via `/auth/logout`

## 📋 Visão Geral dos Endpoints da API

### Endpoints de Autenticação
| Método | Endpoint | Descrição | Auth Obrigatória |
|--------|----------|-----------|------------------|
| `POST` | `/auth/registrar` | Registro de usuário com verificação de email | ❌ |
| `POST` | `/auth/login` | Login do usuário | ❌ |
| `POST` | `/auth/logout` | Logout do usuário | ✅ |
| `POST` | `/auth/refresh` | Renovar token JWT | ✅ |
| `GET` | `/auth/me` | Obter dados do usuário atual | ✅ |
| `POST` | `/auth/reenviar-verificacao-email` | Reenviar email de verificação | ✅ |
| `GET` | `/auth/verificar-email/{id}/{hash}` | Verificar endereço de email | ✅ |

### Endpoints de Gestão de Usuários  
| Método | Endpoint | Descrição | Auth Obrigatória | Apenas Admin |
|--------|----------|-----------|------------------|--------------|
| `GET` | `/usuarios` | Listar todos os usuários | ✅ | ✅ |
| `POST` | `/usuarios` | Criar novo usuário | ✅ | ✅ |
| `GET` | `/usuarios/{id}` | Obter usuário específico | ✅ | ✅ |
| `PUT` | `/usuarios/{id}` | Atualizar dados do usuário | ✅ | ✅ |
| `DELETE` | `/usuarios/{id}` | Excluir usuário (soft delete) | ✅ | ✅ |
| `PUT` | `/usuarios/alterar-senha` | Alterar própria senha | ✅ | ❌ |

### Endpoints de Gestão de Documentos (Laudo)
| Método | Endpoint | Descrição | Auth Obrigatória | Apenas Admin |
|--------|----------|-----------|------------------|--------------|
| `GET` | `/laudos` | Listar documentos | ✅ | ❌ |
| `POST` | `/laudos` | Criar documento com upload de PDF | ✅ | ✅ |
| `GET` | `/laudos/{id}` | Obter documento específico | ✅ | ❌ |
| `PUT` | `/laudos/{id}` | Atualizar documento | ✅ | ✅ |
| `DELETE` | `/laudos/{id}` | Excluir documento (soft delete) | ✅ | ✅ |
| `GET` | `/laudos/{id}/download` | Baixar PDF do documento | ✅ | ❌ |
| `GET` | `/laudos/consultar/{id}` | Consulta pública de documento | ❌ | ❌ |
| `GET` | `/laudos/buscar` | Buscar documentos | ✅ | ❌ |
| `GET` | `/laudos/meus-laudos` | Obter documentos próprios do usuário | ✅ | ❌ |

## 🔐 Endpoints de Autenticação

### Registro de Usuário
Registrar um novo usuário com verificação de email.

```http
POST /api/auth/registrar
Content-Type: application/json

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
            "criado_em": "2025-10-09T10:30:00Z"
        },
        "mensagem_verificacao": "Um email de verificação foi enviado para joao@exemplo.com"
    }
}
```

### Login do Usuário
Autenticar usuário e receber token JWT.

```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "admin@pharmedice.com",
    "senha": "admin123"
}
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "message": "Login realizado com sucesso",
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
        "token_type": "bearer", 
        "expires_in": 3600,
        "user": {
            "id": "01HXXXXX...",
            "primeiro_nome": "Admin",
            "segundo_nome": "User",
            "email": "admin@pharmedice.com",
            "tipo_usuario": "administrador"
        }
    }
}
```

### Obter Usuário Atual
Obter informações do usuário autenticado.

```http
GET /api/auth/me
Authorization: Bearer SEU_TOKEN
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "data": {
        "id": "01HXXXXX...",
        "primeiro_nome": "João",
        "segundo_nome": "Silva",
        "email": "joao@exemplo.com",
        "tipo_usuario": "usuario",
        "email_verificado": true,
        "telefone": "(11) 99999-9999",
        "criado_em": "2025-10-09T10:30:00Z"
    }
}
```

### Logout
Invalidar o token JWT atual.

```http
POST /api/auth/logout
Authorization: Bearer SEU_TOKEN
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "message": "Logout realizado com sucesso"
}
```

## 👥 Endpoints de Gestão de Usuários

### Listar Usuários (Apenas Admin)
Obter lista paginada de todos os usuários.

```http
GET /api/usuarios
Authorization: Bearer ADMIN_TOKEN
```

**Parâmetros de Query:**
- `page` - Número da página (padrão: 1)
- `per_page` - Itens por página (padrão: 15)
- `tipo_usuario` - Filtrar por tipo de usuário (`administrador` ou `usuario`)

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": "01HXXXXX...",
                "primeiro_nome": "João",
                "segundo_nome": "Silva",
                "email": "joao@exemplo.com",
                "tipo_usuario": "usuario",
                "ativo": true,
                "criado_em": "2025-10-09T10:30:00Z"
            }
        ],
        "total": 10,
        "per_page": 15,
        "last_page": 1
    }
}
```

### Alterar Senha
Permitir que o usuário altere sua própria senha.

```http
PUT /api/usuarios/alterar-senha  
Authorization: Bearer SEU_TOKEN
Content-Type: application/json

{
    "senha_atual": "senhaAtual",
    "nova_senha": "NovaSenh@123",
    "nova_senha_confirmation": "NovaSenh@123"
}
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "message": "Senha alterada com sucesso"
}
```

## 📄 Endpoints de Gestão de Documentos (Laudo)

### Listar Documentos
Obter lista paginada de documentos acessíveis ao usuário.

```http
GET /api/laudos
Authorization: Bearer SEU_TOKEN
```

**Parâmetros de Query:**
- `page` - Número da página (padrão: 1)
- `per_page` - Itens por página (padrão: 15)

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": "01HXXXXX...",
                "titulo": "Exame de Sangue",
                "descricao": "Resultado do exame laboratorial",
                "url_arquivo": "laudos/2024/10/arquivo.pdf",
                "usuario": {
                    "id": "01HYYYY...",
                    "primeiro_nome": "João",
                    "segundo_nome": "Silva"
                },
                "criado_em": "2025-10-09T10:30:00Z"
            }
        ],
        "total": 5,
        "per_page": 15,
        "last_page": 1
    }
}
```

### Criar Documento (Apenas Admin)
Criar novo documento com upload de arquivo PDF.

```http
POST /api/laudos
Authorization: Bearer ADMIN_TOKEN
Content-Type: multipart/form-data

{
    "usuario_id": "01HXXXXX...",
    "titulo": "Exame de Sangue",  
    "descricao": "Resultado do exame laboratorial",
    "arquivo": [ARQUIVO_PDF]
}
```

**Resposta de Sucesso (201):**
```json
{
    "success": true,
    "message": "Laudo criado com sucesso",
    "data": {
        "id": "01HXXXXX...",
        "titulo": "Exame de Sangue",
        "descricao": "Resultado do exame laboratorial", 
        "url_arquivo": "laudos/2024/10/nome-unico.pdf",
        "usuario_id": "01HYYYY...",
        "criado_em": "2025-10-09T10:30:00Z"
    }
}
```

### Buscar Documentos
Buscar documentos por título ou descrição.

```http
GET /api/laudos/buscar?busca=exame
Authorization: Bearer SEU_TOKEN
```

**Parâmetros de Query:**
- `busca` - Termo de busca (pesquisa no título e descrição)

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": "01HXXXXX...",
            "titulo": "Exame de Sangue",
            "descricao": "Resultado do exame laboratorial",
            "usuario": {
                "primeiro_nome": "João",
                "segundo_nome": "Silva"
            },
            "criado_em": "2025-10-09T10:30:00Z"
        }
    ]
}
```

### Baixar Documento
Baixar o arquivo PDF de um documento específico.

```http
GET /api/laudos/{id}/download
Authorization: Bearer SEU_TOKEN
```

**Resposta de Sucesso (200):**
Retorna o arquivo PDF com cabeçalhos apropriados para download.

### Consulta Pública de Documento  
Endpoint público para visualizar documento específico (não requer autenticação).

```http
GET /api/laudos/consultar/{id}
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "data": {
        "id": "01HXXXXX...",
        "titulo": "Exame de Sangue",
        "descricao": "Resultado do exame laboratorial",
        "usuario": {
            "primeiro_nome": "João", 
            "segundo_nome": "Silva"
        },
        "criado_em": "2025-10-09T10:30:00Z"
    }
}
```

## ⚠️ Respostas de Erro

### Erros de Autenticação
```json
// 401 Unauthorized
{
    "success": false,
    "message": "Token não fornecido"
}

// 401 Token Inválido  
{
    "success": false,
    "message": "Token inválido"
}
```

### Erros de Autorização
```json
// 403 Forbidden
{
    "success": false, 
    "message": "Acesso negado. Permissão insuficiente."
}
```

### Erros de Validação
```json
// 422 Unprocessable Entity
{
    "success": false,
    "message": "Dados inválidos fornecidos",
    "erros": {
        "email": ["O campo email é obrigatório"],
        "senha": ["A senha deve ter no mínimo 8 caracteres"]
    }
}
```

### Erros do Servidor
```json
// 500 Internal Server Error
{
    "success": false,
    "message": "Erro interno do servidor"
}
```

## 🧪 Testando a API

### Usando cURL

```bash
# Login
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@pharmedice.com","senha":"admin123"}' \
  | jq -r '.data.access_token')

# Usar token para requisições autenticadas
curl -X GET http://localhost:8000/api/laudos \
  -H "Authorization: Bearer $TOKEN"
```

### Usando Postman
1. Importe a coleção da API (se disponível)
2. Configure variáveis de ambiente para URL base e tokens
3. Use os endpoints de autenticação para obter tokens
4. Teste outros endpoints com cabeçalhos de autorização apropriados

## 📝 Limitação de Taxa

Alguns endpoints podem ter limitação de taxa aplicada:
- **Tentativas de login**: 5 por minuto por IP
- **Reenvio de verificação de email**: 3 por minuto por usuário
- **Redefinição de senha**: 3 por hora por email

## 🔒 Considerações de Segurança

- Sempre use HTTPS em produção
- Tokens JWT devem ser armazenados de forma segura no cliente
- Implemente configuração CORS adequada
- Valide todos os uploads de arquivo
- Use variáveis de ambiente para configurações sensíveis
- Monitore atividade suspeita e implemente logging

---

Para informações mais detalhadas, consulte os arquivos de documentação de endpoints específicos neste diretório.