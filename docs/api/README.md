# Pharmedice - Área do Cliente (Backend)

API Laravel para o sistema de área do cliente da Pharmedice, com autenticação JWT e integração com AWS S3 para armazenamento de laudos em PDF.

## 🚀 Tecnologias

- **Laravel 11** - Framework PHP
- **PostgreSQL** - Banco de dados
- **JWT Auth** - Autenticação via tokens JWT
- **AWS S3** - Armazenamento de arquivos PDF
- **Docker** (opcional) - Containerização

## 📋 Funcionalidades

- ✅ Autenticação JWT (Login/Logout/Refresh Token)
- ✅ Gestão de usuários (administradores e clientes)
- ✅ Upload, visualização e download de laudos PDF
- ✅ Controle de acesso baseado em roles
- ✅ Integração com AWS S3 para armazenamento seguro
- ✅ API RESTful com respostas padronizadas

## 🛠️ Instalação e Configuração

### Pré-requisitos

- PHP 8.2+
- Composer
- PostgreSQL
- Conta AWS com S3 configurado

### 1. Clonar o repositório

```bash
git clone <repository-url>
cd customer-area-be
```

### 2. Instalar dependências

```bash
composer install
```

### 3. Configurar ambiente

```bash
cp .env.example .env
```

### 4. Configurar variáveis de ambiente

Edite o arquivo `.env` com suas configurações:

```env
# Aplicação
APP_NAME="Pharmedice Customer Area"
APP_URL=http://localhost:8000

# Banco de dados PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pharmedice_customer_area
DB_USERNAME=postgres
DB_PASSWORD=sua_senha

# AWS S3
AWS_ACCESS_KEY_ID=sua_access_key
AWS_SECRET_ACCESS_KEY=sua_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=pharmedice-laudos

# JWT
JWT_SECRET=sua_jwt_secret
```

### 5. Gerar chave da aplicação e JWT

```bash
php artisan key:generate
php artisan jwt:secret
```

### 6. Executar migrations e seeders

```bash
php artisan migrate
php artisan db:seed
```

### 7. Iniciar o servidor

```bash
php artisan serve
```

A API estará disponível em `http://localhost:8000`

## 🔑 Usuários Padrão

Após executar os seeders, os seguintes usuários estarão disponíveis:

- **Administrador**: `admin@pharmedice.com` / `admin123`
- **Cliente**: `joao@exemplo.com` / `123456`

## 📚 Documentação da API

### Base URL
```
http://localhost:8000/api
```

### Headers Padrão
```json
{
  "Content-Type": "application/json",
  "Accept": "application/json",
  "Authorization": "Bearer {token}" // Para rotas autenticadas
}
```

### Respostas Padrão

#### Sucesso
```json
{
  "success": true,
  "message": "Mensagem de sucesso",
  "data": {...}
}
```

#### Erro
```json
{
  "success": false,
  "message": "Mensagem de erro",
  "errors": {...} // Opcional, para erros de validação
}
```

### 🔐 Autenticação

#### POST /auth/login
Fazer login no sistema.

**Payload:**
```json
{
  "email": "admin@pharmedice.com",
  "senha": "admin123"
}
```

**Resposta:**
```json
{
  "success": true,
  "message": "Login realizado com sucesso",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600,
    "usuario": {
      "id": "01234567-89ab-cdef-0123-456789abcdef",
      "nome_completo": "Admin Sistema",
      "email": "admin@pharmedice.com",
      "tipo_usuario": "administrador",
      "is_admin": true
    }
  }
}
```

#### POST /auth/logout
Fazer logout (invalidar token).

#### POST /auth/refresh
Renovar token JWT.

#### GET /auth/me
Obter dados do usuário logado.

### 👥 Usuários (Admin apenas)

#### GET /usuarios
Listar usuários com paginação e filtros.

**Query Parameters:**
- `per_page` (opcional): Itens por página (padrão: 15)
- `tipo_usuario` (opcional): Filtrar por tipo (administrador/usuario)
- `email` (opcional): Filtrar por email
- `nome` (opcional): Filtrar por nome

#### POST /usuarios
Criar novo usuário.

**Payload:**
```json
{
  "primeiro_nome": "João",
  "segundo_nome": "Silva",
  "apelido": "João",
  "email": "joao@exemplo.com",
  "senha": "123456",
  "telefone": "(11) 99999-9999",
  "numero_documento": "123.456.789-00",
  "data_nascimento": "1990-01-01",
  "tipo_usuario": "usuario",
  "aceite_comunicacoes_email": true,
  "aceite_comunicacoes_sms": false,
  "aceite_comunicacoes_whatsapp": true
}
```

#### GET /usuarios/{id}
Obter dados de um usuário específico.

#### PUT /usuarios/{id}
Atualizar dados de um usuário.

#### DELETE /usuarios/{id}
Remover usuário (soft delete).

