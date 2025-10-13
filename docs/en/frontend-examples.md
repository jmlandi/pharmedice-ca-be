# Frontend Framework Examples

## React Implementation Examples

### Context Setup for Authentication

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
      // Verify token validity
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
        message: error.response?.data?.message || 'Login failed' 
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
        message: error.response?.data?.message || 'Registration failed',
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
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
```

### Reports Management Hook

```jsx
// hooks/useReports.js
import { useState, useEffect } from 'react';
import { useAuth } from '../contexts/AuthContext';

export const useReports = () => {
  const { api } = useAuth();
  const [reports, setReports] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const fetchReports = async (page = 1) => {
    setLoading(true);
    setError(null);
    try {
      const response = await api.get(`/laudos?page=${page}`);
      if (response.data.success) {
        setReports(response.data.data);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to fetch reports');
    } finally {
      setLoading(false);
    }
  };

  const searchReports = async (searchTerm) => {
    setLoading(true);
    setError(null);
    try {
      const response = await api.get(`/laudos/buscar?busca=${encodeURIComponent(searchTerm)}`);
      if (response.data.success) {
        setReports(response.data.data);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Search failed');
    } finally {
      setLoading(false);
    }
  };

  const uploadReport = async (file, titulo, descricao) => {
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
        message: err.response?.data?.message || 'Upload failed',
        errors: err.response?.data?.errors
      };
    }
  };

  const deleteReport = async (reportId) => {
    try {
      await api.delete(`/laudos/${reportId}`);
      setReports(prev => ({
        ...prev,
        data: prev.data.filter(report => report.id !== reportId)
      }));
      return { success: true };
    } catch (err) {
      return {
        success: false,
        message: err.response?.data?.message || 'Delete failed'
      };
    }
  };

  return {
    reports,
    loading,
    error,
    fetchReports,
    searchReports,
    uploadReport,
    deleteReport
  };
};
```

### Login Component

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
        
        {/* Test user credentials for development */}
        <div className="test-users">
          <p>Test Users:</p>
          <p>Admin: admin@pharmedice.com / admin123</p>
          <p>User: joao@exemplo.com / 123456</p>
        </div>
      </form>
    </div>
  );
};

export default Login;
```

### Reports List Component

```jsx
// components/ReportsList.jsx
import React, { useEffect, useState } from 'react';
import { useReports } from '../hooks/useReports';
import { useAuth } from '../contexts/AuthContext';

const ReportsList = () => {
  const { reports, loading, error, fetchReports, searchReports } = useReports();
  const { isAdmin } = useAuth();
  const [searchTerm, setSearchTerm] = useState('');

  useEffect(() => {
    fetchReports();
  }, []);

  const handleSearch = (e) => {
    e.preventDefault();
    if (searchTerm.trim()) {
      searchReports(searchTerm);
    } else {
      fetchReports();
    }
  };

  const handleDownload = async (reportId) => {
    try {
      const response = await fetch(`http://localhost:8000/api/laudos/${reportId}/download`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });
      
      if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `laudo_${reportId}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
      }
    } catch (err) {
      console.error('Download failed:', err);
    }
  };

  if (loading) return <div className="loading">Carregando laudos...</div>;
  if (error) return <div className="error">Erro: {error}</div>;

  return (
    <div className="reports-container">
      <h2>Laudos Médicos</h2>
      
      {/* Search Form */}
      <form onSubmit={handleSearch} className="search-form">
        <input
          type="text"
          placeholder="Buscar laudos..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
        />
        <button type="submit">Buscar</button>
        <button type="button" onClick={() => {
          setSearchTerm('');
          fetchReports();
        }}>
          Limpar
        </button>
      </form>

      {/* Reports List */}
      <div className="reports-list">
        {reports.data?.length > 0 ? (
          reports.data.map(report => (
            <div key={report.id} className="report-card">
              <h3>{report.titulo}</h3>
              <p>{report.descricao}</p>
              <div className="report-meta">
                <span>Criado por: {report.usuario?.primeiro_nome} {report.usuario?.segundo_nome}</span>
                <span>Data: {new Date(report.created_at).toLocaleDateString('pt-BR')}</span>
              </div>
              <div className="report-actions">
                <button onClick={() => handleDownload(report.id)}>
                  Download
                </button>
                {isAdmin && (
                  <button className="delete-btn" onClick={() => handleDelete(report.id)}>
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

      {/* Pagination */}
      {reports.last_page > 1 && (
        <div className="pagination">
          {Array.from({ length: reports.last_page }, (_, i) => i + 1).map(page => (
            <button
              key={page}
              onClick={() => fetchReports(page)}
              className={page === reports.current_page ? 'active' : ''}
            >
              {page}
            </button>
          ))}
        </div>
      )}
    </div>
  );
};

export default ReportsList;
```

