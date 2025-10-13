# Exemplos de Implementação Frontend

## Exemplos de Implementação React

### Configuração de Context para Autenticação

```jsx
// contexts/AuthContext.js
import React, { createContext, useContext, useState, useEffect } from 'react';
import axios from 'axios';

const AuthContext = createContext();

const API_BASE_URL = 'http://localhost:8000/api';

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Accept': 'application/json'
  }
});

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(localStorage.getItem('token'));
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (token) {
      api.defaults.headers.Authorization = `Bearer ${token}`;
      // Verificar validade do token
      api.get('/auth/me')
        .then(response => {
          if (response.data.success) {
            setUser(response.data.data);
          }
        })
        .catch(() => {
          logout();
        })
        .finally(() => setLoading(false));
    } else {
      setLoading(false);
    }
  }, [token]);

  const login = async (email, senha) => {
    try {
      const response = await api.post('/auth/login', { email, senha });
      if (response.data.success) {
        const { access_token, user } = response.data.data;
        setToken(access_token);
        setUser(user);
        localStorage.setItem('token', access_token);
        api.defaults.headers.Authorization = `Bearer ${access_token}`;
        return { success: true, user };
      }
    } catch (error) {
      return { 
        success: false, 
        message: error.response?.data?.message || 'Falha no login' 
      };
    }
  };

  const logout = () => {
    setToken(null);
    setUser(null);
    localStorage.removeItem('token');
    delete api.defaults.headers.Authorization;
  };

  const register = async (userData) => {
    try {
      const response = await api.post('/auth/registrar', userData);
      return { success: true, data: response.data };
    } catch (error) {
      return { 
        success: false, 
        message: error.response?.data?.message || 'Falha no registro',
        errors: error.response?.data?.errors 
      };
    }
  };

  return (
    <AuthContext.Provider value={{
      user,
      token,
      loading,
      login,
      logout,
      register,
      api,
      isAdmin: user?.is_admin || false
    }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth deve ser usado dentro de um AuthProvider');
  }
  return context;
};
```

### Hook para Gerenciamento de Laudos

```jsx
// hooks/useLaudos.js
import { useState, useEffect } from 'react';
import { useAuth } from '../contexts/AuthContext';

export const useLaudos = () => {
  const { api } = useAuth();
  const [laudos, setLaudos] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const buscarLaudos = async (page = 1) => {
    setLoading(true);
    setError(null);
    try {
      const response = await api.get(`/laudos?page=${page}`);
      if (response.data.success) {
        setLaudos(response.data.data);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Falha ao buscar laudos');
    } finally {
      setLoading(false);
    }
  };

  const pesquisarLaudos = async (termoBusca) => {
    setLoading(true);
    setError(null);
    try {
      const response = await api.get(`/laudos/buscar?busca=${encodeURIComponent(termoBusca)}`);
      if (response.data.success) {
        setLaudos(response.data.data);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Falha na pesquisa');
    } finally {
      setLoading(false);
    }
  };

  const uploadLaudo = async (file, titulo, descricao) => {
    const formData = new FormData();
    formData.append('arquivo', file);
    formData.append('titulo', titulo);
    formData.append('descricao', descricao);

    try {
      const response = await api.post('/laudos', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });
      return { success: true, data: response.data };
    } catch (err) {
      return {
        success: false,
        message: err.response?.data?.message || 'Falha no upload',
        errors: err.response?.data?.errors
      };
    }
  };

  const excluirLaudo = async (laudoId) => {
    try {
      await api.delete(`/laudos/${laudoId}`);
      setLaudos(prev => ({
        ...prev,
        data: prev.data.filter(laudo => laudo.id !== laudoId)
      }));
      return { success: true };
    } catch (err) {
      return {
        success: false,
        message: err.response?.data?.message || 'Falha ao excluir'
      };
    }
  };

  return {
    laudos,
    loading,
    error,
    buscarLaudos,
    pesquisarLaudos,
    uploadLaudo,
    excluirLaudo
  };
};
```

### Componente de Login

