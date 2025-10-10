# API Reference - Pharmedice Customer Area

> Complete REST API documentation for the Pharmedice Customer Area backend system.

## 🌐 Base URL

```
http://localhost:8000/api
```

## 🔐 Authentication

This API uses **JWT (JSON Web Tokens)** for authentication. Most endpoints require a valid JWT token in the Authorization header.

### Authentication Header
```http
Authorization: Bearer YOUR_JWT_TOKEN_HERE
```

### Token Lifecycle
- **Login**: Obtain JWT token via `/auth/login`
- **Refresh**: Renew token via `/auth/refresh`  
- **Expiry**: Tokens expire after 60 minutes
- **Logout**: Invalidate token via `/auth/logout`

## 📋 API Endpoints Overview

### Authentication Endpoints
| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| `POST` | `/auth/registrar` | User registration with email verification | ❌ |
| `POST` | `/auth/login` | User login | ❌ |
| `POST` | `/auth/logout` | User logout | ✅ |
| `POST` | `/auth/refresh` | Refresh JWT token | ✅ |
| `GET` | `/auth/me` | Get current user data | ✅ |
| `POST` | `/auth/reenviar-verificacao-email` | Resend verification email | ✅ |
| `GET` | `/auth/verificar-email/{id}/{hash}` | Verify email address | ✅ |

### User Management Endpoints  
| Method | Endpoint | Description | Auth Required | Admin Only |
|--------|----------|-------------|---------------|------------|
| `GET` | `/usuarios` | List all users | ✅ | ✅ |
| `POST` | `/usuarios` | Create new user | ✅ | ✅ |
| `GET` | `/usuarios/{id}` | Get specific user | ✅ | ✅ |
| `PUT` | `/usuarios/{id}` | Update user data | ✅ | ✅ |
| `DELETE` | `/usuarios/{id}` | Delete user (soft delete) | ✅ | ✅ |
| `PUT` | `/usuarios/alterar-senha` | Change own password | ✅ | ❌ |

### Document (Laudo) Management Endpoints
| Method | Endpoint | Description | Auth Required | Admin Only |
|--------|----------|-------------|---------------|------------|
| `GET` | `/laudos` | List documents | ✅ | ❌ |
| `POST` | `/laudos` | Create document with PDF upload | ✅ | ✅ |
| `GET` | `/laudos/{id}` | Get specific document | ✅ | ❌ |
| `PUT` | `/laudos/{id}` | Update document | ✅ | ✅ |
| `DELETE` | `/laudos/{id}` | Delete document (soft delete) | ✅ | ✅ |
| `GET` | `/laudos/{id}/download` | Download document PDF | ✅ | ❌ |
| `GET` | `/laudos/consultar/{id}` | Public document consultation | ❌ | ❌ |
| `GET` | `/laudos/buscar` | Search documents | ✅ | ❌ |
| `GET` | `/laudos/meus-laudos` | Get user's own documents | ✅ | ❌ |

## 🔐 Authentication Endpoints

### User Registration
Register a new user with email verification.

```http
POST /api/auth/registrar
Content-Type: application/json

{
    "primeiro_nome": "John",
    "segundo_nome": "Doe", 
    "apelido": "johndoe",
    "email": "john@example.com",
    "senha": "MyPassw0rd!",
    "senha_confirmation": "MyPassw0rd!",
    "confirmacao_senha": "MyPassw0rd!",
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

**Success Response (201):**
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
            "primeiro_nome": "John",
            "segundo_nome": "Doe",
            "email": "john@example.com",
            "tipo_usuario": "usuario",
            "email_verificado": false,
            "criado_em": "2025-10-09T10:30:00Z"
        },
        "mensagem_verificacao": "Um email de verificação foi enviado para john@example.com"
    }
}
```

### User Login
Authenticate user and receive JWT token.

```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "admin@pharmedice.com",
    "senha": "admin123"
}
```

**Success Response (200):**
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

### Get Current User
Get authenticated user information.

