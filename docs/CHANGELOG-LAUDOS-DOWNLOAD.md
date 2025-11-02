# 🔄 Mudanças no Sistema de Download de Laudos

**Data:** 30 de Outubro de 2025  
**Versão:** 1.1.0  
**Tipo:** Correção Crítica + Nova Funcionalidade

---

## 🐛 Problema Corrigido

Os downloads de laudos estavam retornando arquivos corrompidos devido a:
- Arquivos privados no S3 sem URLs assinadas
- Resposta JSON ao invés de stream binário do PDF
- Headers HTTP incorretos

---

## ✅ Mudanças Implementadas

### 1. **Endpoint de Download Atualizado**

**Endpoint:** `GET /api/laudos/{id}/download`

**ANTES:**
```json
// Retornava JSON com URL
{
  "sucesso": true,
  "dados": {
    "url": "https://bucket.s3.amazonaws.com/...",
    "nome_arquivo": "...",
    "titulo": "..."
  }
}
```

**AGORA:**
- Retorna o **arquivo PDF diretamente** (stream binário)
- Headers corretos para download:
  - `Content-Type: application/pdf`
  - `Content-Disposition: attachment; filename="nome-original.pdf"`
  - `Content-Length: <tamanho>`

### 2. **Novo Endpoint: Visualização de Laudos** ✨

**Endpoint:** `GET /api/laudos/{id}/visualizar`

- Retorna o **arquivo PDF diretamente** para visualização no navegador
- Headers para exibição inline:
  - `Content-Type: application/pdf`
  - `Content-Disposition: inline; filename="nome-original.pdf"`
  - `Content-Length: <tamanho>`

---

## 📋 Integração no Frontend

### **Opção 1: Download Direto (Recomendado)**

```javascript
// Abrir em nova aba (navegador faz o download automaticamente)
const downloadLaudo = (laudoId, token) => {
  window.open(
    `${API_URL}/laudos/${laudoId}/download`,
    '_blank'
  );
};
```

### **Opção 2: Download com Fetch + Blob**

```javascript
const downloadLaudoComFetch = async (laudoId, token) => {
  try {
    const response = await fetch(`${API_URL}/laudos/${laudoId}/download`, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });

    if (!response.ok) throw new Error('Erro ao baixar laudo');

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `laudo_${laudoId}.pdf`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
  } catch (error) {
    console.error('Erro no download:', error);
  }
};
```

### **Opção 3: Visualizar no Navegador**

```javascript
// Abrir PDF em nova aba para visualização
const visualizarLaudo = (laudoId, token) => {
  window.open(
    `${API_URL}/laudos/${laudoId}/visualizar`,
    '_blank'
  );
};
```

### **Opção 4: Visualizar em iFrame/Modal**

```jsx
// React/Vue/Angular
<iframe 
  src={`${API_URL}/laudos/${laudoId}/visualizar`}
  width="100%"
  height="600px"
  title="Visualização do Laudo"
/>
```

---

## 🔐 Autenticação

**Ambos os endpoints requerem autenticação JWT:**

```javascript
headers: {
  'Authorization': `Bearer ${seu_token_jwt}`
}
```

**Respostas de Erro:**
- `401 Unauthorized` - Token inválido ou expirado
- `404 Not Found` - Laudo não existe
- `500 Internal Server Error` - Erro ao buscar arquivo no S3

---

## 🎯 Exemplos de Uso

### React/Next.js

```jsx
import { useState } from 'react';

const LaudoCard = ({ laudo, token }) => {
  const API_URL = process.env.NEXT_PUBLIC_API_URL;

  const handleDownload = () => {
    window.open(`${API_URL}/laudos/${laudo.id}/download`, '_blank');
  };

  const handleVisualizar = () => {
    window.open(`${API_URL}/laudos/${laudo.id}/visualizar`, '_blank');
  };

  return (
    <div className="laudo-card">
      <h3>{laudo.titulo}</h3>
      <p>{laudo.descricao}</p>
      <div className="actions">
        <button onClick={handleVisualizar}>
          👁️ Visualizar
        </button>
        <button onClick={handleDownload}>
          ⬇️ Baixar
        </button>
      </div>
    </div>
  );
};
```

