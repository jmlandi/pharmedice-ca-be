# Frontend Integration Guide

## Overview

This document provides comprehensive information for frontend developers to integrate with the Pharmedice Customer Area backend API. The backend is built with Laravel 12 and provides JWT-based authentication for managing medical reports (laudos).

## Architecture

- **Backend Framework**: Laravel 12
- **Database**: PostgreSQL
- **File Storage**: AWS S3
- **Authentication**: JWT (JSON Web Tokens)
- **API Format**: RESTful JSON API

## Base Configuration

### API Base URL
```
http://localhost:8000/api/
```

### Headers Required
```javascript
{
  'Content-Type': 'application/json',
  'Accept': 'application/json',
  'Authorization': 'Bearer <jwt_token>' // For authenticated routes
}
```

## Authentication

### Login Endpoint
```
POST /api/auth/login
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "senha": "password123"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Login realizado com sucesso",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600,
    "user": {
      "id": "01k74vnbvs5nntym592rhyrq44",
      "primeiro_nome": "John",
      "segundo_nome": "Doe",
      "email": "user@example.com",
      "tipo_usuario": "administrador", // or "usuario"
      "is_admin": true
    }
  }
}
```

### User Registration (Public - No Authentication Required)
```
POST /api/auth/registrar
```

**Important**: This is a public endpoint - no authentication required. Anyone can create an account.

**Request Body:**
```json
{
  "primeiro_nome": "Maria",
  "segundo_nome": "Santos",
  "apelido": "Maria",
  "email": "maria.santos@example.com",
  "senha": "SecurePass@123",
  "senha_confirmation": "SecurePass@123",
  "confirmacao_senha": "SecurePass@123",
  "telefone": "(11) 99999-9999",
  "numero_documento": "12345678901", // CPF with 11 digits
  "data_nascimento": "1990-01-01",
  "aceite_comunicacoes_email": true,
  "aceite_comunicacoes_sms": false,
  "aceite_comunicacoes_whatsapp": true,
  "aceite_termos_uso": true, // Required
  "aceite_politica_privacidade": true // Required
}
```

**Password Requirements:**
- At least 1 lowercase letter
- At least 1 uppercase letter  
- At least 1 number
- At least 1 special character (@$!%*?&)

**Response (Success):**
```json
{
  "sucesso": true,
  "mensagem": "Usuário registrado com sucesso! Verifique seu email para ativar a conta.",
  "dados": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600,
    "usuario": {
      "id": "01k77ajhf8c2e4n2dwq4pfqw79",
      "primeiro_nome": "Maria",
      "segundo_nome": "Santos",
      "email": "maria.santos@example.com",
      "tipo_usuario": "usuario",
      "email_verificado": false,
      "criado_em": "2025-10-10T15:07:04.000000Z"
    },
    "mensagem_verificacao": "Um email de verificação foi enviado para maria.santos@example.com"
  }
}
```

### Other Auth Endpoints
- `POST /api/auth/logout` - Logout user
- `POST /api/auth/refresh` - Refresh JWT token
- `GET /api/auth/me` - Get current user info

## Medical Reports (Laudos) Management

### List Reports
```
GET /api/laudos
```

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "01k7771hv7c5rfsf25wmqkcws6",
        "usuario_id": "01k74vnbvs5nntym592rhyrq44",
        "titulo": "Complete Blood Count Report",
        "descricao": "Complete blood analysis results",
        "url_arquivo": "laudos/2025/10/report_file.pdf",
        "ativo": true,
        "created_at": "2025-10-10T14:05:21.000000Z",
        "updated_at": "2025-10-10T14:05:21.000000Z",
        "usuario": {
          "id": "01k74vnbvs5nntym592rhyrq44",
          "primeiro_nome": "Admin",
          "segundo_nome": "System",
          "email": "admin@pharmedice.com",
          "tipo_usuario": "administrador"
        }
      }
    ],
    "first_page_url": "http://127.0.0.1:8000/api/laudos?page=1",
    "from": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

### Search Reports
```
GET /api/laudos/buscar?busca=<search_term>
```

**Query Parameters:**
- `busca` (required): Search term to find in title or description

### Upload Report (Admin Only)
```
POST /api/laudos
```

**Request (Form Data):**
```javascript
const formData = new FormData();
formData.append('arquivo', file); // PDF file
formData.append('titulo', 'Report Title');
formData.append('descricao', 'Report description');
```

**Headers:**
```javascript
{
  'Accept': 'application/json',
  'Authorization': 'Bearer <admin_jwt_token>'
  // Don't set Content-Type for FormData
}
```

### Download Report
```
GET /api/laudos/{id}/download
```

### Get Single Report
```
GET /api/laudos/{id}
```

### Update Report (Admin Only)
```
PUT /api/laudos/{id}
```

### Delete Report (Admin Only)
```
DELETE /api/laudos/{id}
```

