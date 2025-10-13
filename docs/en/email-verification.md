# Email Verification

## Overview

Complete email verification system that allows users to confirm their accounts through a link sent via email after registration.

## Verification Flow

### Flow Diagram

```
User                       Backend                     Email                    Frontend
   |                          |                          |                          |
   |--1. POST /register------>|                          |                          |
   |   (signup data)          |                          |                          |
   |                          |                          |                          |
   |                          |--2. Create user--------->|                          |
   |                          |    and send email        |                          |
   |                          |                          |                          |
   |<------3. Response--------|                          |                          |
   |   "Registration complete"|                          |                          |
   |   + JWT token            |                          |                          |
   |                          |                          |                          |
   |<-----------4. Receive email with link--------------|                          |
   |                          |                          |                          |
   |--5. Click link---------------------------------------->|                     |
   |   (URL varies by type)   |                          |    /cliente/verify      |
   |                          |                          |    or /admin/verify     |
   |                          |                          |                          |
   |--6. Frontend captures parameters----------------------------->|               |
   |   (id, hash, expires,    |                          |                          |
   |    signature)            |                          |                          |
   |                          |                          |                          |
   |<-7. POST /verify-email (parameters)-------------------|                     |
   |                          |                          |                          |
   |<------8. Email verified--|                          |                          |
   |   "Success!"             |                          |                          |
   |                          |                          |                          |
   |--9. Redirect to dashboard or login------------------------>|                   |
```

## API Endpoints

### 1. User Registration

During registration, the user automatically receives a verification email.

**Endpoint:** `POST /api/auth/registrar-usuario`

**Body:**
```json
{
  "primeiro_nome": "John",
  "segundo_nome": "Doe",
  "apelido": "johndoe",
  "email": "john@example.com",
  "senha": "MyPassw0rd!",
  "senha_confirmation": "MyPassw0rd!",
  "confirmacao_senha": "MyPassw0rd!",
  "telefone": "(11) 99999-9999",
  "numero_documento": "12345678901",
  "data_nascimento": "1990-05-15",
  "aceite_comunicacoes_email": true,
  "aceite_comunicacoes_sms": false,
  "aceite_comunicacoes_whatsapp": true,
  "aceite_termos_uso": true,
  "aceite_politica_privacidade": true
}
```

**Success Response (201):**
```json
{
  "sucesso": true,
  "mensagem": "Usuário registrado com sucesso",
  "dados": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "bearer",
    "expires_in": 3600,
    "usuario": {
      "id": "01HXXXXX...",
      "primeiro_nome": "John",
      "segundo_nome": "Doe",
      "email": "john@example.com",
      "tipo_usuario": "usuario",
      "email_verificado": false,
      "criado_em": "2025-10-13T10:30:00Z"
    },
    "mensagem_verificacao": "Um email de verificação foi enviado para john@example.com"
  }
}
```

**Notes:**
- Verification email is sent automatically after registration
- User can login without verifying email, but will have limited access
- Verification link expires in 60 minutes
- **The URL in the email varies by user type:**
  - **Clients** (`tipo_usuario = 'usuario'`): `/cliente/verificar-email`
  - **Administrators** (`tipo_usuario = 'administrador'`): `/admin/verificar-email`

### 2. Verify Email

After receiving the email and clicking the link, the frontend captures the URL parameters and calls this endpoint.

**Endpoint:** `POST /api/auth/verificar-email`

**Body:**
```json
{
  "id": "01HXXXXX...",
  "hash": "abc123def456...",
  "expires": 1697234567,
  "signature": "xyz789..."
}
```

**Success Response (200):**
```json
{
  "sucesso": true,
  "mensagem": "Email verificado com sucesso!",
  "dados": {
    "sucesso": true,
    "mensagem": "Email verificado com sucesso!",
    "usuario": {
      "email": "john@example.com",
      "email_verificado": true,
      "verificado_em": "2025-10-13T10:35:00.000000Z"
    }
  }
}
```

**Possible Errors:**

- **422 - Invalid or Expired Link:**
```json
{
  "sucesso": false,
  "mensagem": "Este link de verificação é inválido ou expirou.",
  "codigo": "LINK_INVALIDO"
}
```

