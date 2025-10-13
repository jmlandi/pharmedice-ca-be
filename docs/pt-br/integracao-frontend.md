# Guia de Integração com Frontend

## Visão Geral

Este documento fornece informações abrangentes para desenvolvedores frontend integrarem com a API backend da Área do Cliente Pharmedice. O backend é construído com Laravel 12 e fornece autenticação baseada em JWT para gerenciamento de laudos médicos.

## Arquitetura

- **Framework Backend**: Laravel 12
- **Banco de Dados**: PostgreSQL
- **Armazenamento de Arquivos**: AWS S3
- **Autenticação**: JWT (JSON Web Tokens)
- **Formato da API**: API JSON RESTful

## Configuração Base

### URL Base da API
```
http://localhost:8000/api/
```

### Headers Obrigatórios
```javascript
{
  'Content-Type': 'application/json',
  'Accept': 'application/json',
  'Authorization': 'Bearer <jwt_token>' // Para rotas autenticadas
}
```

## Autenticação

### Endpoint de Login
```
POST /api/auth/login
```

**Corpo da Requisição:**
```json
{
  "email": "usuario@exemplo.com",
  "senha": "senha123"
}
```

**Resposta (Sucesso):**
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
      "primeiro_nome": "João",
      "segundo_nome": "Silva",
      "email": "usuario@exemplo.com",
      "tipo_usuario": "administrador", // ou "usuario"
      "is_admin": true
    }
  }
}
```

### Registro de Usuário (Público - Sem Autenticação Necessária)
```
POST /api/auth/registrar
```

**Importante**: Este é um endpoint público - não requer autenticação. Qualquer pessoa pode criar uma conta.

**Corpo da Requisição:**
```json
{
  "primeiro_nome": "Maria",
  "segundo_nome": "Santos",
  "apelido": "Maria",
  "email": "maria.santos@exemplo.com",
  "senha": "MinhaSenh@123",
  "senha_confirmation": "MinhaSenh@123",
  "confirmacao_senha": "MinhaSenh@123",
  "telefone": "(11) 99999-9999",
  "numero_documento": "12345678901", // CPF com 11 dígitos
  "data_nascimento": "1990-01-01",
  "aceite_comunicacoes_email": true,
  "aceite_comunicacoes_sms": false,
  "aceite_comunicacoes_whatsapp": true,
  "aceite_termos_uso": true, // Obrigatório
  "aceite_politica_privacidade": true // Obrigatório
}
```

**Requisitos da Senha:**
- Pelo menos 1 letra minúscula
- Pelo menos 1 letra maiúscula  
- Pelo menos 1 número
- Pelo menos 1 caractere especial (@$!%*?&)

**Resposta (Sucesso):**
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
      "email": "maria.santos@exemplo.com",
      "tipo_usuario": "usuario",
      "email_verificado": false,
      "criado_em": "2025-10-10T15:07:04.000000Z"
    },
    "mensagem_verificacao": "Um email de verificação foi enviado para maria.santos@exemplo.com"
  }
}
```

### Outros Endpoints de Autenticação
- `POST /api/auth/logout` - Logout do usuário
- `POST /api/auth/refresh` - Renovar token JWT
- `GET /api/auth/me` - Obter informações do usuário atual

## Gerenciamento de Laudos Médicos

