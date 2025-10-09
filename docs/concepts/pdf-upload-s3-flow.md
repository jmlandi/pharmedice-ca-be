# 📄 PDF Upload & S3 Integration - Complete Flow

## ✅ Implementation Summary

The system is correctly implemented to:

1. **Receive PDF file** via multipart/form-data
2. **Upload to AWS S3** with unique naming
3. **Store S3 path** in PostgreSQL database
4. **Generate download URLs** when needed

## 🔄 Complete Flow Diagram

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Laravel API   │    │     AWS S3      │    │   PostgreSQL    │
│                 │    │                 │    │                 │    │                 │
├─────────────────┤    ├─────────────────┤    ├─────────────────┤    ├─────────────────┤
│                 │    │                 │    │                 │    │                 │
│ 1. Select PDF   │───▶│ 2. Receive File │    │                 │    │                 │
│    File         │    │    Validation   │    │                 │    │                 │
│                 │    │                 │    │                 │    │                 │
│                 │    │ 3. Generate     │───▶│ 4. Store File   │    │                 │
│                 │    │    Unique Name  │    │    /laudos/     │    │                 │
│                 │    │                 │    │    2024/10/     │    │                 │
│                 │    │                 │    │    uuid_file    │    │                 │
│                 │    │                 │    │                 │    │                 │
│                 │    │ 5. Save S3 Path │───────────────────────────▶│ 6. Insert       │
│                 │    │    to Database  │    │                 │    │    Record       │
│                 │    │                 │    │                 │    │                 │
│ 7. Success      │◀───│ 8. Return       │    │                 │    │                 │
│    Response     │    │    Response     │    │                 │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘    └─────────────────┘
```

## 🚀 API Usage Examples

### 1. Upload New PDF Laudo

```bash
curl -X POST http://localhost:8000/api/laudos \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "titulo=Exame de Sangue - João Silva" \
  -F "descricao=Resultados do hemograma completo" \
  -F "usuario_id=01234567-89ab-cdef-0123-456789abcdef" \
  -F "arquivo=@/path/to/exame_sangue.pdf"
