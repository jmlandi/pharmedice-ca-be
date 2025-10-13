# Exemplos de Teste - Recuperação de Senha

## Cenário 1: Recuperação de Senha para Cliente

### 1. Criar um usuário cliente (se ainda não existir)
```bash
curl -X POST http://localhost:8000/api/auth/registrar-usuario \
  -H "Content-Type: application/json" \
  -d '{
    "primeiro_nome": "João",
    "segundo_nome": "Silva",
    "apelido": "joaosilva",
    "email": "joao.cliente@exemplo.com",
    "senha": "Senha@123",
    "senha_confirmation": "Senha@123",
    "confirmacao_senha": "Senha@123",
    "telefone": "(11) 98765-4321",
    "numero_documento": "12345678901",
    "data_nascimento": "1990-01-15",
    "aceite_termos_uso": true,
    "aceite_politica_privacidade": true
  }'
```

### 2. Solicitar recuperação de senha
```bash
curl -X POST http://localhost:8000/api/auth/solicitar-recuperacao-senha \
  -H "Content-Type: application/json" \
  -d '{
    "email": "joao.cliente@exemplo.com"
  }'
```

**Resposta esperada:**
```json
{
  "sucesso": true,
  "mensagem": "Se o email existir em nosso sistema, você receberá um link de recuperação de senha."
}
```

### 3. Verificar o email

O e-mail será enviado com um link como:
```
http://localhost:3000/cliente/redefinir-senha?token=abc123...&email=joao.cliente@exemplo.com
```

Note que o caminho é `/cliente/redefinir-senha` porque o usuário é do tipo "usuario".

### 4. Redefinir a senha
```bash
curl -X POST http://localhost:8000/api/auth/redefinir-senha \
  -H "Content-Type: application/json" \
  -d '{
    "email": "joao.cliente@exemplo.com",
    "token": "TOKEN_COPIADO_DO_EMAIL",
    "senha": "NovaSenha@456",
    "confirmacao_senha": "NovaSenha@456"
  }'
```

**Resposta esperada:**
```json
{
  "sucesso": true,
  "mensagem": "Senha redefinida com sucesso! Você já pode fazer login com sua nova senha.",
  "dados": {
    "email": "joao.cliente@exemplo.com",
    "nome": "João"
  }
}
```

### 5. Fazer login com a nova senha
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "joao.cliente@exemplo.com",
    "senha": "NovaSenha@456"
  }'
```

---

## Cenário 2: Recuperação de Senha para Administrador

### 1. Criar um usuário administrador (se ainda não existir)
```bash
curl -X POST http://localhost:8000/api/auth/registrar-admin \
  -H "Content-Type: application/json" \
  -d '{
    "primeiro_nome": "Maria",
    "segundo_nome": "Santos",
    "apelido": "mariaadmin",
    "email": "maria.admin@exemplo.com",
    "senha": "Admin@123",
    "senha_confirmation": "Admin@123",
    "confirmacao_senha": "Admin@123",
    "telefone": "(11) 91234-5678",
    "numero_documento": "98765432109",
    "data_nascimento": "1985-05-20",
    "aceite_termos_uso": true,
    "aceite_politica_privacidade": true
  }'
```

### 2. Solicitar recuperação de senha
```bash
curl -X POST http://localhost:8000/api/auth/solicitar-recuperacao-senha \
  -H "Content-Type: application/json" \
  -d '{
    "email": "maria.admin@exemplo.com"
  }'
```

### 3. Verificar o email

O e-mail será enviado com um link como:
```
http://localhost:3000/admin/redefinir-senha?token=xyz789...&email=maria.admin@exemplo.com
```

Note que o caminho é `/admin/redefinir-senha` porque o usuário é do tipo "administrador".

### 4. Redefinir a senha
```bash
curl -X POST http://localhost:8000/api/auth/redefinir-senha \
  -H "Content-Type: application/json" \
  -d '{
    "email": "maria.admin@exemplo.com",
    "token": "TOKEN_COPIADO_DO_EMAIL",
    "senha": "NovaAdmin@789",
    "confirmacao_senha": "NovaAdmin@789"
  }'
```

### 5. Fazer login com a nova senha
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "maria.admin@exemplo.com",
    "senha": "NovaAdmin@789"
  }'
```

---

## Cenário 3: Testes de Validação

### Teste 1: Email não existente (segurança)
```bash
curl -X POST http://localhost:8000/api/auth/solicitar-recuperacao-senha \
  -H "Content-Type: application/json" \
  -d '{
    "email": "naoexiste@exemplo.com"
  }'
```