#### PUT /usuarios/alterar-senha
Alterar própria senha (qualquer usuário autenticado).

**Payload:**
```json
{
  "senha_atual": "senha123",
  "nova_senha": "novaSenha123",
  "nova_senha_confirmation": "novaSenha123"
}
```

### 📄 Laudos

#### GET /laudos
Listar todos os laudos (qualquer usuário autenticado pode acessar).

**Query Parameters:**
- `per_page` (opcional): Itens por página (padrão: 15)
- `usuario_id` (opcional): Filtrar por usuário específico
- `titulo` (opcional): Filtrar por título
- `nome_arquivo` (opcional): Filtrar por nome do arquivo
- `busca` (opcional): Busca geral em título e nome do arquivo
- `data_inicio` (opcional): Filtrar por data (YYYY-MM-DD)
- `data_fim` (opcional): Filtrar por data (YYYY-MM-DD)

#### GET /laudos/meus-laudos
Listar laudos do usuário logado.

#### GET /laudos/buscar
Buscar laudos por termo de busca (título ou nome do arquivo).

**Query Parameters:**
- `busca` (obrigatório): Termo de busca (mínimo 2 caracteres)
- `per_page` (opcional): Itens por página (padrão: 15)
- `usuario_id` (opcional): Filtrar por usuário específico
- `data_inicio` (opcional): Filtrar por data (YYYY-MM-DD)
- `data_fim` (opcional): Filtrar por data (YYYY-MM-DD)

**Resposta:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [...],
    "total": 25
  },
  "meta": {
    "termo_busca": "exame",
    "total_encontrado": 25
  }
}
```

#### POST /laudos (Admin apenas)
Criar novo laudo com upload de PDF.

**Payload (multipart/form-data):**
```
usuario_id: 01234567-89ab-cdef-0123-456789abcdef  # ID do usuário CRIADOR (opcional, padrão: usuário logado)
titulo: Laudo de Exame
descricao: Descrição do laudo
arquivo: [arquivo PDF]
```

**Importante**: O `usuario_id` representa quem está **criando/enviando** o laudo, não necessariamente o paciente do laudo.

#### GET /laudos/{id}
Obter dados de um laudo específico.

#### PUT /laudos/{id} (Admin apenas)
Atualizar laudo.

#### DELETE /laudos/{id} (Admin apenas)
Remover laudo.

#### GET /laudos/{id}/download
Obter URL temporária para download do PDF.

**Resposta:**
```json
{
  "success": true,
  "data": {
    "url": "https://bucket.s3.region.amazonaws.com/path/file.pdf?signature=...",
    "nome_arquivo": "laudo.pdf",
    "titulo": "Laudo de Exame"
  }
}
```

## 🗄️ Estrutura do Banco de Dados

### Tabela: usuarios
- `id` (ULID, PK)
- `primeiro_nome` (string)
- `segundo_nome` (string)
- `apelido` (string)
- `email` (string, único)
- `senha` (string, hash)
- `telefone` (string)
- `numero_documento` (string, único)
- `data_nascimento` (date)
- `tipo_usuario` (enum: administrador, usuario)
- `aceite_comunicacoes_email` (boolean)
- `aceite_comunicacoes_sms` (boolean)
- `aceite_comunicacoes_whatsapp` (boolean)
- `ativo` (boolean)
- `created_at`, `updated_at` (timestamps)

### Tabela: laudos
- `id` (ULID, PK)
- `usuario_id` (ULID, FK para usuarios) - **ID do usuário que CRIOU o laudo**
- `titulo` (string)
- `descricao` (text, opcional)
- `url_arquivo` (string, caminho no S3)
- `ativo` (boolean)
- `created_at`, `updated_at` (timestamps)

### Tabela: permissoes
- `id` (ULID, PK)
- `nome` (string)
- `descricao` (string, opcional)
- `permissao_admin` (boolean)
- `ativo` (boolean)
- `created_at`, `updated_at` (timestamps)

### Tabela: permissoes_de_usuario
- `id` (ULID, PK)
- `usuario_id` (ULID, FK)
- `permissao_id` (ULID, FK)
- `created_at`, `updated_at` (timestamps)

## 🔧 Comandos Úteis

```bash
# Limpar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Executar migrations
php artisan migrate

# Executar seeders
php artisan db:seed

# Gerar nova secret JWT
php artisan jwt:secret

# Executar testes
php artisan test
```

## 📦 Estrutura de Arquivos

```
app/
├── DTOs/           # Data Transfer Objects
├── Http/
│   ├── Controllers/ # Controllers da API
│   └── Middleware/  # Middlewares customizados
├── Models/         # Eloquent Models
└── Services/       # Lógica de negócio

database/
├── migrations/     # Migrations do banco
└── seeders/       # Seeders para popular dados iniciais

routes/
└── api.php        # Rotas da API
```

## 🤝 Contribuição

1. Faça fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.