### Vue.js

```vue
<template>
  <div class="laudo-card">
    <h3>{{ laudo.titulo }}</h3>
    <p>{{ laudo.descricao }}</p>
    <div class="actions">
      <button @click="visualizar">👁️ Visualizar</button>
      <button @click="download">⬇️ Baixar</button>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue';

const props = defineProps(['laudo', 'token']);
const API_URL = import.meta.env.VITE_API_URL;

const visualizar = () => {
  window.open(`${API_URL}/laudos/${props.laudo.id}/visualizar`, '_blank');
};

const download = () => {
  window.open(`${API_URL}/laudos/${props.laudo.id}/download`, '_blank');
};
</script>
```

### Angular

```typescript
// laudo.component.ts
import { Component, Input } from '@angular/core';
import { environment } from '../environments/environment';

@Component({
  selector: 'app-laudo-card',
  template: `
    <div class="laudo-card">
      <h3>{{ laudo.titulo }}</h3>
      <p>{{ laudo.descricao }}</p>
      <div class="actions">
        <button (click)="visualizar()">👁️ Visualizar</button>
        <button (click)="download()">⬇️ Baixar</button>
      </div>
    </div>
  `
})
export class LaudoCardComponent {
  @Input() laudo: any;
  @Input() token: string;

  visualizar() {
    window.open(
      `${environment.apiUrl}/laudos/${this.laudo.id}/visualizar`,
      '_blank'
    );
  }

  download() {
    window.open(
      `${environment.apiUrl}/laudos/${this.laudo.id}/download`,
      '_blank'
    );
  }
}
```

---

## 🔄 Migração

### ⚠️ **BREAKING CHANGES**

Se você estava usando o endpoint `/laudos/{id}/download` esperando JSON, **precisa atualizar seu código**.

**Código Antigo (NÃO FUNCIONA MAIS):**
```javascript
// ❌ Isso não funciona mais
const response = await fetch(`/api/laudos/${id}/download`);
const data = await response.json(); // ❌ Não retorna mais JSON
const url = data.dados.url;
```

**Código Novo:**
```javascript
// ✅ Opção 1: Redirecionar diretamente
window.open(`/api/laudos/${id}/download`, '_blank');

// ✅ Opção 2: Fetch + Blob
const response = await fetch(`/api/laudos/${id}/download`);
const blob = await response.blob();
// ... criar download do blob
```

---

## 📝 Notas Técnicas

### Segurança
- Arquivos permanecem **privados** no S3
- Acesso controlado via **autenticação JWT**
- Stream direto pela API Laravel (não expõe URLs do S3)

### Performance
- Arquivo é transmitido diretamente do S3 para o cliente
- Sem armazenamento temporário no servidor
- Headers `Content-Length` permitem barra de progresso

### Limitações do Nginx

⚠️ **IMPORTANTE:** Se você tem upload/download de arquivos grandes, verifique o `client_max_body_size` no Nginx:

```nginx
# /etc/nginx/conf.d/seu-site.conf
server {
    # ...
    client_max_body_size 10M;  # Ajuste conforme necessário
}
```

---

## ✅ Checklist de Implementação

- [ ] Atualizar código frontend para usar novos endpoints
- [ ] Remover código antigo que esperava JSON do `/download`
- [ ] Testar download em diferentes navegadores
- [ ] Testar visualização inline
- [ ] Verificar autenticação JWT em todas as requisições
- [ ] Testar com arquivos grandes (> 5MB)
- [ ] Atualizar documentação interna da equipe

---

## 🆘 Suporte

Em caso de dúvidas ou problemas:

1. Verificar logs do navegador (console)
2. Verificar network tab (DevTools) para ver headers da resposta
3. Confirmar que token JWT está sendo enviado corretamente
4. Verificar se o laudo existe (`GET /api/laudos/{id}`)

**Endpoints de Debug:**
```bash
# Verificar se laudo existe
GET /api/laudos/{id}
Authorization: Bearer {token}

# Testar download direto no navegador
https://api.seudominio.com/laudos/{id}/download
```

---

**Desenvolvido por:** Backend Team  
**Revisado por:** Tech Lead  
**Status:** ✅ Produção
