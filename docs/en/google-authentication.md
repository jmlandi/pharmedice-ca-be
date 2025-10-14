# Google OAuth Authentication

This guide describes how to implement Google authentication in the Pharmedice Customer Area frontend application.

## Table of Contents

- [Overview](#overview)
- [Backend Configuration](#backend-configuration)
- [Authentication Flow](#authentication-flow)
- [Frontend Integration](#frontend-integration)
- [Implementation Examples](#implementation-examples)
- [Error Handling](#error-handling)
- [FAQ](#faq)

---

## Overview

Google OAuth authentication allows users to log in or create accounts using their Google credentials, offering:

- **Fast and secure login**: No need to create and remember passwords
- **Account linking**: Existing users can link their Google accounts
- **Automatically verified email**: Google already validates the user's email
- **Familiar experience**: Login interface that users already know

### How It Works

1. User clicks "Sign in with Google" on the frontend
2. Frontend requests redirect URL from backend
3. User is redirected to Google's login page
4. After authentication, Google redirects back to backend callback
5. Backend processes data and returns a JWT token
6. Frontend stores the token and user is authenticated

---

## Backend Configuration

### 1. Create Project in Google Cloud Console

1. Access the [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the **Google+ API**

### 2. Create OAuth 2.0 Credentials

1. Go to **APIs & Services > Credentials**
2. Click **Create Credentials > OAuth client ID**
3. Select **Web application**
4. Configure authorized URLs:

   **Authorized JavaScript origins:**
   ```
   http://localhost:3000
   https://your-domain.com
   ```

   **Authorized redirect URIs:**
   ```
   http://localhost:8000/api/auth/google/callback
   https://api.your-domain.com/api/auth/google/callback
   ```

5. Copy the **Client ID** and **Client Secret**

### 3. Configure Environment Variables

Add the following variables to the backend `.env` file:

```env
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback
```

**Important:** Change `GOOGLE_REDIRECT_URI` to the production URL when deploying.

### 4. Run Migrations

Run the migration to add the necessary fields to the users table:

```bash
php artisan migrate
```

This migration adds the following fields:
- `google_id`: Unique user identifier in Google
- `provider`: OAuth provider (e.g., "google")
- `avatar`: User's avatar URL
- Makes fields optional: `senha`, `telefone`, `numero_documento`, `data_nascimento`, `apelido`

---

## Authentication Flow

### Sequence Diagram

```
┌──────────┐         ┌──────────┐         ┌──────────┐         ┌──────────┐
│ Frontend │         │ Backend  │         │  Google  │         │ Database │
└────┬─────┘         └────┬─────┘         └────┬─────┘         └────┬─────┘
     │                    │                    │                    │
     │ 1. GET /auth/google│                    │                    │
     ├───────────────────>│                    │                    │
     │                    │                    │                    │
     │ 2. Redirect URL    │                    │                    │
     │<───────────────────┤                    │                    │
     │                    │                    │                    │
     │ 3. Redirect to Google Auth             │                    │
     ├────────────────────────────────────────>│                    │
     │                    │                    │                    │
     │ 4. User authenticates                   │                    │
     │<────────────────────────────────────────┤                    │
     │                    │                    │                    │
     │ 5. GET /auth/google/callback?code=...   │                    │
     │                    │<───────────────────┤                    │
     │                    │                    │                    │
     │                    │ 6. Exchange code for user data          │
     │                    ├────────────────────>│                    │
     │                    │                    │                    │
     │                    │ 7. User data       │                    │
     │                    │<────────────────────┤                    │
     │                    │                    │                    │
     │                    │ 8. Find/Create user│                    │
     │                    ├────────────────────────────────────────>│
     │                    │                    │                    │
     │                    │ 9. User record     │                    │
     │                    │<────────────────────────────────────────┤
     │                    │                    │                    │
     │ 10. JWT Token + User data              │                    │
     │<───────────────────┤                    │                    │
     │                    │                    │                    │
```

### Detailed Step by Step

#### 1. Start Authentication

**Endpoint:** `GET /api/auth/google`

The frontend makes a request to get the Google redirect URL.

**Success Response (200):**
```json
{
  "sucesso": true,
  "mensagem": "Redirecione o usuário para a URL fornecida",
  "dados": {
    "redirect_url": "https://accounts.google.com/o/oauth2/auth?..."
  }
}
```

#### 2. Redirect to Google

The frontend redirects the user to the received `redirect_url`. Google displays its login/authorization interface.

#### 3. Google Callback

After user authorization, Google redirects to:
```
GET /api/auth/google/callback?code=AUTHORIZATION_CODE&state=...
```

The backend:
- Validates the authorization code
- Gets user data from Google (name, email, photo)
- Checks if user already exists:
  - **If exists by `google_id`**: Logs in
  - **If exists by `email`**: Links Google account and logs in
  - **If doesn't exist**: Creates new account and logs in

**Success Response (200):**
```json
{
  "sucesso": true,
  "mensagem": "Autenticação com Google realizada com sucesso",
  "dados": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "bearer",
    "expires_in": 3600,
    "usuario": {
      "id": "01HN2P3Q4R5S6T7U8V9W0X1Y2Z",
      "primeiro_nome": "John",
      "segundo_nome": "Doe",
      "email": "john.doe@gmail.com",
      "tipo_usuario": "usuario",
      "is_admin": false,
      "email_verificado": true,
      "avatar": "https://lh3.googleusercontent.com/..."
    }
  }
}
```

---

## Frontend Integration

### Next.js with React

#### 1. Create Authentication Hook

```typescript
// hooks/useGoogleAuth.ts
import { useState } from 'react';
import { useRouter } from 'next/navigation';

interface GoogleAuthResponse {
  access_token: string;
  token_type: string;
  expires_in: number;
  usuario: {
    id: string;
    primeiro_nome: string;
    segundo_nome: string;
    email: string;
    tipo_usuario: string;
    is_admin: boolean;
    email_verificado: boolean;
    avatar?: string;
  };
}

export const useGoogleAuth = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const router = useRouter();
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

  const loginWithGoogle = async () => {
    try {
      setLoading(true);
      setError(null);

      // 1. Get Google redirect URL
      const response = await fetch(`${API_URL}/auth/google`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error('Error starting Google authentication');
      }

      const data = await response.json();

      if (!data.sucesso) {
        throw new Error(data.mensagem || 'Error starting authentication');
      }

      // 2. Save state for later validation (optional but recommended)
      const state = Math.random().toString(36).substring(7);
      sessionStorage.setItem('oauth_state', state);

      // 3. Open popup window with Google URL
      const width = 500;
      const height = 600;
      const left = window.screen.width / 2 - width / 2;
      const top = window.screen.height / 2 - height / 2;

      const popup = window.open(
        data.dados.redirect_url,
        'Google Login',
        `width=${width},height=${height},left=${left},top=${top}`
      );

      // 4. Listen for message from popup window
      window.addEventListener('message', async (event) => {
        // Verify origin for security
        if (event.origin !== window.location.origin) {
          return;
        }

        if (event.data.type === 'GOOGLE_AUTH_SUCCESS') {
          const authData: GoogleAuthResponse = event.data.data;
          
          // Save token and user data
          localStorage.setItem('access_token', authData.access_token);
          localStorage.setItem('user', JSON.stringify(authData.usuario));

          // Close popup
          popup?.close();

          // Redirect to dashboard
          router.push('/dashboard');
        } else if (event.data.type === 'GOOGLE_AUTH_ERROR') {
          setError(event.data.message || 'Authentication error');
          popup?.close();
        }
      });

    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unknown error');
    } finally {
      setLoading(false);
    }
  };

  return { loginWithGoogle, loading, error };
};
```

#### 2. Callback Page

Create a page to process the Google callback:

```typescript
// app/auth/google/callback/page.tsx
'use client';

import { useEffect, useState } from 'react';
import { useSearchParams } from 'next/navigation';

export default function GoogleCallbackPage() {
  const searchParams = useSearchParams();
  const [status, setStatus] = useState<'loading' | 'success' | 'error'>('loading');
  const [message, setMessage] = useState('Processing authentication...');

  useEffect(() => {
    const processCallback = async () => {
      try {
        const code = searchParams.get('code');
        const state = searchParams.get('state');

        if (!code) {
          throw new Error('Authorization code not found');
        }

        // Optional: Validate state
        const savedState = sessionStorage.getItem('oauth_state');
        if (savedState && state !== savedState) {
          throw new Error('Invalid state - possible CSRF attack');
        }

        // Make request to backend to process callback
        const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';
        const response = await fetch(
          `${API_URL}/auth/google/callback?code=${code}&state=${state}`,
          {
            method: 'GET',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
          }
        );

        if (!response.ok) {
          throw new Error('Error processing authentication');
        }

        const data = await response.json();

        if (!data.sucesso) {
          throw new Error(data.mensagem || 'Authentication error');
        }

        // Send data to parent window (opener)
        if (window.opener) {
          window.opener.postMessage(
            {
              type: 'GOOGLE_AUTH_SUCCESS',
              data: data.dados,
            },
            window.location.origin
          );
        }

        setStatus('success');
        setMessage('Authentication successful! You can close this window.');

        // Automatically close window after 2 seconds
        setTimeout(() => {
          window.close();
        }, 2000);

      } catch (err) {
        console.error('Error in Google callback:', err);
        setStatus('error');
        setMessage(err instanceof Error ? err.message : 'Unknown error');

        // Send error to parent window
        if (window.opener) {
          window.opener.postMessage(
            {
              type: 'GOOGLE_AUTH_ERROR',
              message: err instanceof Error ? err.message : 'Unknown error',
            },
            window.location.origin
          );
        }

        // Close window after 3 seconds
        setTimeout(() => {
          window.close();
        }, 3000);
      }
    };

    processCallback();
  }, [searchParams]);

  return (
    <div className="flex items-center justify-center min-h-screen bg-gray-100">
      <div className="bg-white p-8 rounded-lg shadow-md max-w-md w-full text-center">
        {status === 'loading' && (
          <>
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
            <p className="text-gray-700">{message}</p>
          </>
        )}
        
        {status === 'success' && (
          <>
            <div className="text-green-500 text-5xl mb-4">✓</div>
            <p className="text-gray-700">{message}</p>
          </>
        )}
        
        {status === 'error' && (
          <>
            <div className="text-red-500 text-5xl mb-4">✗</div>
            <p className="text-red-600 font-semibold">Authentication Error</p>
            <p className="text-gray-700 mt-2">{message}</p>
            <button
              onClick={() => window.close()}
              className="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
            >
              Close
            </button>
          </>
        )}
      </div>
    </div>
  );
}
```

#### 3. Login Button Component

```typescript
// components/GoogleLoginButton.tsx
'use client';

import { useGoogleAuth } from '@/hooks/useGoogleAuth';

export default function GoogleLoginButton() {
  const { loginWithGoogle, loading, error } = useGoogleAuth();

  return (
    <div className="w-full">
      <button
        onClick={loginWithGoogle}
        disabled={loading}
        className="w-full flex items-center justify-center gap-3 px-4 py-3 border border-gray-300 rounded-lg shadow-sm bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        {loading ? (
          <>
            <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-gray-900"></div>
            <span className="text-gray-700 font-medium">Authenticating...</span>
          </>
        ) : (
          <>
            <svg className="w-5 h-5" viewBox="0 0 24 24">
              <path
                fill="#4285F4"
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
              />
              <path
                fill="#34A853"
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
              />
              <path
                fill="#FBBC05"
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
              />
              <path
                fill="#EA4335"
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
              />
            </svg>
            <span className="text-gray-700 font-medium">Continue with Google</span>
          </>
        )}
      </button>
      
      {error && (
        <p className="mt-2 text-sm text-red-600 text-center">{error}</p>
      )}
    </div>
  );
}
```

#### 4. Usage in Login Page

```typescript
// app/login/page.tsx
import GoogleLoginButton from '@/components/GoogleLoginButton';

export default function LoginPage() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50">
      <div className="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow">
        <div>
          <h2 className="text-center text-3xl font-bold text-gray-900">
            Sign in to your account
          </h2>
        </div>
        
        {/* Traditional login form */}
        <form className="mt-8 space-y-6">
          {/* ... email and password fields ... */}
        </form>

        {/* Divider */}
        <div className="relative my-6">
          <div className="absolute inset-0 flex items-center">
            <div className="w-full border-t border-gray-300"></div>
          </div>
          <div className="relative flex justify-center text-sm">
            <span className="px-2 bg-white text-gray-500">Or</span>
          </div>
        </div>

        {/* Google Button */}
        <GoogleLoginButton />
      </div>
    </div>
  );
}
```

---

## Implementation Examples

### React (Vite/CRA)

```typescript
// hooks/useGoogleAuth.ts
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';

export const useGoogleAuth = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const navigate = useNavigate();
  const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

  const loginWithGoogle = async () => {
    try {
      setLoading(true);
      setError(null);

      const response = await fetch(`${API_URL}/auth/google`);
      const data = await response.json();

      if (!data.sucesso) {
        throw new Error(data.mensagem);
      }

      // Open popup
      const width = 500;
      const height = 600;
      const left = window.screen.width / 2 - width / 2;
      const top = window.screen.height / 2 - height / 2;

      const popup = window.open(
        data.dados.redirect_url,
        'Google Login',
        `width=${width},height=${height},left=${left},top=${top}`
      );

      // Listen for message
      window.addEventListener('message', (event) => {
        if (event.origin !== window.location.origin) return;

        if (event.data.type === 'GOOGLE_AUTH_SUCCESS') {
          localStorage.setItem('access_token', event.data.data.access_token);
          localStorage.setItem('user', JSON.stringify(event.data.data.usuario));
          popup?.close();
          navigate('/dashboard');
        } else if (event.data.type === 'GOOGLE_AUTH_ERROR') {
          setError(event.data.message);
          popup?.close();
        }
      });

    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unknown error');
    } finally {
      setLoading(false);
    }
  };

  return { loginWithGoogle, loading, error };
};
```

### Vue.js 3 (Composition API)

```typescript
// composables/useGoogleAuth.ts
import { ref } from 'vue';
import { useRouter } from 'vue-router';

export const useGoogleAuth = () => {
  const loading = ref(false);
  const error = ref<string | null>(null);
  const router = useRouter();
  const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

  const loginWithGoogle = async () => {
    try {
      loading.value = true;
      error.value = null;

      const response = await fetch(`${API_URL}/auth/google`);
      const data = await response.json();

      if (!data.sucesso) {
        throw new Error(data.mensagem);
      }

      const width = 500;
      const height = 600;
      const left = window.screen.width / 2 - width / 2;
      const top = window.screen.height / 2 - height / 2;

      const popup = window.open(
        data.dados.redirect_url,
        'Google Login',
        `width=${width},height=${height},left=${left},top=${top}`
      );

      window.addEventListener('message', (event) => {
        if (event.origin !== window.location.origin) return;

        if (event.data.type === 'GOOGLE_AUTH_SUCCESS') {
          localStorage.setItem('access_token', event.data.data.access_token);
          localStorage.setItem('user', JSON.stringify(event.data.data.usuario));
          popup?.close();
          router.push('/dashboard');
        } else if (event.data.type === 'GOOGLE_AUTH_ERROR') {
          error.value = event.data.message;
          popup?.close();
        }
      });

    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Unknown error';
    } finally {
      loading.value = false;
    }
  };

  return { loginWithGoogle, loading, error };
};
```

---

## Error Handling

### Common Errors

| Error | Cause | Solution |
|-------|-------|----------|
| `Error authenticating with Google. Please try again.` | Invalid OAuth state | Clear cookies/session and try again |
| `Invalid credentials` | Incorrect Client ID/Secret | Check `.env` configuration |
| `Redirect URI mismatch` | Unauthorized callback URL | Add URL in Google Cloud Console |
| `Inactive user` | Account disabled in system | Contact administrator |

### Frontend Error Handling Example

```typescript
try {
  await loginWithGoogle();
} catch (error) {
  if (error.message.includes('inactive')) {
    showNotification('Your account has been disabled. Contact support.');
  } else if (error.message.includes('Google')) {
    showNotification('Error connecting to Google. Please try again.');
  } else {
    showNotification('Unknown error. Please try again later.');
  }
}
```

---

## FAQ

### 1. What happens if the user already has an account with the same email?

If a user already exists with the email returned by Google, the system automatically links the Google account to the existing user, updating the `google_id`, `provider`, and `avatar` fields.

### 2. Do users created via Google need to set a password?

No. Users created via Google can log in exclusively through Google. If they wish to add a password later, they can use the "forgot password" functionality.

### 3. Is the email automatically verified?

Yes. Since Google already verifies users' emails, the `email_verified_at` field is automatically filled during Google sign-up.

### 4. Can I link multiple Google accounts to the same user?

No. Currently, each user can have only one linked Google account. The `google_id` field is unique in the users table.

### 5. How does it work in production?

In production, make sure to:
1. Update environment variables with production URLs
2. Add production URLs in Google Cloud Console
3. Use HTTPS (required for OAuth)
4. Configure CORS properly in backend

### 6. Can I disable traditional login and use only Google?

Yes, but it's not recommended. It's good practice to maintain multiple authentication options to give users flexibility.

### 7. How do I test locally?

Use `http://localhost:3000` for frontend and `http://localhost:8000` for backend. Make sure these URLs are configured in Google Cloud Console.

---

## Support

For more information or support, see:

- [API Documentation](./README.md)
- [Frontend Examples](./frontend-examples.md)
- [Google OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)
- Repository: [GitHub](https://github.com/jmlandi/pharmedice-ca-be)

---

**Last updated:** October 2025
