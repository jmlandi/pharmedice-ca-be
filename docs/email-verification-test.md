# 🔒 Sistema de Verificação de Email - Teste

Este documento explica como testar o novo sistema de bloqueio de login para usuários sem email verificado.

## ✅ Implementações Realizadas

### 1. **Bloqueio no Login**
- Usuários com `email_verified_at = null` não conseguem fazer login
- Retorna erro 403 com mensagem clara sobre verificação de email

### 2. **Endpoint Público para Reenvio**
- Novo endpoint: `POST /api/auth/reenviar-verificacao-email-publico`
- Permite reenviar email de verificação sem estar logado
- Útil quando o login é bloqueado por falta de verificação

### 3. **Melhorias no Response**
- Campo `email_verificado` no retorno do login
- Mensagens de erro mais claras e informativas

## 🧪 Como Testar

### **Passo 1: Criar Usuário de Teste**
```bash
php artisan test:usuario-sem-verificacao teste@exemplo.com
```

### **Passo 2: Testar Login Bloqueado**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "teste@exemplo.com",
    "senha": "123456"
  }'
```

**Resposta esperada:** Erro 403
```json
{
  "success": false,
  "message": "Email não verificado. Verifique sua caixa de entrada e clique no link de verificação enviado no momento do cadastro."
}
```

### **Passo 3: Reenviar Email de Verificação**
```bash
curl -X POST http://localhost:8000/api/auth/reenviar-verificacao-email-publico \
  -H "Content-Type: application/json" \
  -d '{
    "email": "teste@exemplo.com"
  }'
```

**Resposta esperada:** Sucesso
```json
{
  "sucesso": true,
  "mensagem": "Email de verificação reenviado para teste@exemplo.com"
}
```

### **Passo 4: Verificar Email**
- Acesse o link enviado por email
- Ou manualmente defina `email_verified_at` no banco:
```sql
UPDATE usuarios SET email_verified_at = NOW() WHERE email = 'teste@exemplo.com';
```

### **Passo 5: Testar Login Liberado**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "teste@exemplo.com",
    "senha": "123456"
  }'
```

**Resposta esperada:** Sucesso com token
```json
{
  "success": true,
  "message": "Login realizado com sucesso",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600,
    "usuario": {
      "id": "01JASF7...",
      "primeiro_nome": "Teste",
      "segundo_nome": "Usuario",
      "email": "teste@exemplo.com",
      "tipo_usuario": "usuario",
      "is_admin": false,
      "email_verificado": true
    }
  }
}
```

## 📋 Cenários de Teste

### ✅ **Cenário 1: Usuário Novo**
1. Registra conta → Recebe email de verificação
2. Tenta login sem verificar → Bloqueado
3. Clica no link de verificação → Email verificado
4. Faz login → Sucesso

### ✅ **Cenário 2: Email Perdido**
1. Usuário registrou mas perdeu email
2. Tenta login → Bloqueado com mensagem
3. Usa endpoint público para reenviar
4. Verifica email → Login liberado

### ✅ **Cenário 3: Email já Verificado**
1. Tenta reenviar verificação → Erro "já verificado"
2. Login funciona normalmente

## 🔧 **Endpoints Disponíveis**

### **Públicos (sem autenticação):**
- `POST /api/auth/login` - Login (com verificação de email)
- `POST /api/auth/registrar-usuario` - Registro
- `POST /api/auth/reenviar-verificacao-email-publico` - Reenvio público

### **Autenticados:**
- `POST /api/auth/reenviar-verificacao-email` - Reenvio autenticado
- `GET /api/auth/verificar-email/{id}/{hash}` - Verificação via link

## 🚨 **Códigos de Erro**

- **401** - Credenciais inválidas
- **403** - Email não verificado (novo!)
- **422** - Email já verificado (ao tentar reenviar)
- **404** - Usuário não encontrado

## 🎯 **Benefícios da Implementação**

- ✅ **Segurança**: Apenas usuários com email válido podem acessar
- ✅ **UX**: Mensagens claras sobre o que fazer
- ✅ **Flexibilidade**: Reenvio sem precisar estar logado
- ✅ **Auditoria**: Logs detalhados de todas as tentativas
- ✅ **Compatibilidade**: Não quebra usuários já verificados

---

## 🔄 **Comandos Úteis**

```bash
# Criar usuário de teste sem verificação
php artisan test:usuario-sem-verificacao teste@exemplo.com

# Verificar usuário manualmente no banco
php artisan tinker
>>> $user = App\Models\Usuario::where('email', 'teste@exemplo.com')->first();
>>> $user->markEmailAsVerified();

# Limpar usuário de teste
php artisan tinker
>>> App\Models\Usuario::where('email', 'teste@exemplo.com')->delete();
```