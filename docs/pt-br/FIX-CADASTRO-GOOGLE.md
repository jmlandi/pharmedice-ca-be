# Fix: Cadastro com Google não Funcionando

## 🐛 Problema Identificado

O cadastro de novos usuários via Google OAuth não estava funcionando. O login com Google funcionava para usuários existentes, mas a criação de novas contas falhava.

## 🔍 Causa Raiz

O campo `segundo_nome` na tabela `usuarios` não era nullable, mas a migration de social login (`2025_10_14_172343_add_social_login_fields_to_usuarios_table.php`) não o tornou opcional.

Quando um usuário do Google tinha apenas um nome (ex: "João" sem sobrenome), o sistema tentava criar um usuário com `segundo_nome` vazio ou null, causando erro no banco de dados.

## ✅ Solução Implementada

### 1. Nova Migration
Criada migration `2025_10_14_173000_make_segundo_nome_nullable.php` para tornar o campo `segundo_nome` nullable:

```php
$table->string('segundo_nome')->nullable()->change();
```

### 2. Código Melhorado no AuthService
Atualizado o método `callbackGoogle()` para:

- **Validar** se o nome do Google não está vazio
- **Tratar** corretamente nomes com apenas uma palavra
- **Usar null** em vez de string vazia quando não houver segundo nome
- **Adicionar logs** mais detalhados para debugging

### 3. Campos que Ficam Nullable
Após as migrations, os seguintes campos são opcionais para login social:
- ✅ `senha`
- ✅ `telefone`
- ✅ `numero_documento`
- ✅ `data_nascimento`
- ✅ `apelido`
- ✅ `segundo_nome` (NEW)

## 📝 Como Aplicar o Fix

### No Ambiente de Staging

```bash
# 1. Fazer pull das mudanças
git pull origin fix/criar-conta-com-google

# 2. Executar a nova migration
php artisan migrate

# 3. Verificar se a migration foi aplicada
php artisan migrate:status
```

### Verificação
Após aplicar o fix, teste criando uma nova conta com Google usando um email que não existe no sistema.

## 🧪 Casos de Teste

| Cenário | Resultado Esperado |
|---------|-------------------|
| Usuário novo com nome completo (ex: "João Silva") | ✅ Cria conta com `primeiro_nome: João` e `segundo_nome: Silva` |
| Usuário novo com nome único (ex: "Madonna") | ✅ Cria conta com `primeiro_nome: Madonna` e `segundo_nome: null` |
| Usuário existente faz login via Google | ✅ Vincula conta Google ao usuário existente |
| Usuário com @pharmedice.com.br | ✅ Criado como administrador |

## 📊 Log de Debugging

O código agora inclui logs detalhados em `callbackGoogle()`:

```php
Log::info('Criando novo usuário via Google OAuth', [
    'email' => $email,
    'primeiro_nome' => $primeiroNome,
    'segundo_nome' => $segundoNome,
    'google_id' => $googleUser->getId(),
    'tipo_usuario' => $tipoUsuario
]);
```

Verifique os logs em `storage/logs/laravel.log` para debugging.

## 🔄 Mudanças de Código

### Arquivos Modificados
1. `app/Services/AuthService.php` - Método `callbackGoogle()`
2. `database/migrations/2025_10_14_173000_make_segundo_nome_nullable.php` (novo)

### Melhorias Adicionadas
- Validação de nome vazio/null
- Trim em todos os valores de nome
- Logs mais detalhados
- Tratamento correto de usuários com nome único
