# Reativação Automática de Usuários via Google OAuth

## 📋 Problema Identificado

Usuários que foram "deletados" do sistema (marcados como `ativo = false`) não conseguiam fazer login novamente via Google OAuth, resultando no erro:
```
Usuário inativo
```

Isso ocorria porque o sistema encontrava o usuário existente mas inativo e rejeitava a autenticação.

## ✅ Solução Implementada

### Comportamento Anterior
1. Google OAuth encontrava usuário com `ativo = false`
2. Sistema rejeitava login com erro "Usuário inativo"
3. Usuário não conseguia acessar a plataforma novamente

### Comportamento Atual
1. Google OAuth encontra usuário (ativo ou inativo)
2. **Se usuário estiver inativo, reativa automaticamente**
3. Atualiza informações do Google (avatar, nome se necessário)
4. Permite login normal

## 🔧 Alterações Realizadas

### AuthService.php - Método `callbackGoogle()`

#### 1. Busca Incluindo Usuários Inativos
```php
// ANTES: só buscava usuários ativos
$usuario = Usuario::where('google_id', $googleUser->getId())->first();

// DEPOIS: busca incluindo usuários inativos
$usuario = Usuario::withoutGlobalScopes()->where('google_id', $googleUser->getId())->first();
```

#### 2. Reativação Automática por Email
```php
if ($usuario) {
    // Se o usuário estava inativo (deletado), reativa a conta
    if (!$usuario->ativo) {
        $usuario->ativo = true;
        Log::info('Conta reativada via Google OAuth', [...]);
    }
}
```

#### 3. Reativação Automática por Google ID
```php
} else if ($usuario && !$usuario->ativo) {
    // Reativa conta e atualiza informações
    $usuario->ativo = true;
    $usuario->avatar = $googleUser->getAvatar();
    $usuario->email_verified_at = $usuario->email_verified_at ?? now();
    
    // Atualiza nome se mudou no Google
    // ... código de atualização de nome
}
```

#### 4. Remoção da Verificação Restritiva
```php
// REMOVIDO: verificação que impedia login de usuários inativos
// if (!$usuario->ativo) {
//     throw new \Exception('Usuário inativo', 401);
// }
```

## 📝 Cenários Cobertos

### Cenário 1: Usuário Deletado por Google ID
- **Situação**: Usuário já tinha `google_id` mas foi marcado como `ativo = false`
- **Ação**: Reativa automaticamente + atualiza avatar e nome
- **Log**: "Conta existente reativada via Google OAuth"

### Cenário 2: Usuário Deletado por Email  
- **Situação**: Usuário foi deletado, tenta login com Google pela primeira vez
- **Ação**: Vincula Google ID + reativa conta + atualiza informações
- **Log**: "Conta reativada via Google OAuth" + "Conta Google vinculada a usuário existente"

### Cenário 3: Usuário Novo
- **Situação**: Email não existe no sistema
- **Ação**: Cria novo usuário normalmente
- **Log**: "Novo usuário criado via Google OAuth"

### Cenário 4: Usuário Ativo Existente
- **Situação**: Usuário já existe e está ativo
- **Ação**: Login normal, atualiza avatar se necessário
- **Log**: Logs normais de autenticação

## 🔍 Logs Adicionais

Novos logs foram adicionados para rastrear reativações:

```php
Log::info('Conta reativada via Google OAuth', [
    'usuario_id' => $usuario->id,
    'email' => $usuario->email,
    'google_id' => $googleUser->getId()
]);

Log::info('Conta Google vinculada a usuário existente', [
    'usuario_id' => $usuario->id,
    'email' => $usuario->email,
    'foi_reativada' => !$usuario->getOriginal('ativo')
]);
```

## ⚠️ Considerações de Segurança

### ✅ Comportamentos Seguros
- Apenas usuários com acesso ao email original podem reativar a conta
- Google já valida a propriedade do email
- Logs completos para auditoria
- Atualização segura de informações básicas (nome, avatar)

### 🔒 Dados Preservados
- Histórico de laudos mantido
- Permissões anteriores mantidas
- Configurações de comunicação preservadas

### 📊 Informações Atualizadas na Reativação
- `ativo = true`
- `avatar` (URL atual do Google)
- `email_verified_at` (se era null)
- `primeiro_nome` e `segundo_nome` (se mudaram no Google)

## 🚀 Deploy e Testes

### Teste Manual
1. Marcar usuário como inativo: `UPDATE usuarios SET ativo = false WHERE email = 'teste@exemplo.com'`
2. Tentar login com Google usando o mesmo email
3. Verificar se usuário foi reativado e consegue acessar

### Monitoramento
- Acompanhar logs de reativação em produção
- Verificar se há padrões de reativação massiva (possível problema)
- Validar se usuários reativados conseguem acessar normalmente

---

**Data da Implementação**: 2 de Novembro de 2025  
**Problema Original**: `https://cliente.pharmedice.com.br/login?error=Usu%C3%A1rio+inativo`  
**Status**: ✅ Resolvido - Usuários deletados podem fazer login via Google OAuth novamente