**Resposta:** Mesma mensagem de sucesso (não revela se o email existe)

### Teste 2: Senhas não coincidem
```bash
curl -X POST http://localhost:8000/api/auth/redefinir-senha \
  -H "Content-Type: application/json" \
  -d '{
    "email": "joao.cliente@exemplo.com",
    "token": "TOKEN_VALIDO",
    "senha": "NovaSenha@123",
    "confirmacao_senha": "SenhaDiferente@123"
  }'
```

**Resposta esperada:** Erro 422 - "A confirmação da senha deve ser igual à senha"

### Teste 3: Senha fraca
```bash
curl -X POST http://localhost:8000/api/auth/redefinir-senha \
  -H "Content-Type: application/json" \
  -d '{
    "email": "joao.cliente@exemplo.com",
    "token": "TOKEN_VALIDO",
    "senha": "senha123",
    "confirmacao_senha": "senha123"
  }'
```

**Resposta esperada:** Erro 422 - "A senha deve conter pelo menos: 1 letra minúscula, 1 maiúscula, 1 número e 1 caractere especial"

### Teste 4: Token inválido
```bash
curl -X POST http://localhost:8000/api/auth/redefinir-senha \
  -H "Content-Type: application/json" \
  -d '{
    "email": "joao.cliente@exemplo.com",
    "token": "token_invalido_123",
    "senha": "NovaSenha@123",
    "confirmacao_senha": "NovaSenha@123"
  }'
```

**Resposta esperada:** Erro 422 - "Token de recuperação inválido ou expirado"

### Teste 5: Token expirado
Aguarde 60 minutos após solicitar a recuperação e tente usar o token.

**Resposta esperada:** Erro 422 - "Token de recuperação expirado. Solicite um novo link de recuperação."

### Teste 6: Usar o mesmo token duas vezes
Após redefinir a senha com sucesso, tente usar o mesmo token novamente.

**Resposta esperada:** Erro 422 - "Token de recuperação inválido ou expirado"

---

## Cenário 4: Teste usando Postman

### Coleção Postman

Crie uma coleção no Postman com as seguintes requisições:

1. **Solicitar Recuperação - Cliente**
   - Método: `POST`
   - URL: `{{base_url}}/api/auth/solicitar-recuperacao-senha`
   - Body (JSON):
   ```json
   {
     "email": "joao.cliente@exemplo.com"
   }
   ```

2. **Solicitar Recuperação - Admin**
   - Método: `POST`
   - URL: `{{base_url}}/api/auth/solicitar-recuperacao-senha`
   - Body (JSON):
   ```json
   {
     "email": "maria.admin@exemplo.com"
   }
   ```

3. **Redefinir Senha**
   - Método: `POST`
   - URL: `{{base_url}}/api/auth/redefinir-senha`
   - Body (JSON):
   ```json
   {
     "email": "{{email}}",
     "token": "{{token}}",
     "senha": "NovaSenha@123",
     "confirmacao_senha": "NovaSenha@123"
   }
   ```

### Variáveis de Ambiente

```
base_url = http://localhost:8000
email = (será preenchido manualmente)
token = (será copiado do email)
```

---

## Verificação Manual via Banco de Dados

### Verificar tokens criados
```sql
SELECT * FROM password_reset_tokens;
```

### Verificar tipo de usuário
```sql
SELECT id, primeiro_nome, email, tipo_usuario 
FROM usuarios 
WHERE email = 'joao.cliente@exemplo.com';
```

### Limpar tokens expirados manualmente (opcional)
```sql
DELETE FROM password_reset_tokens 
WHERE created_at < NOW() - INTERVAL 60 MINUTE;
```

---

## Checklist de Testes

- [ ] Cliente consegue solicitar recuperação de senha
- [ ] Administrador consegue solicitar recuperação de senha
- [ ] Email é enviado para clientes com URL `/cliente/redefinir-senha`
- [ ] Email é enviado para admins com URL `/admin/redefinir-senha`
- [ ] Token funciona corretamente
- [ ] Token expira após 60 minutos
- [ ] Token não pode ser reutilizado
- [ ] Senha é validada corretamente (requisitos de força)
- [ ] Senhas devem coincidir
- [ ] Email não existente retorna mensagem genérica de segurança
- [ ] Usuário inativo recebe erro apropriado
- [ ] Login funciona com a nova senha
- [ ] Logs são registrados corretamente
- [ ] Frontend redireciona para a página correta baseada no tipo de usuário
