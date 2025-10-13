# Guia de Integração com Frontend

## Visão Geral

Este documento fornece informações abrangentes para desenvolvedores frontend integrarem com a API backend da Área do Cliente Pharmedice. O backend é construído com Laravel 11 e fornece autenticação baseada em JWT para gerenciamento de laudos médicos.

## Arquitetura

- **Framework Backend**: Laravel 11
- **Banco de Dados**: PostgreSQL  
- **Armazenamento de Arquivos**: AWS S3
- **Autenticação**: JWT (JSON Web Tokens)
- **Formato da API**: API JSON RESTful
- **Padrão de Resposta**: Português Brasileiro (pt-BR)

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

### Teste de CORS
Para verificar se o CORS está funcionando corretamente:
```
GET /api/test-cors
```

**Resposta:**
```json
{
  "mensagem": "CORS está funcionando!",
  "timestamp": "2025-10-13T15:30:00.000000Z",
  "origin": "http://localhost:3000"
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
  "sucesso": true,
  "mensagem": "Login realizado com sucesso",
  "dados": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600,
    "usuario": {
      "id": "01k74vnbvs5nntym592rhyrq44",
      "primeiro_nome": "João",
      "segundo_nome": "Silva",
      "email": "usuario@exemplo.com",
      "tipo_usuario": "administrador", // ou "usuario"
      "is_admin": true,
      "email_verificado": true
    }
  }
}
```