- **422 - Email Already Verified:**
```json
{
  "sucesso": false,
  "mensagem": "Este email já foi verificado",
  "codigo": "JA_VERIFICADO"
}
```

### 3. Resend Verification Email (Authenticated)

For logged-in users who haven't verified their email yet.

**Endpoint:** `POST /api/auth/reenviar-verificacao-email`

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Success Response (200):**
```json
{
  "sucesso": true,
  "mensagem": "Email de verificação reenviado para john@example.com"
}
```

### 4. Resend Verification Email (Public)

For users who are not logged in.

**Endpoint:** `POST /api/auth/reenviar-verificacao-email-publico`

**Body:**
```json
{
  "email": "john@example.com"
}
```

**Success Response (200):**
```json
{
  "sucesso": true,
  "mensagem": "Email de verificação reenviado para john@example.com"
}
```

## Verification Email

The email sent includes:

- 🎨 Modern and responsive design
- ✉️ Email confirmation icon
- 🎉 Welcome message
- ⏱️ Link expiration information (60 minutes)
- 📋 List of benefits after verification
- ⚠️ Security warning
- 🔗 Main button + alternative link
- 📧 Company information
- 🎯 **Personalized URL based on user type**

**Template:** `resources/views/emails/email-verification.blade.php`

### URL Routing Logic

The system automatically determines the correct URL based on the `tipo_usuario` field in the database:

| User Type | DB Value | Generated URL |
|-----------|----------|---------------|
| Client | `'usuario'` | `{FRONTEND_URL}/cliente/verificar-email?id=...&hash=...&expires=...&signature=...` |
| Administrator | `'administrador'` | `{FRONTEND_URL}/admin/verificar-email?id=...&hash=...&expires=...&signature=...` |

**Code in `AuthService.php`:**
```php
// Define path based on user type
if ($usuario->tipo_usuario === 'administrador') {
    $path = '/admin/verificar-email';
} else {
    $path = '/cliente/verificar-email';
}

// Create frontend URL with required parameters
$verificationUrl = $frontendUrl . $path . 
    '?id=' . $usuario->id . 
    '&hash=' . sha1($usuario->getEmailForVerification()) .
    '&expires=' . $params['expires'] .
    '&signature=' . $params['signature'];
```

## Frontend - Verification Page

The frontend must create verification pages on different routes based on user type:

### For Clients (tipo_usuario = 'usuario'):
**Route:** `/cliente/verificar-email`

### For Administrators (tipo_usuario = 'administrador'):
**Route:** `/admin/verificar-email`

Both pages should:

1. **Capture URL parameters:**
   - `id`: User ID
   - `hash`: Verification hash
   - `expires`: Expiration timestamp
   - `signature`: Cryptographic signature

2. **Perform verification automatically:**
   - On page load, immediately call `POST /api/auth/verificar-email`
   - Send all 4 parameters in the request body

3. **Display feedback to user:**
   - Show loading during verification
   - Success: Display confirmation message + button to go to dashboard/login
   - Error (expired link): Show button to resend email
   - Error (already verified): Show informative message + button to login

**Example Implementation (React/Next.js):**

```typescript
// /cliente/verificar-email/page.tsx
'use client';

import { useEffect, useState } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';

export default function VerifyEmail() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const [status, setStatus] = useState<'loading' | 'success' | 'error'>('loading');
  const [message, setMessage] = useState('');
  const [code, setCode] = useState('');

  useEffect(() => {
    const verifyEmail = async () => {
      try {
        const response = await fetch('/api/auth/verificar-email', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id: searchParams.get('id'),
            hash: searchParams.get('hash'),
            expires: searchParams.get('expires'),
            signature: searchParams.get('signature'),
          }),
        });

        const data = await response.json();

        if (data.sucesso) {
          setStatus('success');
          setMessage(data.mensagem);
          // Redirect after 3 seconds
          setTimeout(() => router.push('/cliente/dashboard'), 3000);
        } else {
          setStatus('error');
          setMessage(data.mensagem);
          setCode(data.codigo);
        }
      } catch (error) {
        setStatus('error');
        setMessage('Error verifying email. Please try again.');
      }
    };

    verifyEmail();
  }, [searchParams, router]);

  if (status === 'loading') {
    return <div>Verifying your email...</div>;
  }

  if (status === 'success') {
    return (
      <div>
        <h1>✓ Email Verified!</h1>
        <p>{message}</p>
        <p>Redirecting to dashboard...</p>
      </div>
    );
  }

  return (
    <div>
      <h1>✗ Verification Error</h1>
      <p>{message}</p>
      {code === 'LINK_INVALIDO' && (
        <button onClick={() => router.push('/cliente/resend-verification')}>
          Resend Verification Email
        </button>
      )}
      {code === 'JA_VERIFICADO' && (
        <button onClick={() => router.push('/login')}>
          Go to Login
        </button>
      )}
    </div>
  );
}
```