### Upload Component (Admin Only)

```jsx
// components/UploadReport.jsx
import React, { useState } from 'react';
import { useReports } from '../hooks/useReports';
import { useAuth } from '../contexts/AuthContext';

const UploadReport = () => {
  const { uploadReport } = useReports();
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

    const result = await uploadReport(formData.arquivo, formData.titulo, formData.descricao);

    if (result.success) {
      setMessage('Laudo enviado com sucesso!');
      setFormData({ titulo: '', descricao: '', arquivo: null });
      // Reset file input
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

export default UploadReport;
```

## Vue.js Implementation Examples

### Composable for API

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
      error.value = err.response?.data?.message || 'Login failed'
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

  const fetchReports = async (page = 1) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.get(`/laudos?page=${page}`)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch reports'
      throw err
    } finally {
      loading.value = false
    }
  }

  const searchReports = async (searchTerm) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.get(`/laudos/buscar?busca=${encodeURIComponent(searchTerm)}`)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Search failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    ...state,
    loading,
    error,
    // Methods
    login,
    logout,
    fetchReports,
    searchReports,
    api
  }
}
```

### Login Component (Vue 3)

```vue
<!-- components/LoginForm.vue -->
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
        <p>Test Users:</p>
        <p>Admin: admin@pharmedice.com / admin123</p>
        <p>User: joao@exemplo.com / 123456</p>
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

## Angular Implementation Examples

### Auth Service

```typescript
// services/auth.service.ts
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable } from 'rxjs';
import { tap, catchError } from 'rxjs/operators';

interface User {
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
    user: User;
  };
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = 'http://localhost:8000/api';
  private userSubject = new BehaviorSubject<User | null>(null);
  private tokenSubject = new BehaviorSubject<string | null>(localStorage.getItem('token'));

  public user$ = this.userSubject.asObservable();
  public token$ = this.tokenSubject.asObservable();

  constructor(private http: HttpClient) {
    const token = localStorage.getItem('token');
    if (token) {
      this.verifyToken();
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

  private verifyToken(): void {
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

### Reports Service

```typescript
// services/reports.service.ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { AuthService } from './auth.service';

interface Report {
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

interface ReportsResponse {
  success: boolean;
  data: {
    data: Report[];
    current_page: number;
    last_page: number;
    total: number;
  };
}

@Injectable({
  providedIn: 'root'
})
export class ReportsService {
  private apiUrl = 'http://localhost:8000/api';

  constructor(
    private http: HttpClient,
    private authService: AuthService
  ) {}

  getReports(page: number = 1): Observable<ReportsResponse> {
    const headers = this.authService.getAuthHeaders();
    return this.http.get<ReportsResponse>(`${this.apiUrl}/laudos?page=${page}`, { headers });
  }

  searchReports(searchTerm: string): Observable<ReportsResponse> {
    const headers = this.authService.getAuthHeaders();
    return this.http.get<ReportsResponse>(
      `${this.apiUrl}/laudos/buscar?busca=${encodeURIComponent(searchTerm)}`,
      { headers }
    );
  }

  uploadReport(file: File, titulo: string, descricao: string): Observable<any> {
    const headers = this.authService.getAuthHeaders();
    const formData = new FormData();
    formData.append('arquivo', file);
    formData.append('titulo', titulo);
    formData.append('descricao', descricao);

    return this.http.post(`${this.apiUrl}/laudos`, formData, { headers });
  }

  downloadReport(reportId: string): Observable<Blob> {
    const headers = this.authService.getAuthHeaders();
    return this.http.get(`${this.apiUrl}/laudos/${reportId}/download`, {
      headers,
      responseType: 'blob'
    });
  }
}
```

These examples provide a solid foundation for integrating with the Pharmedice backend API across different frontend frameworks.