# Configuração CORS - Pharmedice Customer Area API

## 📋 Resumo

A configuração CORS (Cross-Origin Resource Sharing) foi otimizada para funcionar de forma diferente em ambiente local e produção:

### 🏠 Ambiente Local (`APP_ENV=local`)
- ✅ **Todas as origens permitidas** (`*`)
- ✅ Middleware CORS do Laravel habilitado
- ✅ Facilita desenvolvimento e testes
- ✅ Não requer configuração adicional

### 🚀 Ambiente de Produção (`APP_ENV=production`)
- ✅ **Apenas origens específicas permitidas**
- ✅ CORS controlado pelo Nginx (melhor performance)
- ✅ Middleware CORS do Laravel desabilitado
- ✅ Maior segurança

---

## 🔧 Arquivos Modificados

### 1. `config/cors.php`

```php
// Configuração dinâmica baseada no ambiente
'allowed_origins' => env('APP_ENV') === 'local' ? ['*'] : [
    'https://cliente.pharmedice.com.br',
    'https://api.pharmedice.com.br',
    // ... outros domínios
],
```

**Comportamento:**
- **Local**: Permite qualquer origem (`*`)
- **Produção**: Lista específica de domínios autorizados

### 2. `bootstrap/app.php`

```php
// Habilita CORS apenas em ambiente local
if (env('APP_ENV') === 'local') {
    $middleware->api(prepend: [
        \Illuminate\Http\Middleware\HandleCors::class,
    ]);
}
```

**Comportamento:**
- **Local**: Middleware CORS do Laravel ativo
- **Produção**: CORS gerenciado pelo Nginx

---

## 🧪 Como Testar Localmente

### 1. Verificar Configuração do .env

```env
APP_ENV=local
APP_DEBUG=true
```

### 2. Testar com cURL

```bash
# Requisição OPTIONS (preflight)
curl -X OPTIONS http://localhost:8000/api/auth/login \
  -H "Origin: http://localhost:3000" \
  -H "Access-Control-Request-Method: POST" \
  -v

# Requisição GET
curl -X GET http://localhost:8000/api/auth/google \
  -H "Origin: http://localhost:3000" \
  -v
```

### 3. Verificar Headers Esperados

Você deve ver estes headers na resposta:

```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: *
Access-Control-Max-Age: 86400
```

### 4. Testar do Frontend

```javascript
// Next.js / React
fetch('http://localhost:8000/api/auth/google', {
  method: 'GET',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
})
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Erro:', error));
```

---

## 🔍 Troubleshooting

### Erro: "CORS policy: No 'Access-Control-Allow-Origin' header"

**Solução 1**: Verificar APP_ENV
```bash
php artisan config:cache
php artisan config:clear
php artisan serve
```

**Solução 2**: Verificar se o middleware está carregado
```bash
php artisan route:list --middleware
# Deve mostrar HandleCors nas rotas api/*
```

**Solução 3**: Limpar cache
```bash
php artisan optimize:clear
php artisan config:cache
```

### Erro: "CORS policy: The request client is not a secure context"

**Causa**: Requisição HTTP em vez de HTTPS (só em produção)

**Solução**: Em local, use `http://localhost`. Em produção, use `https://`.

### Headers CORS duplicados

**Causa**: Nginx e Laravel ambos adicionando headers CORS

**Solução**: 
- Local: Desabilitar headers CORS no Nginx
- Produção: Desabilitar middleware CORS do Laravel (já configurado)

---

## 📝 Configurações Detalhadas

### Paths Habilitados

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
```

CORS está habilitado para:
- `/api/*` - Todas as rotas da API
- `/sanctum/csrf-cookie` - Cookie CSRF do Sanctum (se usado)

### Métodos Permitidos

```php
'allowed_methods' => ['*'],
```

Todos os métodos HTTP são permitidos:
- GET
- POST
- PUT
- DELETE
- PATCH
- OPTIONS

### Headers Permitidos

```php
'allowed_headers' => ['*'],
```

Todos os headers são permitidos, incluindo:
- Authorization (JWT)
- Content-Type
- Accept
- X-Requested-With
- etc.

### Headers Expostos

```php
'exposed_headers' => ['Authorization', 'Content-Type'],
```

Headers que o frontend pode acessar na resposta.

### Credenciais

```php
'supports_credentials' => true,
```

Permite envio de cookies e headers de autenticação.

### Cache (Preflight)

```php
'max_age' => 86400,
```

Requisições OPTIONS (preflight) são cacheadas por 24 horas.

---

## 🔐 Segurança em Produção

### Domínios Permitidos

Atualmente configurados:
```php
'https://cliente.pharmedice.com.br'
'https://api.pharmedice.com.br'
'https://api-pharmedice.marcoslandi.com'
```

### Padrões de Domínio

```php
'#^https://.*\.pharmedice\.com$#'
'#^https://.*\.pharmedice\.com\.br$#'
'#^https://.*\.marcoslandi\.com$#'
```

Permite todos os subdomínios dos domínios principais.

### Adicionar Novo Domínio

Edite `config/cors.php`:

```php
'allowed_origins' => env('APP_ENV') === 'local' ? ['*'] : [
    // ... domínios existentes
    'https://novo-dominio.com',
],
```

Depois execute:
```bash
php artisan config:cache
```

---

## 📚 Referências

- [Laravel CORS Documentation](https://laravel.com/docs/11.x/routing#cors)
- [MDN Web Docs - CORS](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS)
- [CORS Preflight Requests](https://developer.mozilla.org/en-US/docs/Glossary/Preflight_request)

---

## ✅ Checklist de Validação

- [x] `APP_ENV=local` no `.env`
- [x] CORS permite todas as origens em local
- [x] Middleware HandleCors habilitado em local
- [x] Requisições OPTIONS funcionando
- [x] Headers CORS presentes nas respostas
- [x] Frontend consegue fazer requisições
- [x] Autenticação Google funcionando com CORS

---

**Última atualização**: Outubro 2025  
**Ambiente testado**: PHP 8.2+ / Laravel 12
