# Fix da Validação de Assinatura - Verificação de Email

## Problema

Durante a refatoração do sistema de verificação de email para usar POST em vez de GET, enfrentamos um desafio com a validação de assinaturas das URLs assinadas (signed URLs).

### Contexto

O Laravel gera URLs assinadas temporárias para segurança, usando HMAC-SHA256. A assinatura garante que:
1. A URL não foi adulterada
2. A URL não expirou
3. Apenas o servidor pode gerar URLs válidas (usa `APP_KEY`)

### O Desafio

Quando mudamos de GET para POST:
- **Antes**: `GET /api/auth/verificar-email/{id}/{hash}?expires=X&signature=Y`
- **Depois**: `POST /api/auth/verificar-email` com parâmetros no body JSON

O Laravel tem métodos nativos para validar signed URLs (`hasValidSignature()`), mas eles foram projetados para requests GET com query parameters, não para POST com JSON body.

## Solução

### A Chave: Ordem dos Parâmetros

Laravel gera a assinatura baseada na URL completa incluindo todos os query parameters **em ordem alfabética**. Descobrimos que a ordem correta é:

```
?expires={timestamp}&hash={hash}&id={id}
```

E não:
```
?id={id}&hash={hash}&expires={timestamp}
```

### Implementação Final

No `AuthController::verificarEmail()`:

```php
// Validar a assinatura
// Laravel gera os parâmetros em ordem alfabética: expires, hash, id
// Precisamos reconstruir a URL exatamente como ela foi gerada
$baseUrl = URL::route('verification.verify', [], true);

// Construir a query string na ordem alfabética correta
$queryString = http_build_query([
    'expires' => $request->expires,
    'hash' => $request->hash,
    'id' => $request->id,
]);

$urlToValidate = $baseUrl . '?' . $queryString;

// Calcular a assinatura esperada
$expectedSignature = hash_hmac('sha256', $urlToValidate, config('app.key'));

// Comparar as assinaturas usando hash_equals (timing-attack safe)
if (!hash_equals($expectedSignature, $request->signature)) {
    // Assinatura inválida
    return response()->json([...], 422);
}
```

### Por que `http_build_query()`?

A função `http_build_query()` do PHP ordena automaticamente os parâmetros em ordem alfabética, garantindo que reconstruímos a URL exatamente como o Laravel a gerou.

## Alternativas Consideradas

### 1. Usar `Request::hasValidSignature()`

**Tentativa:**
```php
$testRequest = Request::create($url, 'GET');
$testRequest->hasValidSignature();
```

**Problema:** Não funcionou porque a request original era POST com JSON body, e criar uma request GET simulada não preservava o contexto correto.

### 2. Validação Manual com HMAC Incorreto

**Tentativa:**
```php
$url = route('verification.verify', [
    'id' => $request->id,
    'hash' => $request->hash
], true);
$url .= '&expires=' . $request->expires;
```

**Problema:** Os parâmetros não estavam na ordem alfabética correta.

## Lições Aprendidas

1. **Ordem Importa**: Laravel ordena parâmetros alfabeticamente ao gerar assinaturas
2. **Use `http_build_query()`**: Garante ordem consistente
3. **Use `hash_equals()`**: Previne timing attacks ao comparar hashes
4. **Logs são Essenciais**: Adicionamos logs detalhados mostrando URL esperada vs recebida, facilitando debug

## Segurança

A solução mantém todos os aspectos de segurança:

✅ Assinatura criptográfica (HMAC-SHA256)  
✅ Tempo de expiração validado  
✅ Comparação timing-attack safe (`hash_equals()`)  
✅ APP_KEY mantém sigilo (apenas servidor pode gerar/validar)  
✅ Logs de tentativas inválidas para auditoria

## Testes

Todos os 6 testes de verificação de email passaram:

```bash
✓ usuario_pode_verificar_email_com_link_valido
✓ nao_pode_verificar_email_com_link_expirado
✓ pode_reenviar_email_de_verificacao
✓ nao_pode_reenviar_se_email_ja_verificado
✓ deve_exigir_autenticacao_para_reenviar_verificacao
✓ nao_pode_verificar_email_com_link_nao_assinado
```

## Referências

- [Laravel Signed URLs Documentation](https://laravel.com/docs/urls#signed-urls)
- [PHP hash_hmac()](https://www.php.net/manual/en/function.hash-hmac.php)
- [PHP http_build_query()](https://www.php.net/manual/en/function.http-build-query.php)
- [Timing Attack Prevention](https://en.wikipedia.org/wiki/Timing_attack)
