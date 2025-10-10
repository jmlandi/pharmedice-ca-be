# User ID Concept in Documents (Laudos)

## Important Definition

The `usuario_id` field in the `laudos` table represents **who CREATED/UPLOADED the document**, not necessarily the patient the document refers to.

## Use Case Scenarios

### Scenario 1: Administrator Creating Documents
```json
{
  "usuario_id": "admin-123",  // ← Admin who is creating
  "titulo": "Blood Test - Patient Maria Silva",
  "arquivo": "maria_results.pdf"
}
```
**Meaning**: Administrator `admin-123` created/uploaded this document in the system.

### Scenario 2: Client Uploading Own Document
```json
{
  "usuario_id": "client-456",  // ← Client uploading their own document
  "titulo": "My X-Ray Results",
  "arquivo": "my_xray.pdf"
}
```
**Meaning**: Client `client-456` uploaded their own document.

### Scenario 3: Admin Uploading for Specific Client
```json
{
  "usuario_id": "client-789",  // ← Target client who will own the document
  "titulo": "Lab Results - John Doe",
  "arquivo": "john_lab_results.pdf"
}
```
**Meaning**: Admin is creating a document that will belong to client `client-789`.

## Database Relationship

```sql
-- The usuario_id creates a foreign key relationship
ALTER TABLE laudos 
ADD CONSTRAINT fk_laudos_usuario 
FOREIGN KEY (usuario_id) REFERENCES usuarios(id);
```

This ensures:
- **Data Integrity**: Every document must have a valid user owner
- **Access Control**: Users can only see documents where they are the owner
- **Audit Trail**: We know who owns each document in the system

## Access Control Logic

### For Regular Users (Clients)
```php
// Users can only see their own documents
$laudos = Laudo::where('usuario_id', auth()->user()->id)
    ->where('ativo', true)
    ->get();
```

### For Administrators
```php
// Admins can see all documents
$laudos = Laudo::where('ativo', true)->get();

// Or filter by specific user
$laudos = Laudo::where('usuario_id', $targetUserId)
    ->where('ativo', true)
    ->get();
```

## API Endpoint Behavior

### Document Creation (Admin Only)
```http
POST /api/laudos
Authorization: Bearer {admin_token}
Content-Type: multipart/form-data

{
    "usuario_id": "01HXXXXX-client-id",  // Target user who will own document
    "titulo": "Medical Report",
    "descricao": "Patient examination results",
    "arquivo": [PDF_FILE]
}
```

### Document Listing (User Perspective)
```http
GET /api/laudos
Authorization: Bearer {user_token}

// Returns only documents where usuario_id = authenticated user's ID
```

### My Documents Endpoint
```http
GET /api/laudos/meus-laudos  
Authorization: Bearer {user_token}

// Explicitly returns user's own documents
// Same as /api/laudos but more explicit intent
```

## Business Logic Implications

### Document Ownership Transfer
```php
// If needed to transfer document ownership (rare case)
$laudo = Laudo::find($id);
$laudo->usuario_id = $newOwnerId;
$laudo->save();

// Log the ownership change for audit
Log::info('Document ownership transferred', [
    'document_id' => $id,
    'from_user' => $oldOwnerId,
    'to_user' => $newOwnerId,
    'transferred_by' => auth()->user()->id
]);
```

### Multi-User Access (Future Enhancement)
If future requirements need multiple users to access the same document:

```sql
-- Could create a separate access table
CREATE TABLE laudo_access (
    laudo_id CHAR(26) NOT NULL,
    usuario_id CHAR(26) NOT NULL,
    permission_type VARCHAR(20) DEFAULT 'read', -- 'read', 'write', 'admin'
    granted_by CHAR(26) NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (laudo_id, usuario_id)
);
```

But currently, the system uses simple ownership via `usuario_id`.

## Validation Rules

### Document Creation Validation
```php
// Validation ensures usuario_id is valid
'usuario_id' => [
    'required',
    'string',
    'exists:usuarios,id',  // Must exist in users table
    'size:26'              // Must be valid ULID length
]
```

### Access Permission Validation
```php
// Before document operations, check ownership
if (!auth()->user()->isAdmin() && $laudo->usuario_id !== auth()->user()->id) {
    abort(403, 'Access denied. You can only access your own documents.');
}
```

## Common Queries

### Get User's Document Count
```sql
SELECT COUNT(*) as total_documents 
FROM laudos 
WHERE usuario_id = ? AND ativo = true;
```

### Get Documents by Date Range for User
```sql
SELECT * FROM laudos 
WHERE usuario_id = ? 
  AND ativo = true 
  AND created_at BETWEEN ? AND ?
ORDER BY created_at DESC;
```

### Admin Report: Documents per User
```sql
SELECT 
    u.primeiro_nome,
    u.segundo_nome,
    u.email,
    COUNT(l.id) as total_documents
FROM usuarios u 
LEFT JOIN laudos l ON u.id = l.usuario_id AND l.ativo = true
GROUP BY u.id, u.primeiro_nome, u.segundo_nome, u.email
ORDER BY total_documents DESC;
```

## Key Takeaways

1. **`usuario_id` = Document Owner**: This field determines who owns and can access the document
2. **Not Patient ID**: This is not necessarily the patient the document refers to
3. **Access Control Basis**: All permission checks are based on this field
4. **Admin Override**: Administrators can create documents for any user
5. **Data Integrity**: Foreign key constraint ensures valid user references

This design provides clear ownership semantics while allowing administrators to manage documents for all users in the system.