```jsx
// components/Login.jsx
import React, { useState } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { useNavigate } from 'react-router-dom';

const Login = () => {
  const [formData, setFormData] = useState({ email: '', senha: '' });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    const result = await login(formData.email, formData.senha);
    
    if (result.success) {
      navigate('/dashboard');
    } else {
      setError(result.message);
    }
    
    setLoading(false);
  };

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  return (
    <div className="login-container">
      <form onSubmit={handleSubmit} className="login-form">
        <h2>Login - Pharmedice</h2>
        
        {error && <div className="error-message">{error}</div>}
        
        <div className="form-group">
          <label htmlFor="email">Email:</label>
          <input
            type="email"
            id="email"
            name="email"
            value={formData.email}
            onChange={handleChange}
            required
          />
        </div>
        
        <div className="form-group">
          <label htmlFor="senha">Senha:</label>
          <input
            type="password"
            id="senha"
            name="senha"
            value={formData.senha}
            onChange={handleChange}
            required
          />
        </div>
        
        <button type="submit" disabled={loading}>
          {loading ? 'Entrando...' : 'Entrar'}
        </button>
        
        {/* Credenciais de usuários de teste para desenvolvimento */}
        <div className="test-users">
          <p>Usuários de Teste:</p>
          <p>Admin: admin@pharmedice.com / admin123</p>
          <p>Usuário: joao@exemplo.com / 123456</p>
        </div>
      </form>
    </div>
  );
};

export default Login;
```

### Componente de Lista de Laudos

```jsx
// components/ListaLaudos.jsx
import React, { useEffect, useState } from 'react';
import { useLaudos } from '../hooks/useLaudos';
import { useAuth } from '../contexts/AuthContext';

const ListaLaudos = () => {
  const { laudos, loading, error, buscarLaudos, pesquisarLaudos } = useLaudos();
  const { isAdmin } = useAuth();
  const [termoBusca, setTermoBusca] = useState('');

  useEffect(() => {
    buscarLaudos();
  }, []);

  const handleBusca = (e) => {
    e.preventDefault();
    if (termoBusca.trim()) {
      pesquisarLaudos(termoBusca);
    } else {
      buscarLaudos();
    }
  };

  const handleDownload = async (laudoId) => {
    try {
      const response = await fetch(`http://localhost:8000/api/laudos/${laudoId}/download`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });
      
      if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `laudo_${laudoId}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
      }
    } catch (err) {
      console.error('Falha no download:', err);
    }
  };

  if (loading) return <div className="loading">Carregando laudos...</div>;
  if (error) return <div className="error">Erro: {error}</div>;

  return (
    <div className="laudos-container">
      <h2>Laudos Médicos</h2>
      
      {/* Formulário de Busca */}
      <form onSubmit={handleBusca} className="search-form">
        <input
          type="text"
          placeholder="Buscar laudos..."
          value={termoBusca}
          onChange={(e) => setTermoBusca(e.target.value)}
        />
        <button type="submit">Buscar</button>
        <button type="button" onClick={() => {
          setTermoBusca('');
          buscarLaudos();
        }}>
          Limpar
        </button>
      </form>

      {/* Lista de Laudos */}
      <div className="laudos-list">
        {laudos.data?.length > 0 ? (
          laudos.data.map(laudo => (
            <div key={laudo.id} className="laudo-card">
              <h3>{laudo.titulo}</h3>
              <p>{laudo.descricao}</p>
              <div className="laudo-meta">
                <span>Criado por: {laudo.usuario?.primeiro_nome} {laudo.usuario?.segundo_nome}</span>
                <span>Data: {new Date(laudo.created_at).toLocaleDateString('pt-BR')}</span>
              </div>
              <div className="laudo-actions">
                <button onClick={() => handleDownload(laudo.id)}>
                  Download
                </button>
                {isAdmin && (
                  <button className="delete-btn" onClick={() => handleDelete(laudo.id)}>
                    Excluir
                  </button>
                )}
              </div>
            </div>
          ))
        ) : (
          <p>Nenhum laudo encontrado.</p>
        )}
      </div>

      {/* Paginação */}
      {laudos.last_page > 1 && (
        <div className="pagination">
          {Array.from({ length: laudos.last_page }, (_, i) => i + 1).map(page => (
            <button
              key={page}
              onClick={() => buscarLaudos(page)}
              className={page === laudos.current_page ? 'active' : ''}
            >
              {page}
            </button>
          ))}
        </div>
      )}
    </div>
  );
};

export default ListaLaudos;
```

### Componente de Upload (Apenas Admin)