### Listar Laudos
```
GET /api/laudos
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "01k7771hv7c5rfsf25wmqkcws6",
        "usuario_id": "01k74vnbvs5nntym592rhyrq44",
        "titulo": "Laudo de Hemograma Completo",
        "descricao": "Resultado de exame de sangue completo",
        "url_arquivo": "laudos/2025/10/arquivo_laudo.pdf",
        "ativo": true,
        "created_at": "2025-10-10T14:05:21.000000Z",
        "updated_at": "2025-10-10T14:05:21.000000Z",
        "usuario": {
          "id": "01k74vnbvs5nntym592rhyrq44",
          "primeiro_nome": "Admin",
          "segundo_nome": "Sistema",
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

### Buscar Laudos
```
GET /api/laudos/buscar?busca=<termo_busca>
```

**Parâmetros de Query:**
- `busca` (obrigatório): Termo de busca para encontrar no título ou descrição

### Upload de Laudo (Apenas Admin)
```
POST /api/laudos
```

**Requisição (Form Data):**
```javascript
const formData = new FormData();
formData.append('arquivo', file); // Arquivo PDF
formData.append('titulo', 'Título do Laudo');
formData.append('descricao', 'Descrição do laudo');
```

**Headers:**
```javascript
{
  'Accept': 'application/json',
  'Authorization': 'Bearer <admin_jwt_token>'
  // Não definir Content-Type para FormData
}
```

### Download de Laudo
```
GET /api/laudos/{id}/download
```

### Obter Laudo Individual
```
GET /api/laudos/{id}
```

### Atualizar Laudo (Apenas Admin)
```
PUT /api/laudos/{id}
```

### Excluir Laudo (Apenas Admin)
```
DELETE /api/laudos/{id}
```

## Criação de Usuários - Duas Abordagens Diferentes

### 1. Registro Público de Usuário (Sem Autenticação Necessária)
Use `POST /api/auth/registrar` quando:
- Usuários estão se registrando
- Não requer autenticação
- Cria apenas usuários regulares (`tipo_usuario: "usuario"`)
- Inclui processo de verificação de email

### 2. Criação de Usuário pelo Admin (Autenticação Necessária)
Use `POST /api/usuarios` quando:
- Admin está criando usuários administrativamente
- Requer autenticação de admin
- Pode criar tanto usuários regulares quanto admins
- Não requer verificação de email (contas criadas pelo admin são automaticamente ativas)

## Gerenciamento de Usuários (Apenas Admin)

### Listar Usuários
```
GET /api/usuarios
```

### Criar Usuário (Apenas Admin - Requer Autenticação)
```
POST /api/usuarios
```

**Importante**: Este endpoint requer autenticação de admin. Para registro público de usuários, use `POST /api/auth/registrar`.

**Corpo da Requisição:**
```json
{
  "primeiro_nome": "João",
  "segundo_nome": "Silva",
  "apelido": "João",
  "email": "usuario@exemplo.com",
  "senha": "MinhaSenh@123",
  "telefone": "(11) 99999-9999",
  "numero_documento": "12345678901",
  "data_nascimento": "1990-01-01",
  "tipo_usuario": "usuario", // "usuario" ou "administrador"
  "aceite_comunicacoes_email": true,
  "aceite_comunicacoes_sms": false,
  "aceite_comunicacoes_whatsapp": true
}
```

### Obter Usuário
```
GET /api/usuarios/{id}
```

### Atualizar Usuário
```
PUT /api/usuarios/{id}
```

### Excluir Usuário
```
DELETE /api/usuarios/{id}
```

### Alterar Senha (Qualquer usuário autenticado)
```
PUT /api/usuarios/alterar-senha
```

**Corpo da Requisição:**
```json
{
  "senha_atual": "senha_atual",
  "nova_senha": "nova_senha",
  "nova_senha_confirmation": "nova_senha"
}
```

## Usuários de Teste

Para desenvolvimento e testes:

### Administrador
- **Email**: `admin@pharmedice.com`
- **Senha**: `admin123`
- **Permissões**: Pode fazer upload, editar, excluir laudos e gerenciar usuários

### Usuário Regular
- **Email**: `joao@exemplo.com`
- **Senha**: `123456`
- **Permissões**: Pode apenas visualizar e buscar laudos

## Tratamento de Erros

### Formato de Resposta de Erro
```json
{
  "success": false,
  "message": "Descrição do erro",
  "errors": {
    "campo": ["Mensagem de erro de validação"]
  }
}
```

### Códigos de Status HTTP Comuns
- `200` - Sucesso
- `201` - Criado
- `400` - Requisição Inválida (erros de validação)
- `401` - Não Autorizado (token inválido/ausente)
- `403` - Proibido (permissões insuficientes)
- `404` - Não Encontrado
- `500` - Erro Interno do Servidor

## Gerenciamento de Token JWT

### Armazenamento de Token
Armazene o token JWT de forma segura na sua aplicação frontend (localStorage, sessionStorage, ou cookies HTTP-only).

### Expiração do Token
Tokens expiram após 1 hora (3600 segundos). Use o endpoint de refresh para obter um novo token antes da expiração.

### Validação do Token
Inclua o token no header Authorization para todas as rotas protegidas:
```javascript
headers: {
  'Authorization': `Bearer ${token}`
}
```

## Manipulação de Arquivos

### Requisitos de Upload
- **Tipo de Arquivo**: Apenas PDF
- **Tamanho do Arquivo**: Verifique a configuração do backend para limites
- **Armazenamento**: Arquivos são armazenados no AWS S3

### Processo de Download
1. Chame o endpoint de download
2. Backend retorna uma URL pré-assinada do S3 ou faz stream do arquivo
3. Trate a resposta baseada na implementação

## Exemplos de Implementação Frontend

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

// Obter Laudos
async function getLaudos() {
  const token = localStorage.getItem('token');
  const response = await fetch('http://localhost:8000/api/laudos', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}

// Upload de Laudo
async function uploadLaudo(file, titulo, descricao) {
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

### Exemplo React/Axios
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api/',
  headers: {
    'Accept': 'application/json'
  }
});

// Adicionar token às requisições
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Tratar expiração do token
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      // Token expirado ou inválido
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);
```

