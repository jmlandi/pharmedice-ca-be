# Guia Completo: Deploy Laravel no EC2 (API Pública)

## 📋 Índice
1. [Configurar Instância EC2](#1-configurar-instância-ec2)
2. [Instalar Dependências no Servidor](#2-instalar-dependências-no-servidor)
3. [Configurar Nginx](#3-configurar-nginx)
4. [Configurar SSL/HTTPS](#4-configurar-sslhttps)
5. [Deploy da Aplicação](#5-deploy-da-aplicação)
6. [Configurar Domínio](#6-configurar-domínio)
7. [Monitoramento e Manutenção](#7-monitoramento-e-manutenção)

---

## 1. Configurar Instância EC2

### 1.1 Criar/Configurar Security Group

No **AWS Console > EC2 > Security Groups**:

```
Inbound Rules:
┌──────────┬──────────┬───────────────┬─────────────────┐
│ Type     │ Protocol │ Port Range    │ Source          │
├──────────┼──────────┼───────────────┼─────────────────┤
│ SSH      │ TCP      │ 22            │ Seu IP/0.0.0.0/0│
│ HTTP     │ TCP      │ 80            │ 0.0.0.0/0       │
│ HTTPS    │ TCP      │ 443           │ 0.0.0.0/0       │
│ Custom   │ TCP      │ 5432          │ RDS Security Grp│
└──────────┴──────────┴───────────────┴─────────────────┘
```

**⚠️ Importante**: Por segurança, considere restringir SSH apenas ao seu IP.

### 1.2 Criar Elastic IP (Opcional mas Recomendado)

```bash
# No AWS Console:
EC2 > Network & Security > Elastic IPs > Allocate Elastic IP address
# Depois, associe à sua instância EC2
```

**Por quê?** Sem Elastic IP, seu IP público muda toda vez que você reinicia a instância.

---

## 2. Instalar Dependências no Servidor

### 2.1 Conectar ao EC2

```bash
# No seu computador local
ssh -i sua-chave.pem ec2-user@seu-ip-publico
```

### 2.2 Atualizar Sistema

```bash
# Amazon Linux 2023
sudo dnf update -y

# Amazon Linux 2
sudo yum update -y
```

### 2.3 Instalar PHP 8.2+

```bash
# Amazon Linux 2023
sudo dnf install -y php8.2 php8.2-fpm php8.2-cli php8.2-pgsql php8.2-mbstring \
  php8.2-xml php8.2-bcmath php8.2-curl php8.2-zip php8.2-gd php8.2-intl \
  php8.2-opcache php8.2-redis

# Amazon Linux 2
sudo amazon-linux-extras enable php8.2
sudo yum install -y php php-fpm php-pgsql php-mbstring php-xml php-bcmath \
  php-curl php-zip php-gd php-intl php-opcache php-redis
```

### 2.4 Instalar Nginx

```bash
sudo dnf install -y nginx
# ou
sudo yum install -y nginx

# Habilitar e iniciar
sudo systemctl enable nginx
sudo systemctl start nginx
```

### 2.5 Instalar Composer

```bash
cd ~
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### 2.6 Instalar Git

```bash
sudo dnf install -y git
# ou
sudo yum install -y git
```

### 2.7 Instalar Node.js (para compilar assets, se necessário)

```bash
curl -fsSL https://rpm.nodesource.com/setup_20.x | sudo bash -
sudo dnf install -y nodejs
# ou
sudo yum install -y nodejs
```

---

## 3. Configurar Nginx

### 3.1 Criar Configuração do Site

```bash
sudo nano /etc/nginx/conf.d/pharmedice-api.conf
```

```nginx
server {
    listen 80;
    server_name api.seudominio.com;  # Substitua pelo seu domínio
    
    root /home/ec2-user/pharmedice-ca-be/public;
    index index.php;

    charset utf-8;

    # Logs
    access_log /var/log/nginx/pharmedice-access.log;
    error_log /var/log/nginx/pharmedice-error.log;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # CORS (se necessário para seu frontend)
    add_header 'Access-Control-Allow-Origin' 'https://seudominio.com' always;
    add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, PATCH, OPTIONS' always;
    add_header 'Access-Control-Allow-Headers' 'Authorization, Content-Type, Accept, Origin' always;
    add_header 'Access-Control-Allow-Credentials' 'true' always;

    # Handle preflight OPTIONS requests
    if ($request_method = 'OPTIONS') {
        return 204;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { 
        access_log off; 
        log_not_found off; 
    }
    
    location = /robots.txt  { 
        access_log off; 
        log_not_found off; 
    }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm/www.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_index index.php;
        include fastcgi_params;
        
        # Aumentar timeout para operações longas
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Negar acesso a arquivos sensíveis
    location ~ /\.(env|git|gitignore|htaccess) {
        deny all;
    }

    # Cache de arquivos estáticos
    location ~* \.(jpg|jpeg|gif|png|css|js|ico|xml|svg|woff|woff2|ttf)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

### 3.2 Configurar PHP-FPM

```bash
sudo nano /etc/php-fpm.d/www.conf
```

Encontre e modifique estas linhas:

```ini
; User/Group
user = nginx
group = nginx

; Listen
listen = /var/run/php-fpm/www.sock

; Permissions
listen.owner = nginx
listen.group = nginx
listen.mode = 0660

; Performance
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; PHP settings
php_value[upload_max_filesize] = 10M
php_value[post_max_size] = 10M
php_value[memory_limit] = 256M
php_value[max_execution_time] = 300
```

### 3.3 Testar e Reiniciar Serviços

```bash
# Testar configuração do Nginx
sudo nginx -t

# Se OK, reiniciar serviços
sudo systemctl restart php-fpm
sudo systemctl restart nginx

# Verificar status
sudo systemctl status nginx
sudo systemctl status php-fpm
```

---

## 4. Configurar SSL/HTTPS

### 4.1 Instalar Certbot

```bash
# Amazon Linux 2023/2
sudo dnf install -y certbot python3-certbot-nginx
# ou
sudo yum install -y certbot python3-certbot-nginx
```

### 4.2 Obter Certificado SSL (Let's Encrypt - Gratuito)

```bash
# Certifique-se que seu domínio aponta para o IP do EC2
sudo certbot --nginx -d api.seudominio.com

# Responda às perguntas:
# - Email: seu@email.com
# - Aceitar termos: Yes
# - Compartilhar email: No (opcional)
# - Redirecionar HTTP para HTTPS: Yes
```

### 4.3 Renovação Automática

```bash
# Certbot já configura renovação automática via cron/systemd timer
# Testar renovação:
sudo certbot renew --dry-run

# Verificar timer
sudo systemctl status certbot-renew.timer
```

### 4.4 Configuração Manual HTTPS (se não usar certbot)

```bash
sudo nano /etc/nginx/conf.d/pharmedice-api.conf
```

```nginx
# Redirecionar HTTP para HTTPS
server {
    listen 80;
    server_name api.seudominio.com;
    return 301 https://$server_name$request_uri;
}

# Configuração HTTPS
server {
    listen 443 ssl http2;
    server_name api.seudominio.com;
    
    # Certificados SSL
    ssl_certificate /etc/letsencrypt/live/api.seudominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.seudominio.com/privkey.pem;
    ssl_trusted_certificate /etc/letsencrypt/live/api.seudominio.com/chain.pem;
    
    # Configurações SSL modernas
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384';
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # HSTS
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # ... resto da configuração (igual ao passo 3.1)
}
```

---

## 5. Deploy da Aplicação

### 5.1 Clonar Repositório

```bash
cd /home/ec2-user
git clone https://github.com/seu-usuario/pharmedice-ca-be.git
cd pharmedice-ca-be
```

### 5.2 Configurar .env

```bash
cp .env.example .env
nano .env
```

**Configuração .env para Produção:**

```env
APP_NAME="Pharmedice"
APP_ENV=production
APP_KEY=  # Será gerado
APP_DEBUG=false
APP_URL=https://api.seudominio.com

FRONTEND_URL=https://seudominio.com

LOG_CHANNEL=stack
LOG_LEVEL=error

# Banco de Dados (RDS)
DB_CONNECTION=pgsql
DB_HOST=seu-rds.xxxx.us-east-1.rds.amazonaws.com
DB_PORT=5432
DB_DATABASE=pharmedice
DB_USERNAME=admin
DB_PASSWORD=senha-super-segura-aqui

# Cache e Sessão (ElastiCache Redis - Opcional)
CACHE_STORE=redis
SESSION_DRIVER=redis
REDIS_HOST=seu-elasticache.xxxx.cache.amazonaws.com
REDIS_PASSWORD=null
REDIS_PORT=6379

# Ou usar database se não tiver Redis
# CACHE_STORE=database
# SESSION_DRIVER=database

# Queue
QUEUE_CONNECTION=database  # ou redis se tiver

# Mail
MAIL_MAILER=resend
RESEND_KEY=sua_chave_resend
MAIL_FROM_ADDRESS="nao-responda@seudominio.com"
MAIL_FROM_NAME="Pharmedice | Área do Cliente"

# AWS S3
AWS_ACCESS_KEY_ID=sua_access_key
AWS_SECRET_ACCESS_KEY=sua_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=laudos-pharmedice
AWS_USE_PATH_STYLE_ENDPOINT=false

# JWT
JWT_SECRET=  # Será gerado
JWT_TTL=60
JWT_REFRESH_TTL=20160
JWT_ALGO=HS256
JWT_SHOW_BLACKLIST_EXCEPTION=false
```

### 5.3 Instalar Dependências e Configurar

```bash
# Instalar dependências do Composer
composer install --no-dev --optimize-autoloader

# Gerar chaves
php artisan key:generate
php artisan jwt:secret

# Criar link simbólico para storage
php artisan storage:link

# Rodar migrations
php artisan migrate --force

# Rodar seeders (se necessário)
php artisan db:seed --force

# Otimizar para produção
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 5.4 Configurar Permissões

```bash
# Dar ownership para nginx
sudo chown -R nginx:nginx /home/ec2-user/pharmedice-ca-be

# Permissões especiais para storage e bootstrap/cache
sudo chmod -R 775 /home/ec2-user/pharmedice-ca-be/storage
sudo chmod -R 775 /home/ec2-user/pharmedice-ca-be/bootstrap/cache

# Se você quiser fazer deploy via git como ec2-user:
sudo chown -R ec2-user:nginx /home/ec2-user/pharmedice-ca-be
sudo chmod -R 775 /home/ec2-user/pharmedice-ca-be/storage
sudo chmod -R 775 /home/ec2-user/pharmedice-ca-be/bootstrap/cache
```

### 5.5 Configurar SELinux (se ativo)

```bash
# Verificar se SELinux está ativo
getenforce

# Se retornar "Enforcing", configure contextos corretos
sudo chcon -R -t httpd_sys_rw_content_t /home/ec2-user/pharmedice-ca-be/storage
sudo chcon -R -t httpd_sys_rw_content_t /home/ec2-user/pharmedice-ca-be/bootstrap/cache
```

---

## 6. Configurar Domínio

### 6.1 Apontar Domínio para EC2

No seu provedor de domínio (GoDaddy, Namecheap, Route 53, etc.):

**Opção 1: Registro A (se usar Elastic IP)**
```
Tipo: A
Nome: api (ou @)
Valor: 3.XXX.XXX.XXX (IP Elástico do EC2)
TTL: 3600
```

**Opção 2: Route 53 (recomendado se usar AWS)**

```bash
# No AWS Console > Route 53 > Hosted Zones
# Criar registro:
Nome: api.seudominio.com
Tipo: A
Valor: [Alias para instância EC2 ou IP Elástico]
```

### 6.2 Verificar DNS

```bash
# No seu computador local
nslookup api.seudominio.com
# ou
dig api.seudominio.com
```

---

## 7. Monitoramento e Manutenção

### 7.1 Script de Deploy Automatizado

Crie um script para facilitar deployments futuros:

```bash
nano ~/deploy.sh
```

```bash
#!/bin/bash

echo "🚀 Iniciando deploy..."

# Navegar para diretório
cd /home/ec2-user/pharmedice-ca-be

# Ativar modo de manutenção
php artisan down

# Atualizar código
git pull origin main

# Instalar/atualizar dependências
composer install --no-dev --optimize-autoloader

# Rodar migrations
php artisan migrate --force

# Limpar e otimizar caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Otimizar autoloader
composer dump-autoload --optimize

# Reiniciar serviços
sudo systemctl restart php-fpm
sudo systemctl reload nginx

# Desativar modo de manutenção
php artisan up

echo "✅ Deploy concluído!"
```

```bash
# Dar permissão de execução
chmod +x ~/deploy.sh

# Executar deploy
~/deploy.sh
```

### 7.2 Monitorar Logs

```bash
# Logs do Laravel
tail -f /home/ec2-user/pharmedice-ca-be/storage/logs/laravel.log

# Logs do Nginx
sudo tail -f /var/log/nginx/pharmedice-error.log
sudo tail -f /var/log/nginx/pharmedice-access.log

# Logs do PHP-FPM
sudo tail -f /var/log/php-fpm/www-error.log
```

### 7.3 Backup Automático do Banco

```bash
# Criar script de backup
nano ~/backup-db.sh
```

```bash
#!/bin/bash

BACKUP_DIR="/home/ec2-user/backups"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="pharmedice"
DB_HOST="seu-rds.xxxx.rds.amazonaws.com"
DB_USER="admin"
DB_PASS="sua-senha"

# Criar diretório se não existir
mkdir -p $BACKUP_DIR

# Fazer backup
PGPASSWORD=$DB_PASS pg_dump -h $DB_HOST -U $DB_USER $DB_NAME > $BACKUP_DIR/pharmedice_$DATE.sql

# Compactar
gzip $BACKUP_DIR/pharmedice_$DATE.sql

# Manter apenas últimos 7 dias
find $BACKUP_DIR -name "pharmedice_*.sql.gz" -mtime +7 -delete

echo "Backup concluído: pharmedice_$DATE.sql.gz"
```

```bash
chmod +x ~/backup-db.sh

# Agendar backup diário no cron
crontab -e

# Adicionar linha (backup às 3h da manhã):
0 3 * * * /home/ec2-user/backup-db.sh >> /home/ec2-user/backup.log 2>&1
```

### 7.4 Configurar Queue Worker (se usar filas)

```bash
sudo nano /etc/systemd/system/pharmedice-worker.service
```

```ini
[Unit]
Description=Pharmedice Queue Worker
After=network.target

[Service]
Type=simple
User=ec2-user
Group=nginx
Restart=always
ExecStart=/usr/bin/php /home/ec2-user/pharmedice-ca-be/artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
# Habilitar e iniciar
sudo systemctl enable pharmedice-worker
sudo systemctl start pharmedice-worker
sudo systemctl status pharmedice-worker
```

### 7.5 Monitorar Saúde da API

Crie um endpoint de health check:

```php
// routes/api.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
    ]);
});
```

Teste:
```bash
curl https://api.seudominio.com/health
```

---

## 🎯 Checklist Final

- [ ] EC2 configurado com Security Group correto
- [ ] Elastic IP associado
- [ ] Nginx instalado e configurado
- [ ] PHP-FPM configurado
- [ ] SSL/HTTPS funcionando
- [ ] Domínio apontando para EC2
- [ ] Aplicação deployada
- [ ] Migrations executadas
- [ ] Permissões corretas
- [ ] .env configurado para produção
- [ ] APP_DEBUG=false
- [ ] Logs monitorados
- [ ] Backups configurados
- [ ] Queue workers rodando (se necessário)
- [ ] Health check funcionando

---

## 🔥 Comandos Rápidos

```bash
# Ver status dos serviços
sudo systemctl status nginx php-fpm

# Reiniciar serviços
sudo systemctl restart nginx php-fpm

# Ver logs em tempo real
tail -f storage/logs/laravel.log

# Testar API
curl -I https://api.seudominio.com
curl https://api.seudominio.com/health

# Limpar todos os caches
php artisan optimize:clear

# Otimizar para produção
php artisan optimize
```

---

## 🆘 Problemas Comuns

### API retorna 502 Bad Gateway
```bash
# Verificar se PHP-FPM está rodando
sudo systemctl status php-fpm
sudo systemctl restart php-fpm

# Verificar logs
sudo tail -f /var/log/nginx/pharmedice-error.log
```

### API retorna 403 Forbidden
```bash
# Verificar permissões
ls -la /home/ec2-user/pharmedice-ca-be/public
sudo chown -R nginx:nginx /home/ec2-user/pharmedice-ca-be
```

### API lenta
```bash
# Verificar queries lentas
tail -f storage/logs/laravel.log | grep "Slow query"

# Verificar recursos do servidor
top
free -m
df -h
```

### Não consegue acessar pelo domínio
```bash
# Verificar DNS
nslookup api.seudominio.com

# Verificar Security Group (porta 80 e 443 abertas)
# Verificar se Nginx está ouvindo
sudo netstat -tulpn | grep nginx
```

---

**🎉 Pronto! Sua API está pública e acessível!**

Acesse: `https://api.seudominio.com/health`