```jsx
// components/UploadLaudo.jsx
import React, { useState } from 'react';
import { useLaudos } from '../hooks/useLaudos';
import { useAuth } from '../contexts/AuthContext';

const UploadLaudo = () => {
  const { uploadLaudo } = useLaudos();
  const { isAdmin } = useAuth();
  const [formData, setFormData] = useState({
    titulo: '',
    descricao: '',
    arquivo: null
  });
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');

  if (!isAdmin) {
    return <div>Acesso negado. Apenas administradores podem fazer upload.</div>;
  }

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMessage('');

    if (!formData.arquivo) {
      setMessage('Por favor, selecione um arquivo.');
      setLoading(false);
      return;
    }

    const result = await uploadLaudo(formData.arquivo, formData.titulo, formData.descricao);

    if (result.success) {
      setMessage('Laudo enviado com sucesso!');
      setFormData({ titulo: '', descricao: '', arquivo: null });
      // Reset do input de arquivo
      document.getElementById('arquivo').value = '';
    } else {
      setMessage(`Erro: ${result.message}`);
    }

    setLoading(false);
  };

  const handleChange = (e) => {
    const { name, value, files } = e.target;
    setFormData({
      ...formData,
      [name]: files ? files[0] : value
    });
  };

  return (
    <div className="upload-container">
      <h2>Upload de Laudo</h2>
      
      {message && (
        <div className={`message ${message.includes('Erro') ? 'error' : 'success'}`}>
          {message}
        </div>
      )}

      <form onSubmit={handleSubmit} className="upload-form">
        <div className="form-group">
          <label htmlFor="titulo">Título:</label>
          <input
            type="text"
            id="titulo"
            name="titulo"
            value={formData.titulo}
            onChange={handleChange}
            required
          />
        </div>

        <div className="form-group">
          <label htmlFor="descricao">Descrição:</label>
          <textarea
            id="descricao"
            name="descricao"
            value={formData.descricao}
            onChange={handleChange}
            required
            rows="4"
          />
        </div>

        <div className="form-group">
          <label htmlFor="arquivo">Arquivo PDF:</label>
          <input
            type="file"
            id="arquivo"
            name="arquivo"
            accept=".pdf"
            onChange={handleChange}
            required
          />
        </div>

        <button type="submit" disabled={loading}>
          {loading ? 'Enviando...' : 'Enviar Laudo'}
        </button>
      </form>
    </div>
  );
};

export default UploadLaudo;
```

## Exemplos de Implementação Vue.js

### Composable para API

```javascript
// composables/useApi.js
import { ref, reactive } from 'vue'
import axios from 'axios'

const API_BASE_URL = 'http://localhost:8000/api'

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Accept': 'application/json'
  }
})

const state = reactive({
  user: null,
  token: localStorage.getItem('token'),
  isAuthenticated: false
})

if (state.token) {
  api.defaults.headers.Authorization = `Bearer ${state.token}`
}

export function useApi() {
  const loading = ref(false)
  const error = ref(null)

  const login = async (email, senha) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.post('/auth/login', { email, senha })
      if (response.data.success) {
        const { access_token, user } = response.data.data
        state.token = access_token
        state.user = user
        state.isAuthenticated = true
        localStorage.setItem('token', access_token)
        api.defaults.headers.Authorization = `Bearer ${access_token}`
        return { success: true, user }
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Falha no login'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  const logout = () => {
    state.token = null
    state.user = null
    state.isAuthenticated = false
    localStorage.removeItem('token')
    delete api.defaults.headers.Authorization
  }

  const buscarLaudos = async (page = 1) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.get(`/laudos?page=${page}`)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Falha ao buscar laudos'
      throw err
    } finally {
      loading.value = false
    }
  }

  const pesquisarLaudos = async (termoBusca) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.get(`/laudos/buscar?busca=${encodeURIComponent(termoBusca)}`)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Falha na pesquisa'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    // Estado
    ...state,
    loading,
    error,
    // Métodos
    login,
    logout,
    buscarLaudos,
    pesquisarLaudos,
    api
  }
}
```

### Componente de Login (Vue 3)

```vue
<!-- components/FormularioLogin.vue -->
<template>
  <div class="login-container">
    <form @submit.prevent="handleLogin" class="login-form">
      <h2>Login - Pharmedice</h2>
      
      <div v-if="error" class="error-message">{{ error }}</div>
      
      <div class="form-group">
        <label for="email">Email:</label>
        <input
          type="email"
          id="email"
          v-model="form.email"
          required
        />
      </div>
      
      <div class="form-group">
        <label for="senha">Senha:</label>
        <input
          type="password"
          id="senha"
          v-model="form.senha"
          required
        />
      </div>
      
      <button type="submit" :disabled="loading">
        {{ loading ? 'Entrando...' : 'Entrar' }}
      </button>
      
      <div class="test-users">
        <p>Usuários de Teste:</p>
        <p>Admin: admin@pharmedice.com / admin123</p>
        <p>Usuário: joao@exemplo.com / 123456</p>
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../composables/useApi'

const { login, loading, error } = useApi()
const router = useRouter()

const form = reactive({
  email: '',
  senha: ''
})

const handleLogin = async () => {
  const result = await login(form.email, form.senha)
  if (result.success) {
    router.push('/dashboard')
  }
}
</script>
```

