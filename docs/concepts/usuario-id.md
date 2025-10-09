# 📋 Conceito: usuario_id em Laudos

## ⚠️ IMPORTANTE: Definição do campo usuario_id

O campo `usuario_id` na tabela `laudos` representa **quem CRIOU/ENVIOU o laudo**, não necessariamente o paciente do laudo.

## 🎯 Cenários de Uso

### Cenário 1: Administrador Criando Laudos
```json
{
  "usuario_id": "admin-123",  // ← Admin que está criando
  "titulo": "Hemograma - Paciente Maria Silva",
  "arquivo": "resultado_maria.pdf"
}
```
**Significado**: O administrador `admin-123` criou/enviou este laudo no sistema.

### Cenário 2: Cliente Enviando Documento Próprio
```json
{
  "usuario_id": "cliente-456",  // ← Cliente que está enviando
  "titulo": "Meu Exame de Vista",
  "arquivo": "meu_exame.pdf"
}
```
**Significado**: O cliente `cliente-456` enviou seu próprio documento.

### Cenário 3: Admin Criando em Nome de Outro Admin
```json
{
  "usuario_id": "admin-789",  // ← Outro admin especificado
  "titulo": "Laudo Especializado",
  "arquivo": "laudo_especializado.pdf"
}
```
**Significado**: Um admin está criando um laudo e atribuindo a criação ao `admin-789`.

## 🔍 Implicações no Sistema

### Para Consultas
```sql
-- Laudos CRIADOS por um usuário específico
SELECT * FROM laudos WHERE usuario_id = 'user-123';

-- Todos os laudos (independente de quem criou)
SELECT * FROM laudos WHERE ativo = true;
```

### Para Permissões
```php
// Apenas admins podem criar laudos
if (!$usuario->isAdmin()) {
    return 'Acesso negado';
}

// Admin pode criar para si ou para outro admin
if (!$usuario->isAdmin() && $request->usuario_id != $usuario->id) {
    return 'Só pode criar em seu próprio nome';
}
```

## 📊 Casos de Uso Reais

### 1. **Hospital/Clínica**
- **Admin (médico/recepcionista)** cria laudos de pacientes
- `usuario_id` = ID do funcionário que inseriu no sistema
- Título contém informações do paciente

### 2. **Portal do Paciente**
- **Cliente** pode enviar documentos próprios
- `usuario_id` = ID do próprio cliente
- Sistema permite self-service

### 3. **Laboratório**
- **Admin (técnico)** envia resultados
- `usuario_id` = ID do técnico responsável
- Rastreabilidade de quem enviou cada resultado

## 🏗️ Estrutura de Dados

```php
// Model Laudo
public function usuario(): BelongsTo 
{
    return $this->belongsTo(Usuario::class); // Quem criou
}

public function criador(): BelongsTo 
{
    return $this->belongsTo(Usuario::class, 'usuario_id'); // Alias claro
}
```

## 🚀 Benefícios desta Abordagem

1. **Auditoria**: Sempre sabemos quem inseriu cada laudo
2. **Responsabilidade**: Controle de quem fez upload
3. **Flexibilidade**: Admin pode criar para diferentes contextos
4. **Rastreabilidade**: Histórico completo de ações
5. **Segurança**: Controle de acesso por criador

## 💡 Dica para o Frontend

No frontend, você pode:

```javascript
// Mostrar quem criou o laudo
function exibirLaudo(laudo) {
  return `
    Título: ${laudo.titulo}
    Criado por: ${laudo.usuario.nome_completo}
    Data: ${laudo.created_at}
  `;
}

// Filtrar laudos por criador
function filtrarPorCriador(usuarioId) {
  return laudos.filter(laudo => laudo.usuario_id === usuarioId);
}
```

## ✅ Resumo

- ✅ `usuario_id` = **Quem criou o laudo**
- ✅ **Não** é necessariamente o paciente
- ✅ Usado para **auditoria e controle**
- ✅ **Admins** podem criar para qualquer usuário
- ✅ **Clientes** só podem criar para si mesmos
- ✅ **Todos** podem consultar qualquer laudo

Esta abordagem garante **flexibilidade** e **controle total** sobre quem pode fazer o quê no sistema! 🎯