```

**Response:**
```json
{
  "success": true,
  "message": "Laudo criado com sucesso",
  "data": {
    "id": "01234567-89ab-cdef-0123-456789abcdef",
    "usuario_id": "01234567-89ab-cdef-0123-456789abcdef",
    "titulo": "Exame de Sangue - João Silva",
    "descricao": "Resultados do hemograma completo",
    "url_arquivo": "laudos/2024/10/550e8400-e29b-41d4-a716-446655440000_1728123456_exame-sangue.pdf",
    "ativo": true,
    "created_at": "2024-10-09T10:30:00.000000Z",
    "updated_at": "2024-10-09T10:30:00.000000Z",
    "usuario": {
      "id": "01234567-89ab-cdef-0123-456789abcdef",
      "nome_completo": "Admin Sistema",
      "email": "admin@pharmedice.com"
    }
  }
}
```

### 2. Download PDF File

```bash
curl -X GET http://localhost:8000/api/laudos/01234567-89ab-cdef-0123-456789abcdef/download \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "url": "https://pharmedice-laudos.s3.us-east-1.amazonaws.com/laudos/2024/10/550e8400-e29b-41d4-a716-446655440000_1728123456_exame-sangue.pdf",
    "nome_arquivo": "550e8400-e29b-41d4-a716-446655440000_1728123456_exame-sangue.pdf",
    "nome_arquivo_original": "exame-sangue.pdf",
    "titulo": "Exame de Sangue - João Silva",
    "tamanho_arquivo": 245760,
    "content_type": "application/pdf"
  }
}
```

## 🗄️ Database Schema

The `laudos` table stores the S3 file path:

```sql
CREATE TABLE laudos (
    id UUID PRIMARY KEY,
    usuario_id UUID REFERENCES usuarios(id),  -- Who created the laudo
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    url_arquivo VARCHAR(500) NOT NULL,        -- S3 file path
    ativo BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Example record:**
```sql
INSERT INTO laudos VALUES (
    '01234567-89ab-cdef-0123-456789abcdef',
    'f47ac10b-58cc-4372-a567-0e02b2c3d479',
    'Exame de Sangue - João Silva',
    'Resultados do hemograma completo',
    'laudos/2024/10/550e8400-e29b-41d4-a716-446655440000_1728123456_exame-sangue.pdf',
    true,
    '2024-10-09 10:30:00',
    '2024-10-09 10:30:00'
);
```

## 📁 S3 File Organization

Files are organized in S3 with the following structure:

```
s3://pharmedice-laudos/
├── laudos/
│   ├── 2024/
│   │   ├── 01/          # January 2024
│   │   ├── 02/          # February 2024
│   │   ├── ...
│   │   └── 10/          # October 2024
│   │       ├── 550e8400-e29b-41d4-a716-446655440000_1728123456_exame-sangue.pdf
│   │       ├── 550e8400-e29b-41d4-a716-446655440001_1728123500_resultado-urina.pdf
│   │       └── ...
│   └── 2025/
│       ├── 01/
│       └── ...
```

**File naming convention:**
```
{UUID}_{timestamp}_{original-filename-slugified}.pdf

Example:
550e8400-e29b-41d4-a716-446655440000_1728123456_exame-sangue.pdf
```

## 🔧 Code Implementation Details

### LaudoService::uploadArquivo()

```php
private function uploadArquivo($arquivo): string
{
    // Preserva o nome original do arquivo
    $nomeOriginal = pathinfo($arquivo->getClientOriginalName(), PATHINFO_FILENAME);
    $extensao = $arquivo->getClientOriginalExtension();
    
    // Gera nome único mantendo referência ao original
    $nomeArquivo = Str::uuid() . '_' . time() . '_' . Str::slug($nomeOriginal) . '.' . $extensao;
    
    // Define o caminho no S3 organizando por ano/mês
    $diretorio = 'laudos/' . date('Y/m');
    
    // Faz upload para S3
    $path = Storage::disk('s3')->putFileAs(
        $diretorio,
        $arquivo,
        $nomeArquivo,
        'private' // Arquivo privado - só acessível via URLs assinadas
    );

    if (!$path) {
        throw new \Exception('Erro ao fazer upload do arquivo para o S3', 500);
    }

    return $path; // Returns: "laudos/2024/10/uuid_timestamp_filename.pdf"
}
```

### LaudoService::criar()

```php
public function criar(LaudoDTO $laudoDTO): Laudo
{
    $dados = $laudoDTO->toArray();

    // Se tem arquivo, faz upload para S3
    if ($laudoDTO->hasFile()) {
        $dados['url_arquivo'] = $this->uploadArquivo($laudoDTO->arquivo);
    }

    return Laudo::create($dados);
}
```

## 🔐 Security Features

1. **File Validation**: Only PDF files up to 10MB
2. **Private S3 Storage**: Files are stored as private
3. **Unique Naming**: UUID prevents filename conflicts
4. **JWT Authentication**: Only authenticated users can upload/download
5. **Role-based Access**: Only admins can create laudos

## 🧪 Testing the Implementation

### 1. Test with cURL

```bash
# Create a test PDF file
echo "Test PDF content" > test_laudo.pdf

# Upload the file
curl -X POST http://localhost:8000/api/laudos \
  -H "Authorization: Bearer $(echo 'YOUR_JWT_TOKEN')" \
  -F "titulo=Test Laudo" \
  -F "descricao=Test description" \
  -F "arquivo=@test_laudo.pdf"
```

### 2. Test with Postman

1. **Method**: POST
2. **URL**: `http://localhost:8000/api/laudos`
3. **Headers**: 
   - `Authorization: Bearer YOUR_JWT_TOKEN`
4. **Body**: form-data
   - `titulo`: "Test Laudo"
   - `descricao`: "Test description"
   - `arquivo`: [Select PDF file]

## ✅ Verification Checklist

- [ ] ✅ **File Upload**: Controller receives multipart/form-data
- [ ] ✅ **File Validation**: PDF only, max 10MB
- [ ] ✅ **S3 Upload**: File stored in AWS S3
- [ ] ✅ **Database Storage**: S3 path saved in PostgreSQL
- [ ] ✅ **Unique Naming**: UUID + timestamp prevents conflicts
- [ ] ✅ **Download URLs**: Generate accessible URLs for frontend
- [ ] ✅ **Security**: Private storage with authentication

The complete PDF upload and S3 integration is working correctly! 🎉