### Registro de Usuário (Público - Sem Autenticação Necessária)
```
POST /api/auth/registrar-usuario
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
- Mínimo 8 caracteres
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

### Obter Dados do Usuário Atual
```
GET /api/auth/me
```

**Resposta (Sucesso):**
```json
{
  "sucesso": true,
  "dados": {
    "id": "01k74vnbvs5nntym592rhyrq44",
    "nome_completo": "João Silva",
    "primeiro_nome": "João",
    "segundo_nome": "Silva",
    "apelido": "joao",
    "email": "joao@exemplo.com",
    "telefone": "(11) 99999-9999",
    "numero_documento": "12345678901",
    "data_nascimento": "1990-01-01",
    "tipo_usuario": "usuario",
    "is_admin": false,
    "email_verificado": true,
    "email_verificado_em": "2025-10-13 15:30:00",
    "aceite_comunicacoes_email": true,
    "aceite_comunicacoes_sms": false,
    "aceite_comunicacoes_whatsapp": true,
    "ativo": true
  }
}
```

### Outros Endpoints de Autenticação
- `POST /api/auth/logout` - Logout do usuário
- `POST /api/auth/refresh` - Renovar token JWT
- `POST /api/auth/registrar-admin` - Registrar administrador (público)
- `POST /api/auth/reenviar-verificacao-email` - Reenviar email de verificação (autenticado)
- `POST /api/auth/reenviar-verificacao-email-publico` - Reenviar email de verificação (público)
- `GET /api/auth/verificar-email/{id}/{hash}` - Verificar email via link

## Processo de Verificação de Email

### Fluxo de Verificação
1. Usuário se registra via `POST /api/auth/registrar-usuario` ou `POST /api/auth/registrar-admin`
2. Sistema envia email de verificação automaticamente
3. Usuário clica no link no email
4. Sistema verifica o email e ativa a conta

### Reenviar Email de Verificação (Usuário Autenticado)
```
POST /api/auth/reenviar-verificacao-email
```

### Reenviar Email de Verificação (Público)
```
POST /api/auth/reenviar-verificacao-email-publico
```

**Corpo da Requisição:**
```json
{
  "email": "usuario@exemplo.com"
}
```

**Resposta (Sucesso):**
```json
{
  "sucesso": true,
  "mensagem": "Email de verificação reenviado para usuario@exemplo.com"
}
```

### Tratamento de Login com Email Não Verificado
Quando um usuário tenta fazer login sem ter verificado o email:

**Resposta de Erro:**
```json
{
  "sucesso": false,
  "mensagem": "Email não verificado. Verifique sua caixa de entrada e clique no link de verificação enviado no momento do cadastro."
}
```

**Código de Status:** `403 Forbidden`

## Gerenciamento de Laudos Médicos

### Listar Laudos
```
GET /api/laudos
```

**Resposta:**
```json
{
  "sucesso": true,
  "dados": {
    "current_page": 1,
    "data": [
      {
        "id": "01k7771hv7c5rfsf25wmqkcws6",
        "titulo": "Laudo de Hemograma Completo",
        "descricao": "Resultado de exame de sangue completo",
        "nome_arquivo": "arquivo_laudo.pdf",
        "url_arquivo": "laudos/2025/10/arquivo_laudo.pdf",
        "ativo": true,
        "created_at": "2025-10-10T14:05:21.000000Z",
        "updated_at": "2025-10-10T14:05:21.000000Z"
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

**Nota:** Os laudos não são mais associados a usuários específicos. Qualquer usuário autenticado pode visualizar todos os laudos.

### Buscar Laudos
```
GET /api/laudos/buscar?busca=<termo_busca>
```

**Parâmetros de Query:**
- `busca` (obrigatório): Termo de busca para encontrar no título ou descrição (mínimo 2 caracteres)
- `data_inicio` (opcional): Data de início para filtro por período (formato: Y-m-d)
- `data_fim` (opcional): Data de fim para filtro por período (formato: Y-m-d)
- `per_page` (opcional): Número de itens por página (padrão: 15)

**Resposta:**
```json
{
  "sucesso": true,
  "dados": {
    "current_page": 1,
    "data": [
      // ... laudos encontrados
    ],
    "total": 10
  },
  "meta": {
    "termo_busca": "hemograma",
    "total_encontrado": 10
  }
}
```

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

### Consultar Laudo Publicamente (Sem Autenticação)
```
GET /api/laudos/consultar/{id}
```

**Resposta:**
```json
{
  "sucesso": true,
  "dados": {
    "id": "01k7771hv7c5rfsf25wmqkcws6",
    "titulo": "Laudo de Hemograma Completo",
    "descricao": "Resultado de exame de sangue completo",
    "nome_arquivo": "arquivo_laudo.pdf",
    "created_at": "2025-10-10T14:05:21.000000Z",
    "updated_at": "2025-10-10T14:05:21.000000Z"
  }
}
```

### Meus Laudos (DEPRECATED)
```
GET /api/laudos/meus-laudos
```

**Nota:** Este endpoint está deprecated. Ele redirecionará para a listagem geral de laudos, pois os laudos não são mais associados a usuários específicos.

## Criação de Usuários - Três Abordagens Diferentes

### 1. Registro Público de Usuário (Sem Autenticação)
Use `POST /api/auth/registrar-usuario` quando:
- Usuários estão se registrando
- Não requer autenticação
- Cria apenas usuários regulares (`tipo_usuario: "usuario"`)
- Inclui processo de verificação de email obrigatório

### 2. Registro Público de Admin (Sem Autenticação)
Use `POST /api/auth/registrar-admin` quando:
- Registrando um administrador
- Não requer autenticação
- Cria usuários administradores (`tipo_usuario: "administrador"`)
- Inclui processo de verificação de email obrigatório

### 3. Criação de Usuário pelo Admin (Autenticação Necessária)
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

**Importante**: Este endpoint requer autenticação de admin. Para registro público de usuários, use `POST /api/auth/registrar-usuario`.

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

Para desenvolvimento e testes, execute os seeders:

```bash
php artisan db:seed
```

### Administrador
- **Email**: `admin@pharmedice.com`
- **Senha**: `admin123`
- **Status**: Email verificado
- **Permissões**: Pode fazer upload, editar, excluir laudos e gerenciar usuários

### Usuário Regular
- **Email**: `joao@exemplo.com`
- **Senha**: `123456`  
- **Status**: Email verificado
- **Permissões**: Pode visualizar, buscar e fazer download de laudos

**Nota:** Estes usuários são criados com emails já verificados para facilitar os testes.

## Tratamento de Erros

### Formato de Resposta de Erro
```json
{
  "sucesso": false,
  "mensagem": "Descrição do erro",
  "erros": {
    "campo": ["Mensagem de erro de validação"]
  }
}
```

### Códigos de Status HTTP Comuns
- `200` - Sucesso
- `201` - Criado
- `400` - Requisição Inválida (erros de validação)
- `401` - Não Autorizado (token inválido/ausente/expirado)
- `403` - Proibido (permissões insuficientes, usuário inativo, email não verificado)
- `404` - Não Encontrado
- `422` - Entidade Não Processável (dados de validação inválidos)
- `500` - Erro Interno do Servidor

### Mensagens de Erro dos Middlewares

**Middleware JWT (`401`):**
- "Token expirado"
- "Token inválido"
- "Token não fornecido"
- "Usuário não encontrado"

**Middleware JWT (`403`):**
- "Usuário inativo"

**Middleware Admin (`403`):**
- "Acesso negado. Apenas administradores podem acessar este recurso."

**Middleware Admin (`401`):**
- "Token inválido ou expirado"

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
  if (data.sucesso) {
    localStorage.setItem('token', data.dados.access_token);
    return data.dados.usuario;
  }
  throw new Error(data.mensagem);
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

// Tratar expiração do token e outros erros
api.interceptors.response.use(
  response => response,
  error => {
    const status = error.response?.status;
    const data = error.response?.data;
    
    if (status === 401) {
      // Token expirado, inválido ou não fornecido
      localStorage.removeItem('token');
      window.location.href = '/login';
    } else if (status === 403) {
      // Usuário inativo, email não verificado ou sem permissões
      if (data?.mensagem?.includes('não verificado')) {
        // Redirecionar para página de verificação de email
        window.location.href = '/verificar-email';
      } else if (data?.mensagem?.includes('inativo')) {
        // Usuário foi desativado
        localStorage.removeItem('token');
        window.location.href = '/login?erro=conta-inativa';
      } else {
        // Sem permissões de admin
        alert('Acesso negado. Apenas administradores podem acessar este recurso.');
      }
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

## Migração para o Novo Padrão de Resposta

Se você possui um frontend existente que usa o padrão anterior em inglês, será necessário atualizar as chaves das respostas:

### Chaves Alteradas
- `success` → `sucesso`
- `message` → `mensagem`
- `data` → `dados`
- `errors` → `erros`
- `user` → `usuario` (apenas no contexto de login)

### Exemplo de Migração JavaScript
```javascript
// Antes
if (response.data.success) {
  const user = response.data.data.user;
  const token = response.data.data.access_token;
}

// Depois
if (response.data.sucesso) {
  const usuario = response.data.dados.usuario;
  const token = response.data.dados.access_token;
}

// Função auxiliar para migração gradual
function normalizeResponse(response) {
  const data = response.data;
  return {
    success: data.sucesso || data.success,
    message: data.mensagem || data.message,
    data: data.dados || data.data,
    errors: data.erros || data.errors,
    // Para compatibilidade com login
    user: data.dados?.usuario || data.data?.user || data.data?.usuario
  };
}
```

## Changelog da API

### Versão Atual (Outubro 2025)
- ✅ Padronização de respostas para português brasileiro
- ✅ Remoção da associação laudo-usuário
- ✅ Implementação de verificação de email obrigatória
- ✅ Melhorias na segurança e validação
- ✅ Endpoint de consulta pública de laudos
- ✅ Middlewares de autenticação e autorização aprimorados

## Suporte

Para informações adicionais ou problemas com a integração da API, consulte a documentação da API ou entre em contato com a equipe de desenvolvimento backend.