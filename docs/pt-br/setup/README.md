# Guia de Instalação - Pharmedice Customer Area Backend

> Guia completo de instalação e configuração para o sistema backend da Área do Cliente Pharmedice.

## 🎯 Pré-requisitos

Antes de começar, certifique-se de ter o seguinte instalado no seu sistema:

### Software Obrigatório
- **PHP 8.2+** com extensões:
  - BCMath PHP Extension
  - Ctype PHP Extension
  - cURL PHP Extension
  - DOM PHP Extension
  - Fileinfo PHP Extension
  - JSON PHP Extension
  - Mbstring PHP Extension
  - OpenSSL PHP Extension
  - PCRE PHP Extension
  - PDO PHP Extension
  - Tokenizer PHP Extension
  - XML PHP Extension
- **Composer** (gerenciador de pacotes PHP)
- **PostgreSQL 12+** (banco de dados)
- **Git** (controle de versão)

### Software Opcional
- **Redis** (para cache e filas)
- **Docker & Docker Compose** (para desenvolvimento containerizado)
- **AWS CLI** (para configuração do S3)

### Serviços na Nuvem
- **AWS S3 Bucket** (para armazenamento de arquivos)
- **Serviço de Email** (SMTP, AWS SES, ou similar)

## ⚡ Instalação Rápida

### 1. Clonar o Repositório
```bash
git clone https://github.com/seu-usuario/pharmedice-customer-area-backend.git
cd pharmedice-customer-area-backend
```

### 2. Instalar Dependências PHP
```bash
composer install
```

### 3. Configuração do Ambiente
```bash
# Copiar arquivo de ambiente
cp .env.example .env

# Gerar chave da aplicação
php artisan key:generate

# Gerar chave secreta JWT
php artisan jwt:secret
```

### 4. Configurar Banco de Dados
Edite o arquivo `.env` com suas credenciais do banco:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pharmedice_customer_area
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

### 5. Executar Migrações do Banco
```bash
# Criar tabelas do banco
php artisan migrate

# Inserir dados de teste (opcional)
php artisan db:seed
```

### 6. Iniciar Servidor de Desenvolvimento
```bash
php artisan serve
```

A API estará disponível em `http://localhost:8000`

## 🔧 Configuração Detalhada

### Variáveis de Ambiente

Crie e configure seu arquivo `.env` com as seguintes configurações:

#### Configurações da Aplicação
```env
APP_NAME="Pharmedice Customer Area"
APP_ENV=local
APP_KEY=base64:sua_chave_gerada_aqui
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000
```

#### Configuração do Banco de Dados
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pharmedice_customer_area
DB_USERNAME=seu_usuario_db
DB_PASSWORD=sua_senha_db
```

#### Autenticação JWT
```env
JWT_SECRET=sua_chave_jwt_aqui
JWT_TTL=60
JWT_REFRESH_TTL=20160
```

#### Configuração de Email

**Para Desenvolvimento (Emails em log):**
```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@pharmedice.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**Para Produção (SMTP):**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=sua-senha-app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@pharmedice.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**Para Produção (AWS SES):**
```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=sua-chave-acesso
AWS_SECRET_ACCESS_KEY=sua-chave-secreta
AWS_DEFAULT_REGION=us-east-1
MAIL_FROM_ADDRESS="noreply@pharmedice.com"
MAIL_FROM_NAME="${APP_NAME}"
```

#### Configuração AWS S3
```env
AWS_ACCESS_KEY_ID=sua_chave_acesso_id
AWS_SECRET_ACCESS_KEY=sua_chave_acesso_secreta
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=pharmedice-documentos-clientes
AWS_USE_PATH_STYLE_ENDPOINT=false
```

#### Configuração de Cache (Opcional)
```env
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### Configuração de Filas (Opcional)
```env
QUEUE_CONNECTION=redis
```

### Configuração AWS S3

#### 1. Criar Bucket S3
1. Faça login no Console AWS
2. Navegue para o serviço S3
3. Crie um novo bucket (ex.: `pharmedice-documentos-clientes`)
4. Configure permissões do bucket para sua aplicação

#### 2. Criar Usuário IAM
1. Navegue para o serviço IAM
2. Crie novo usuário com acesso programático
3. Anexe política com permissões S3:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "s3:GetObject",
                "s3:PutObject",
                "s3:DeleteObject"
            ],
            "Resource": "arn:aws:s3:::pharmedice-documentos-clientes/*"
        },
        {
            "Effect": "Allow",
            "Action": [
                "s3:ListBucket"
            ],
            "Resource": "arn:aws:s3:::pharmedice-documentos-clientes"
        }
    ]
}
```

#### 3. Configurar CORS (se necessário para uploads diretos)
```json
[
    {
        "AllowedHeaders": ["*"],
        "AllowedMethods": ["GET", "PUT", "POST"],
        "AllowedOrigins": ["https://seudominio.com"],
        "ExposeHeaders": ["ETag"]
    }
]
```

## 🐳 Configuração Docker (Opcional)

### Usando Docker Compose

Crie um arquivo `docker-compose.yml`:

```yaml
version: '3.8'
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8000:8000"
    environment:
      - DB_HOST=db
      - DB_DATABASE=pharmedice
      - DB_USERNAME=laravel
      - DB_PASSWORD=secret
    volumes:
      - .:/var/www/html
    depends_on:
      - db

  db:
    image: postgres:15
    environment:
      POSTGRES_DB: pharmedice
      POSTGRES_USER: laravel
      POSTGRES_PASSWORD: secret
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

volumes:
  postgres_data:
```

