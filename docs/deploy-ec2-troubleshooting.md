# Troubleshooting - Deploy EC2 Amazon Linux

## Problema: Composer não consegue criar diretório vendor/aws

### Erro
```
In Filesystem.php line 261:
  /home/ec2-user/pharmedice-ca-be/vendor/aws does not exist and could not be created:
```

### Causa
- Permissões incorretas no diretório do projeto
- O diretório `vendor/` pode ter sido criado com permissões restritivas
- Possível problema com SELinux ou ownership de arquivos

### Soluções

#### 1. Verificar e Corrigir Permissões (Recomendado)

```bash
# Navegar até o diretório do projeto
cd /home/ec2-user/pharmedice-ca-be

# Verificar o owner atual
ls -la

# Se necessário, corrigir ownership para ec2-user
sudo chown -R ec2-user:ec2-user .

# Dar permissões corretas
chmod -R 755 .

# Dar permissões especiais para diretórios de escrita
chmod -R 775 storage bootstrap/cache
```

#### 2. Limpar e Reinstalar Vendor

```bash
# Remover vendor e composer.lock
rm -rf vendor composer.lock

# Limpar cache do Composer
composer clear-cache

# Reinstalar dependências
composer install --no-dev --optimize-autoloader
```

#### 3. Se o Problema Persistir - Verificar SELinux

```bash
# Verificar se SELinux está ativo
getenforce

# Se retornar "Enforcing", você pode temporariamente desabilitar para teste
sudo setenforce 0

# Tentar instalar novamente
composer install

# Re-habilitar SELinux (importante para segurança)
sudo setenforce 1

# Se funcionou, configure o contexto SELinux correto
sudo chcon -R -t httpd_sys_rw_content_t storage bootstrap/cache
```

#### 4. Verificar Espaço em Disco

```bash
# Verificar espaço disponível
df -h

# Se o disco estiver cheio, limpar logs antigos
sudo journalctl --vacuum-time=3d
```

### Procedimento Completo de Deploy

```bash
# 1. Clone/Pull do repositório
cd /home/ec2-user
git clone <seu-repo> pharmedice-ca-be
cd pharmedice-ca-be

# 2. Corrigir permissões
sudo chown -R ec2-user:ec2-user .
chmod -R 755 .

# 3. Copiar arquivo de ambiente
cp .env.example .env
nano .env  # Editar com suas configurações

# 4. Instalar dependências
composer install --no-dev --optimize-autoloader

# 5. Gerar chave da aplicação
php artisan key:generate

# 6. Gerar chave JWT
php artisan jwt:secret

# 7. Configurar permissões de storage
chmod -R 775 storage bootstrap/cache
sudo chgrp -R www-data storage bootstrap/cache  # ou nginx, dependendo do seu servidor

# 8. Rodar migrations
php artisan migrate --force

# 9. Rodar seeders (se necessário)
php artisan db:seed --force

# 10. Otimizar para produção
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Configurações do .env para Produção

```env
APP_NAME="Pharmedice"
APP_ENV=production
APP_KEY=base64:...  # Gerado pelo artisan key:generate
APP_DEBUG=false
APP_URL=https://api.seudomain.com

FRONTEND_URL=https://seudomain.com

DB_CONNECTION=pgsql
DB_HOST=seu-rds-endpoint.amazonaws.com
DB_PORT=5432
DB_DATABASE=pharmedice
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha_segura

# JWT
JWT_SECRET=...  # Gerado pelo artisan jwt:secret
JWT_TTL=60
JWT_REFRESH_TTL=20160

# Mail (Resend)
MAIL_MAILER=resend
RESEND_KEY=sua_chave_resend
MAIL_FROM_ADDRESS="nao-responda@seudominio.com"
MAIL_FROM_NAME="Pharmedice | Área do Cliente"

# AWS S3 para arquivos
AWS_ACCESS_KEY_ID=sua_access_key
AWS_SECRET_ACCESS_KEY=sua_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=seu-bucket
AWS_USE_PATH_STYLE_ENDPOINT=false

# Cache e Sessão
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=seu-elasticache.amazonaws.com
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## Configuração do Nginx (Amazon Linux 2023)

```nginx
server {
    listen 80;
    server_name api.seudomain.com;
    root /home/ec2-user/pharmedice-ca-be/public;

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
        fastcgi_pass unix:/var/run/php-fpm/www.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Configuração do Apache (Amazon Linux 2)

```apache
<VirtualHost *:80>
    ServerName api.seudomain.com
    DocumentRoot /home/ec2-user/pharmedice-ca-be/public

    <Directory /home/ec2-user/pharmedice-ca-be/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog /var/log/httpd/pharmedice-error.log
    CustomLog /var/log/httpd/pharmedice-access.log combined
</VirtualHost>
```

## Configuração do PHP-FPM

```bash
# Editar configuração do PHP-FPM
sudo nano /etc/php-fpm.d/www.conf

# Alterar usuário e grupo (se necessário)
user = ec2-user
group = ec2-user

# Ou manter nginx/apache
user = nginx
group = nginx

# Reiniciar PHP-FPM
sudo systemctl restart php-fpm
```

## Supervisor para Queue Workers (Opcional)

Se você usa filas, configure o Supervisor:

```bash
# Instalar Supervisor
sudo yum install supervisor -y

# Criar configuração
sudo nano /etc/supervisord.d/pharmedice-worker.ini
```

```ini
[program:pharmedice-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/ec2-user/pharmedice-ca-be/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=ec2-user
numprocs=2
redirect_stderr=true
stdout_logfile=/home/ec2-user/pharmedice-ca-be/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Iniciar Supervisor
sudo systemctl enable supervisord
sudo systemctl start supervisord

# Ler nova configuração
sudo supervisorctl reread
sudo supervisorctl update

# Iniciar workers
sudo supervisorctl start pharmedice-worker:*
```

## Comandos Úteis para Manutenção

```bash
# Ver logs em tempo real
tail -f storage/logs/laravel.log

# Limpar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Otimizar para produção (após mudanças)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Verificar status da aplicação
php artisan about

# Rodar migrations em produção
php artisan migrate --force

# Rollback da última migration
php artisan migrate:rollback --force

# Ver status das migrations
php artisan migrate:status
```

## Checklist de Segurança

- [ ] `APP_DEBUG=false` em produção
- [ ] `APP_ENV=production`
- [ ] Senha forte para banco de dados
- [ ] JWT_SECRET gerado e seguro
- [ ] Firewall configurado (permitir apenas 80, 443, 22)
- [ ] Security Groups do EC2 configurados
- [ ] SSL/TLS instalado (use Let's Encrypt)
- [ ] Backups automáticos do RDS
- [ ] Logs monitorados
- [ ] Updates de segurança aplicados regularmente

## Problemas Comuns

### 1. "Permission denied" ao acessar storage

```bash
chmod -R 775 storage bootstrap/cache
sudo chgrp -R nginx storage bootstrap/cache
```

### 2. "Class not found" após deploy

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### 3. Migrations não rodam

```bash
# Verificar conexão com banco
php artisan tinker
>>> DB::connection()->getPdo();

# Se funcionar, rodar migrations
php artisan migrate --force
```

### 4. Upload de arquivos não funciona

```bash
# Criar link simbólico para storage público
php artisan storage:link

# Verificar permissões
chmod -R 775 storage/app/public
```

### 5. JWT Token inválido

```bash
# Regenerar secret
php artisan jwt:secret

# Limpar cache
php artisan config:clear
```
