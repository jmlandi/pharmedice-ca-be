# Conceito de ID de Usuário em Documentos (Laudos)

## Definição Importante

O campo `usuario_id` na tabela `laudos` representa **quem CRIOU/ENVIOU o documento**, não necessariamente o paciente a quem o documento se refere.

## Cenários de Uso

### Cenário 1: Administrador Criando Documentos
```json
{
  "usuario_id": "admin-123",  // ← Admin que está criando
  "titulo": "Exame de Sangue - Paciente Maria Silva",
  "arquivo": "resultados_maria.pdf"
}
```
**Significado**: O administrador `admin-123` criou/enviou este documento no sistema.

### Cenário 2: Cliente Enviando Documento Próprio
```json
{
  "usuario_id": "cliente-456",  // ← Cliente enviando seu próprio documento
  "titulo": "Meus Resultados de Raio-X",
  "arquivo": "meu_raio_x.pdf"
}
```
**Significado**: Cliente `cliente-456` enviou seu próprio documento.

### Cenário 3: Admin Enviando para Cliente Específico
```json
{
  "usuario_id": "cliente-789",  // ← Cliente alvo que será dono do documento
  "titulo": "Resultados Laboratoriais - João Silva",
  "arquivo": "resultados_lab_joao.pdf"
}
```
**Significado**: Admin está criando um documento que pertencerá ao cliente `cliente-789`.

## Relacionamento no Banco de Dados

```sql
-- O usuario_id cria um relacionamento de chave estrangeira
ALTER TABLE laudos 
ADD CONSTRAINT fk_laudos_usuario 
FOREIGN KEY (usuario_id) REFERENCES usuarios(id);
```

Isso garante:
- **Integridade de Dados**: Todo documento deve ter um proprietário válido
- **Controle de Acesso**: Usuários só podem ver documentos onde são proprietários
- **Trilha de Auditoria**: Sabemos quem possui cada documento no sistema

## Lógica de Controle de Acesso

### Para Usuários Regulares (Clientes)
```php
// Usuários só podem ver seus próprios documentos
$laudos = Laudo::where('usuario_id', auth()->user()->id)
    ->where('ativo', true)
    ->get();
```

### Para Administradores
```php
// Admins podem ver todos os documentos
$laudos = Laudo::where('ativo', true)->get();

// Ou filtrar por usuário específico
$laudos = Laudo::where('usuario_id', $targetUserId)
    ->where('ativo', true)
    ->get();
```

## Comportamento dos Endpoints da API

### Criação de Documento (Apenas Admin)
```http
POST /api/laudos
Authorization: Bearer {admin_token}
Content-Type: multipart/form-data

{
    "usuario_id": "01HXXXXX-client-id",  // Usuário alvo que será dono do documento
    "titulo": "Relatório Médico",
    "descricao": "Resultados de exame do paciente",
    "arquivo": [ARQUIVO_PDF]
}
```

### Listagem de Documentos (Perspectiva do Usuário)
```http
GET /api/laudos
Authorization: Bearer {user_token}

// Retorna apenas documentos onde usuario_id = ID do usuário autenticado
```

### Endpoint Meus Documentos
```http
GET /api/laudos/meus-laudos  
Authorization: Bearer {user_token}

// Explicitamente retorna documentos próprios do usuário
// Mesmo que /api/laudos mas com intenção mais explícita
```

## Implicações da Lógica de Negócio

### Transferência de Propriedade de Documento
```php
// Se necessário transferir propriedade de documento (caso raro)
$laudo = Laudo::find($id);
$laudo->usuario_id = $newOwnerId;
$laudo->save();

// Log da mudança de propriedade para auditoria
Log::info('Propriedade de documento transferida', [
    'document_id' => $id,
    'from_user' => $oldOwnerId,
    'to_user' => $newOwnerId,
    'transferred_by' => auth()->user()->id
]);
```

### Acesso Multi-Usuário (Melhoria Futura)
Se requisitos futuros precisarem que múltiplos usuários acessem o mesmo documento:

```sql
-- Poderia criar uma tabela de acesso separada
CREATE TABLE laudo_access (
    laudo_id CHAR(26) NOT NULL,
    usuario_id CHAR(26) NOT NULL,
    permission_type VARCHAR(20) DEFAULT 'read', -- 'read', 'write', 'admin'
    granted_by CHAR(26) NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (laudo_id, usuario_id)
);
```

Mas atualmente, o sistema usa propriedade simples via `usuario_id`.

## Regras de Validação

### Validação de Criação de Documento
```php
// Validação garante que usuario_id é válido
'usuario_id' => [
    'required',
    'string',
    'exists:usuarios,id',  // Deve existir na tabela usuários
    'size:26'              // Deve ter comprimento válido de ULID
]
```

### Validação de Permissão de Acesso
```php
// Antes de operações de documento, verificar propriedade
if (!auth()->user()->isAdmin() && $laudo->usuario_id !== auth()->user()->id) {
    abort(403, 'Acesso negado. Você só pode acessar seus próprios documentos.');
}
```

## Consultas Comuns

### Obter Contagem de Documentos do Usuário
```sql
SELECT COUNT(*) as total_documentos 
FROM laudos 
WHERE usuario_id = ? AND ativo = true;
```

### Obter Documentos por Intervalo de Data para Usuário
```sql
SELECT * FROM laudos 
WHERE usuario_id = ? 
  AND ativo = true 
  AND created_at BETWEEN ? AND ?
ORDER BY created_at DESC;
```

### Relatório Admin: Documentos por Usuário
```sql
SELECT 
    u.primeiro_nome,
    u.segundo_nome,
    u.email,
    COUNT(l.id) as total_documentos
FROM usuarios u 
LEFT JOIN laudos l ON u.id = l.usuario_id AND l.ativo = true
GROUP BY u.id, u.primeiro_nome, u.segundo_nome, u.email
ORDER BY total_documentos DESC;
```

## Pontos-Chave

1. **`usuario_id` = Proprietário do Documento**: Este campo determina quem possui e pode acessar o documento
2. **Não é ID do Paciente**: Não é necessariamente o paciente a quem o documento se refere
3. **Base do Controle de Acesso**: Todas as verificações de permissão são baseadas neste campo
4. **Override de Admin**: Administradores podem criar documentos para qualquer usuário
5. **Integridade de Dados**: Restrição de chave estrangeira garante referências válidas de usuário

Este design fornece semântica clara de propriedade enquanto permite que administradores gerenciem documentos para todos os usuários no sistema.