## Exemplos de Implementação Angular

### Serviço de Autenticação

```typescript
// services/auth.service.ts
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable } from 'rxjs';
import { tap, catchError } from 'rxjs/operators';

interface Usuario {
  id: string;
  primeiro_nome: string;
  segundo_nome: string;
  email: string;
  tipo_usuario: string;
  is_admin: boolean;
}

interface LoginResponse {
  success: boolean;
  message: string;
  data: {
    access_token: string;
    token_type: string;
    expires_in: number;
    user: Usuario;
  };
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = 'http://localhost:8000/api';
  private userSubject = new BehaviorSubject<Usuario | null>(null);
  private tokenSubject = new BehaviorSubject<string | null>(localStorage.getItem('token'));

  public user$ = this.userSubject.asObservable();
  public token$ = this.tokenSubject.asObservable();

  constructor(private http: HttpClient) {
    const token = localStorage.getItem('token');
    if (token) {
      this.verificarToken();
    }
  }

  login(email: string, senha: string): Observable<LoginResponse> {
    return this.http.post<LoginResponse>(`${this.apiUrl}/auth/login`, { email, senha })
      .pipe(
        tap(response => {
          if (response.success) {
            const { access_token, user } = response.data;
            localStorage.setItem('token', access_token);
            this.tokenSubject.next(access_token);
            this.userSubject.next(user);
          }
        })
      );
  }

  logout(): void {
    localStorage.removeItem('token');
    this.tokenSubject.next(null);
    this.userSubject.next(null);
  }

  private verificarToken(): void {
    const headers = this.getAuthHeaders();
    this.http.get<any>(`${this.apiUrl}/auth/me`, { headers })
      .subscribe({
        next: (response) => {
          if (response.success) {
            this.userSubject.next(response.data);
          }
        },
        error: () => {
          this.logout();
        }
      });
  }

  getAuthHeaders(): HttpHeaders {
    const token = this.tokenSubject.value;
    return new HttpHeaders({
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    });
  }

  get isAdmin(): boolean {
    return this.userSubject.value?.is_admin || false;
  }

  get isAuthenticated(): boolean {
    return !!this.tokenSubject.value;
  }
}
```

### Serviço de Laudos

```typescript
// services/laudos.service.ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { AuthService } from './auth.service';

interface Laudo {
  id: string;
  titulo: string;
  descricao: string;
  url_arquivo: string;
  created_at: string;
  usuario: {
    primeiro_nome: string;
    segundo_nome: string;
  };
}

interface LaudosResponse {
  success: boolean;
  data: {
    data: Laudo[];
    current_page: number;
    last_page: number;
    total: number;
  };
}

@Injectable({
  providedIn: 'root'
})
export class LaudosService {
  private apiUrl = 'http://localhost:8000/api';

  constructor(
    private http: HttpClient,
    private authService: AuthService
  ) {}

  getLaudos(page: number = 1): Observable<LaudosResponse> {
    const headers = this.authService.getAuthHeaders();
    return this.http.get<LaudosResponse>(`${this.apiUrl}/laudos?page=${page}`, { headers });
  }

  pesquisarLaudos(termoBusca: string): Observable<LaudosResponse> {
    const headers = this.authService.getAuthHeaders();
    return this.http.get<LaudosResponse>(
      `${this.apiUrl}/laudos/buscar?busca=${encodeURIComponent(termoBusca)}`,
      { headers }
    );
  }

  uploadLaudo(file: File, titulo: string, descricao: string): Observable<any> {
    const headers = this.authService.getAuthHeaders();
    const formData = new FormData();
    formData.append('arquivo', file);
    formData.append('titulo', titulo);
    formData.append('descricao', descricao);

    return this.http.post(`${this.apiUrl}/laudos`, formData, { headers });
  }

  downloadLaudo(laudoId: string): Observable<Blob> {
    const headers = this.authService.getAuthHeaders();
    return this.http.get(`${this.apiUrl}/laudos/${laudoId}/download`, {
      headers,
      responseType: 'blob'
    });
  }
}
```

Esses exemplos fornecem uma base sólida para integração com a API backend Pharmedice em diferentes frameworks frontend.