## User Creation - Two Different Approaches

### 1. Public User Registration (No Authentication Required)
Use `POST /api/auth/registrar` when:
- Users are registering themselves
- No authentication required
- Creates regular users only (`tipo_usuario: "usuario"`)
- Includes email verification process

### 2. Admin User Creation (Authentication Required)
Use `POST /api/usuarios` when:
- Admin is creating users administratively
- Requires admin authentication
- Can create both regular users and admins
- No email verification required (admin-created accounts are automatically active)

## User Management (Admin Only)

### List Users
```
GET /api/usuarios
```

### Create User (Admin Only - Requires Authentication)
```
POST /api/usuarios
```

**Important**: This endpoint requires admin authentication. For public user registration, use `POST /api/auth/registrar`.

**Request Body:**
```json
{
  "primeiro_nome": "John",
  "segundo_nome": "Doe",
  "apelido": "Johnny",
  "email": "user@example.com",
  "senha": "SecurePass@123",
  "telefone": "(11) 99999-9999",
  "numero_documento": "12345678901",
  "data_nascimento": "1990-01-01",
  "tipo_usuario": "usuario", // "usuario" or "administrador"
  "aceite_comunicacoes_email": true,
  "aceite_comunicacoes_sms": false,
  "aceite_comunicacoes_whatsapp": true
}
```

### Get User
```
GET /api/usuarios/{id}
```

### Update User
```
PUT /api/usuarios/{id}
```

### Delete User
```
DELETE /api/usuarios/{id}
```

### Change Password (Any authenticated user)
```
PUT /api/usuarios/alterar-senha
```

**Request Body:**
```json
{
  "senha_atual": "current_password",
  "nova_senha": "new_password",
  "nova_senha_confirmation": "new_password"
}
```

## Test Users

For development and testing purposes:

### Administrator
- **Email**: `admin@pharmedice.com`
- **Password**: `admin123`
- **Permissions**: Can upload, edit, delete reports and manage users

### Regular User
- **Email**: `joao@exemplo.com`
- **Password**: `123456`
- **Permissions**: Can view and search reports only

## Error Handling

### Error Response Format
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "campo": ["Validation error message"]
  }
}
```

### Common HTTP Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request (validation errors)
- `401` - Unauthorized (invalid/missing token)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found
- `500` - Internal Server Error

## JWT Token Management

### Token Storage
Store the JWT token securely in your frontend application (localStorage, sessionStorage, or HTTP-only cookies).

### Token Expiration
Tokens expire after 1 hour (3600 seconds). Use the refresh endpoint to get a new token before expiration.

### Token Validation
Include the token in the Authorization header for all protected routes:
```javascript
headers: {
  'Authorization': `Bearer ${token}`
}
```

## File Handling

### Upload Requirements
- **File Type**: PDF only
- **File Size**: Check backend configuration for limits
- **Storage**: Files are stored in AWS S3

### Download Process
1. Call the download endpoint
2. Backend returns a pre-signed S3 URL or streams the file
3. Handle the response based on implementation

## Frontend Implementation Examples

### JavaScript/Fetch API
```javascript
// Login
async function login(email, senha) {
  const response = await fetch('http://localhost:8000/api/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({ email, senha })
  });
  
  const data = await response.json();
  if (data.success) {
    localStorage.setItem('token', data.data.access_token);
    return data.data.user;
  }
  throw new Error(data.message);
}

// Get Reports
async function getReports() {
  const token = localStorage.getItem('token');
  const response = await fetch('http://localhost:8000/api/laudos', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}

// Upload Report
async function uploadReport(file, titulo, descricao) {
  const token = localStorage.getItem('token');
  const formData = new FormData();
  formData.append('arquivo', file);
  formData.append('titulo', titulo);
  formData.append('descricao', descricao);
  
  const response = await fetch('http://localhost:8000/api/laudos', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    },
    body: formData
  });
  
  return await response.json();
}
```

### React/Axios Example
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api/',
  headers: {
    'Accept': 'application/json'
  }
});

// Add token to requests
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle token expiration
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      // Token expired or invalid
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);
```

## CORS Configuration

The backend should be configured to allow requests from your frontend domain. If you encounter CORS issues, ensure the backend's CORS configuration includes your frontend URL.

## Security Considerations

1. **Token Storage**: Store JWT tokens securely
2. **HTTPS**: Use HTTPS in production
3. **Input Validation**: Always validate user input on the frontend
4. **File Upload**: Validate file types and sizes before upload
5. **Error Handling**: Don't expose sensitive information in error messages

## Development Setup

1. Start the Laravel development server:
   ```bash
   php artisan serve --host=127.0.0.1 --port=8000
   ```

2. The API will be available at `http://127.0.0.1:8000/api/`

3. Use the test users provided above for development

## Support

For additional information or issues with the API integration, refer to the API documentation or contact the backend development team.