### Executar com Docker
```bash
# Construir e iniciar containers
docker-compose up -d

# Instalar dependências dentro do container
docker-compose exec app composer install

# Executar migrações
docker-compose exec app php artisan migrate

# Gerar chaves
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan jwt:secret
```

## 🧪 Verificar Instalação

### 1. Testar Conexão com Banco
```bash
php artisan migrate:status
```

### 2. Testar Endpoints da API
```bash
# Testar endpoint de saúde
curl http://localhost:8000/api/health

# Testar endpoint de login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@pharmedice.com","senha":"admin123"}'
```

### 3. Executar Testes
```bash
# Executar todos os testes
php artisan test

# Executar suítes específicas de teste
php artisan test --filter="SignupTest|EmailVerificationTest"
```

### 4. Testar Upload de Arquivo (se S3 configurado)
```bash
# Criar documento de teste (requer autenticação admin)
curl -X POST http://localhost:8000/api/laudos \
  -H "Authorization: Bearer SEU_TOKEN" \
  -F "usuario_id=ID_USUARIO" \
  -F "titulo=Documento Teste" \
  -F "descricao=Upload de teste" \
  -F "arquivo=@/caminho/para/teste.pdf"
```

## 📊 Configuração do Banco de Dados

### Criar Banco de Dados
```sql
-- Conectar ao PostgreSQL como superusuário
CREATE DATABASE pharmedice_customer_area;
CREATE USER pharmedice_user WITH PASSWORD 'sua_senha_segura';
GRANT ALL PRIVILEGES ON DATABASE pharmedice_customer_area TO pharmedice_user;
```

### Executar Migrações
```bash
# Executar todas as migrações
php artisan migrate

# Executar migração específica
php artisan migrate --path=/database/migrations/2025_10_08_173217_usuarios.php

# Reverter migrações (se necessário)
php artisan migrate:rollback
```

### Inserir Dados de Teste
```bash
# Executar todos os seeders
php artisan db:seed

# Executar seeder específico
php artisan db:seed --class=UserSeeder
```

### Usuários de Teste Padrão
Após executar os seeders, você terá estes usuários de teste:

- **Usuário Admin**: `admin@pharmedice.com` / `admin123`
- **Usuário Regular**: `joao@exemplo.com` / `123456`

## 🔐 Configuração de Segurança

### Permissões de Arquivo (Linux/Mac)
```bash
# Definir permissões apropriadas
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Configuração do Servidor Web

#### Apache (.htaccess)
O projeto inclui um arquivo `.htaccess` no diretório `public`.

#### Nginx
```nginx
server {
    listen 80;
    server_name seu-dominio.com;
    root /caminho/para/pharmedice/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 🚀 Deploy em Produção

### Configuração de Ambiente
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.com

# Usar banco de produção
DB_HOST=seu-host-db-producao
DB_DATABASE=pharmedice_production

# Usar serviço de email de produção
MAIL_MAILER=ses

# Usar bucket S3 de produção
AWS_BUCKET=pharmedice-documentos-producao
```

### Comandos de Otimização
```bash
# Cache de configuração
php artisan config:cache

# Cache de rotas
php artisan route:cache

# Cache de views
php artisan view:cache

# Otimizar autoloader
composer install --optimize-autoloader --no-dev
```

### Checklist de Segurança
- [ ] Definir `APP_DEBUG=false` em produção
- [ ] Usar HTTPS para todas as comunicações
- [ ] Configurar CORS adequadamente
- [ ] Configurar limitação de taxa
- [ ] Configurar cabeçalhos seguros
- [ ] Usar buckets S3 específicos do ambiente
- [ ] Configurar monitoramento e logging
- [ ] Configurar estratégias de backup
- [ ] Usar senhas fortes no banco de dados
- [ ] Proteger permissões de arquivo

## 🆘 Solução de Problemas

### Problemas Comuns

#### Erros "Class not found"
```bash
composer dump-autoload
```

#### JWT secret não definido
```bash
php artisan jwt:secret
```

#### Conexão com banco recusada
- Verificar se PostgreSQL está rodando
- Verificar credenciais do banco no `.env`
- Certificar-se de que o banco existe

#### Falha no upload de arquivo
- Verificar credenciais AWS S3
- Verificar se o bucket existe e as permissões estão corretas
- Verificar limites de tamanho de arquivo na configuração PHP

#### Verificação de email não funciona
- Verificar configuração de email no `.env`
- Verificar credenciais do serviço de email
- Verificar logs da aplicação: `tail -f storage/logs/laravel.log`

### Problemas de Performance
- Ativar Redis para cache e filas
- Otimizar queries do banco de dados
- Usar CDN para assets estáticos
- Configurar cache adequado no servidor web

### Obtendo Ajuda
1. Verificar os logs: `storage/logs/laravel.log`
2. Executar testes para identificar problemas: `php artisan test`
3. Verificar configuração do ambiente: `php artisan config:show`
4. Verificar conectividade do banco: `php artisan migrate:status`

---

Para detalhes específicos de configuração, verifique os arquivos individuais de configuração no diretório `config/`.