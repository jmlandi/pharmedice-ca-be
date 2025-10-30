# Correção do Erro 413 - Upload de Laudos

## Problema
Erro **413 Request Entity Too Large** ao fazer upload de arquivos PDF, mesmo com tamanhos pequenos (400KB).

## Causa
O nginx tem um limite padrão de **1MB** para o tamanho do corpo da requisição (`client_max_body_size`).

## Solução

### 1. Editar a Configuração do Nginx

```bash
sudo nano /etc/nginx/conf.d/pharmedice-api.conf
```

### 2. Adicionar a Diretiva `client_max_body_size`

Localize a seção do `server` e adicione após os logs:

```nginx
server {
    listen 80;
    server_name api-pharmedice.marcoslandi.com;
    
    root /home/ec2-user/pharmedice-ca-be/public;
    index index.php;

    charset utf-8;

    # Logs
    access_log /var/log/nginx/pharmedice-access.log;
    error_log /var/log/nginx/pharmedice-error.log;

    # Upload de arquivos - máximo 10MB
    client_max_body_size 10M;

    # Security headers
    # ... resto da configuração
}
```

### 3. Testar a Configuração

```bash
sudo nginx -t
```

Deve retornar:
```
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
```

### 4. Recarregar o Nginx

```bash
sudo systemctl reload nginx
```

Ou, se preferir reiniciar:

```bash
sudo systemctl restart nginx
```

### 5. Verificar Status

```bash
sudo systemctl status nginx
```

## Verificação

Após aplicar a correção, tente fazer upload novamente. O erro 413 não deve mais ocorrer para arquivos até 10MB.

## Limites Relacionados

Para garantir que tudo funcione corretamente, verifique também:

### PHP (já configurado)
- `upload_max_filesize = 10M`
- `post_max_size = 10M`

### Laravel (já configurado)
- Validação: `max:10240` (10MB em KB)

## Troubleshooting

Se ainda tiver problemas:

1. **Verificar logs do nginx:**
   ```bash
   sudo tail -f /var/log/nginx/pharmedice-error.log
   ```

2. **Verificar se a configuração foi aplicada:**
   ```bash
   sudo nginx -T | grep client_max_body_size
   ```

3. **Verificar se o nginx está rodando:**
   ```bash
   sudo systemctl status nginx
   ```

4. **Se usar SSL/HTTPS**, adicione também no bloco `server` da porta 443.