**URL Examples:**

Client:
```
http://localhost:3000/cliente/verificar-email?id=01HXXXXX&hash=abc123&expires=1697234567&signature=xyz789
```

Administrator:
```
http://localhost:3000/admin/verificar-email?id=01HXXXXX&hash=abc123&expires=1697234567&signature=xyz789
```

## Configuration

### Environment Variables (.env)

Add to your `.env` file:

```env
# Frontend URL (where user will be redirected)
FRONTEND_URL=http://localhost:3000

# Email configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Security

### Implemented Measures:

1. **Signed URL:** The URL contains a cryptographic signature that prevents tampering
2. **Expiration:** Links expire in 60 minutes
3. **Email Hash:** Hash is generated based on user's email, ensuring uniqueness
4. **Single Use:** Email can only be verified once
5. **Rate Limiting:** Consider adding rate limiting on resend endpoint
6. **Audit Log:** All verification attempts are logged

### Additional Recommendations:

1. **Rate Limiting:** Add throttle to resend routes:
```php
Route::post('reenviar-verificacao-email-publico', [AuthController::class, 'reenviarVerificacaoEmailPublico'])
    ->middleware('throttle:3,60'); // 3 attempts per hour
```

2. **Resend Page:** Create a dedicated page to resend verification email

3. **Success Notification:** Consider sending a confirmation email after successful verification

4. **Limited Access:** Implement restrictions for unverified users

## Differences vs. Old System

### ❌ Old System (Laravel Native)
- Link opens directly in backend
- Backend renders Blade page
- Disconnected frontend experience
- Difficult to customize experience

### ✅ New System (Integrated with Frontend)
- Link redirects to frontend
- Frontend controls entire experience
- Verification via REST API
- Professional and customizable HTML email
- Consistent with password reset flow
- Better UX and error control

## User Experience Flow

1. **Registration:** User fills registration form
2. **Confirmation:** System confirms registration and informs about verification email
3. **Email:** User receives beautiful and professional email
4. **Click:** User clicks "Confirm Email" button
5. **Redirect:** Link opens specific page in frontend (/cliente or /admin)
6. **Auto Verification:** Frontend automatically verifies via API
7. **Feedback:** User sees success or error message
8. **Next Step:** System redirects to dashboard or login

## Testing

Run email verification tests:

```bash
# All verification tests
php artisan test --filter="EmailVerificationTest"

# Specific test
php artisan test --filter="usuario_pode_verificar_email_com_link_valido"
```

## Troubleshooting

### Email not being sent
1. Check MAIL settings in .env
2. Check logs in `storage/logs/laravel.log`
3. Test email sending: `php artisan tinker` → `Mail::raw('Test', function($m) { $m->to('your-email@example.com')->subject('Test'); });`

### Verification link doesn't work
1. Check if APP_URL is correct in .env
2. Check if FRONTEND_URL is configured
3. Confirm parameters are being captured correctly in frontend
4. Check error logs in backend

### Email verified but system doesn't recognize
1. Check `email_verified_at` field in database
2. Clear cache: `php artisan cache:clear`
3. Verify if `hasVerifiedEmail()` method is working

---

This system provides a modern and professional email verification experience, fully integrated with the frontend and following best practices for security and UX.