```http
GET /api/auth/me
Authorization: Bearer YOUR_TOKEN
```

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "id": "01HXXXXX...",
        "primeiro_nome": "John",
        "segundo_nome": "Doe",
        "email": "john@example.com",
        "tipo_usuario": "usuario",
        "email_verificado": true,
        "telefone": "(11) 99999-9999",
        "criado_em": "2025-10-09T10:30:00Z"
    }
}
```

### Logout
Invalidate current JWT token.

```http
POST /api/auth/logout
Authorization: Bearer YOUR_TOKEN
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Logout realizado com sucesso"
}
```

## 👥 User Management Endpoints

### List Users (Admin Only)
Get paginated list of all users.

```http
GET /api/usuarios
Authorization: Bearer ADMIN_TOKEN
```

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15)
- `tipo_usuario` - Filter by user type (`administrador` or `usuario`)

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": "01HXXXXX...",
                "primeiro_nome": "John",
                "segundo_nome": "Doe",
                "email": "john@example.com",
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

### Change Password
Allow user to change their own password.

```http
PUT /api/usuarios/alterar-senha  
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
    "senha_atual": "currentPassword",
    "nova_senha": "NewPassw0rd!",
    "nova_senha_confirmation": "NewPassw0rd!"
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Senha alterada com sucesso"
}
```

## 📄 Document (Laudo) Management Endpoints

### List Documents
Get paginated list of documents accessible to the user.

```http
GET /api/laudos
Authorization: Bearer YOUR_TOKEN
```

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15)

**Success Response (200):**
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
                    "primeiro_nome": "John",
                    "segundo_nome": "Doe"
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

### Create Document (Admin Only)
Create new document with PDF file upload.

```http
POST /api/laudos
Authorization: Bearer ADMIN_TOKEN
Content-Type: multipart/form-data

{
    "usuario_id": "01HXXXXX...",
    "titulo": "Exame de Sangue",  
    "descricao": "Resultado do exame laboratorial",
    "arquivo": [PDF_FILE]
}
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "Laudo criado com sucesso",
    "data": {
        "id": "01HXXXXX...",
        "titulo": "Exame de Sangue",
        "descricao": "Resultado do exame laboratorial", 
        "url_arquivo": "laudos/2024/10/unique-filename.pdf",
        "usuario_id": "01HYYYY...",
        "criado_em": "2025-10-09T10:30:00Z"
    }
}
```

### Search Documents
Search documents by title or description.

```http
GET /api/laudos/buscar?busca=exame
Authorization: Bearer YOUR_TOKEN
```

**Query Parameters:**
- `busca` - Search term (searches title and description)

**Success Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": "01HXXXXX...",
            "titulo": "Exame de Sangue",
            "descricao": "Resultado do exame laboratorial",
            "usuario": {
                "primeiro_nome": "John",
                "segundo_nome": "Doe"
            },
            "criado_em": "2025-10-09T10:30:00Z"
        }
    ]
}
```

### Download Document
Download the PDF file for a specific document.

```http
GET /api/laudos/{id}/download
Authorization: Bearer YOUR_TOKEN
```

**Success Response (200):**
Returns the PDF file with appropriate headers for download.

### Public Document Consultation  
Public endpoint to view specific document (no authentication required).

```http
GET /api/laudos/consultar/{id}
```

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "id": "01HXXXXX...",
        "titulo": "Exame de Sangue",
        "descricao": "Resultado do exame laboratorial",
        "usuario": {
            "primeiro_nome": "John", 
            "segundo_nome": "Doe"
        },
        "criado_em": "2025-10-09T10:30:00Z"
    }
}
```

## ⚠️ Error Responses

### Authentication Errors
```json
// 401 Unauthorized
{
    "success": false,
    "message": "Token não fornecido"
}

// 401 Invalid Token  
{
    "success": false,
    "message": "Token inválido"
}
```

### Authorization Errors
```json
// 403 Forbidden
{
    "success": false, 
    "message": "Acesso negado. Permissão insuficiente."
}
```

### Validation Errors
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

### Server Errors
```json
// 500 Internal Server Error
{
    "success": false,
    "message": "Erro interno do servidor"
}
```

## 🧪 Testing the API

### Using cURL

```bash
# Login
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@pharmedice.com","senha":"admin123"}' \
  | jq -r '.data.access_token')

# Use token for authenticated requests
curl -X GET http://localhost:8000/api/laudos \
  -H "Authorization: Bearer $TOKEN"
```

### Using Postman
1. Import the API collection (if available)
2. Set up environment variables for base URL and tokens
3. Use the authentication endpoints to obtain tokens
4. Test other endpoints with proper authorization headers

## 📝 Rate Limiting

Some endpoints may have rate limiting applied:
- **Login attempts**: 5 per minute per IP
- **Email verification resend**: 3 per minute per user
- **Password reset**: 3 per hour per email

## 🔒 Security Considerations

- Always use HTTPS in production
- JWT tokens should be stored securely on the client
- Implement proper CORS configuration
- Validate all file uploads
- Use environment variables for sensitive configuration
- Monitor for suspicious activity and implement logging

---

For more detailed information, check the specific endpoint documentation files in this directory.