## Configuração CORS

O backend deve estar configurado para permitir requisições do domínio do seu frontend. Se encontrar problemas de CORS, certifique-se de que a configuração CORS do backend inclui a URL do seu frontend.

## Considerações de Segurança

1. **Armazenamento de Token**: Armazene tokens JWT de forma segura
2. **HTTPS**: Use HTTPS em produção
3. **Validação de Input**: Sempre valide entrada do usuário no frontend
4. **Upload de Arquivo**: Valide tipos e tamanhos de arquivo antes do upload
5. **Tratamento de Erro**: Não exponha informações sensíveis em mensagens de erro

## Configuração de Desenvolvimento

1. Inicie o servidor de desenvolvimento Laravel:
   ```bash
   php artisan serve --host=127.0.0.1 --port=8000
   ```

2. A API estará disponível em `http://127.0.0.1:8000/api/`

3. Use os usuários de teste fornecidos acima para desenvolvimento

## Fluxo de Trabalho Recomendado

### Para Administradores
1. Login com credenciais de admin
2. Upload de laudos através da interface de admin
3. Gerenciamento de usuários
4. Visualização e busca de todos os laudos

### Para Usuários Regulares
1. Login com credenciais de usuário
2. Visualização da lista de laudos disponíveis
3. Busca por laudos específicos
4. Download de laudos quando necessário

## Casos de Uso Comuns

### Busca de Laudos
```javascript
// Buscar por termo específico
async function buscarLaudos(termo) {
  const token = localStorage.getItem('token');
  const response = await fetch(
    `http://localhost:8000/api/laudos/buscar?busca=${encodeURIComponent(termo)}`,
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    }
  );
  
  return await response.json();
}

// Exemplo de uso
const resultados = await buscarLaudos('hemograma');
```

### Upload com Validação
```javascript
async function uploadLaudoComValidacao(file, titulo, descricao) {
  // Validações frontend
  if (!file || file.type !== 'application/pdf') {
    throw new Error('Apenas arquivos PDF são permitidos');
  }
  
  if (file.size > 10 * 1024 * 1024) { // 10MB
    throw new Error('Arquivo muito grande (máximo 10MB)');
  }
  
  if (!titulo.trim() || !descricao.trim()) {
    throw new Error('Título e descrição são obrigatórios');
  }
  
  return await uploadLaudo(file, titulo, descricao);
}
```

## Suporte

Para informações adicionais ou problemas com a integração da API, consulte a documentação da API ou entre em contato com a equipe de desenvolvimento backend.