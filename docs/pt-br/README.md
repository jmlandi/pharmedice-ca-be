# Pharmedice Customer Area - Documentação do Backend

> Documentação completa para o si## 🆘 Suporte e Solução de Problemas

### Problemas Comuns
- **[🔧 Problemas de Instalação](./setup/README.md)** - Problemas comuns de setup
- **[📋 Referência da API](./api/README.md)** - Troubleshooting de API

### Obtendo Ajudalemas Comuns
- **[🔧 Problemas de Instalação](./setup/README.md)** - Problemas comuns de setup
- **[📋 Referência da API](./api/README.md)** - Troubleshooting de APIa de API REST da Área do Cliente Pharmedice construído com Laravel 11, autenticação JWT e integração AWS S3.

## 🎯 Visão Geral

Este sistema fornece uma solução completa de backend para gerenciar documentos de clientes (laudos) com autenticação segura, controle de acesso baseado em funções e integração com armazenamento em nuvem.

### Principais Funcionalidades

- **🔐 Autenticação JWT** - Sistema completo de auth com verificação de email
- **👥 Gestão de Usuários** - Sistema multi-função (admin/cliente)  
- **📄 Gestão de Documentos** - Upload, armazenamento e recuperação de PDFs
- **🔍 Busca Avançada** - Pesquisa por título, descrição e metadados
- **☁️ Armazenamento em Nuvem** - Integração AWS S3 para arquivos seguros
- **🛡️ Segurança** - Controle de acesso baseado em funções e validação de dados
- **📊 Testes** - Suíte abrangente de testes com 15/15 testes aprovados

## 🚀 Navegação Rápida

### Primeiros Passos
- **[⚡ Guia de Início Rápido](./setup/README.md)** - Execute em poucos minutos

### Documentação da API  
- **[📋 Visão Geral da API](./api/README.md)** - Referência completa da API

### Conceitos do Sistema
- **[🏗️ Visão Geral da Arquitetura](./concepts/README.md)** - Arquitetura e design do sistema
- **[ Verificação de Email](./concepts/email-verification.md)** - Sistema de verificação de email  
- **[☁️ Upload de Arquivos e S3](./concepts/file-upload-s3-flow.md)** - Manipulação de arquivos e armazenamento em nuvem

### Testes e Desenvolvimento
- **[🧪 Guia de Testes](./concepts/testing.md)** - Executar e escrever testes
- **[🔄 Contribuindo](../CONTRIBUTING.md)** - Fluxo de desenvolvimento e diretrizes

## 📊 Status do Sistema

### Status de Implementação Atual
- ✅ **Sistema de Autenticação** - Completo com verificação de email
- ✅ **Gestão de Usuários** - CRUD completo com acesso baseado em funções  
- ✅ **Gestão de Documentos** - Upload, download, busca, consulta pública
- ✅ **Armazenamento em Nuvem** - Integração AWS S3 funcionando
- ✅ **Documentação da API** - Documentação abrangente de endpoints
- ✅ **Suíte de Testes** - 15/15 testes aprovados (100% de sucesso)

### Especificações Técnicas
- **Framework**: Laravel 11
- **Banco de Dados**: PostgreSQL com ULIDs
- **Autenticação**: JWT com tymon/jwt-auth
- **Armazenamento**: AWS S3 com Laravel Flysystem
- **Testes**: PHPUnit com testes Feature & Unit
- **API**: RESTful com respostas JSON

## 🎓 Caminho de Aprendizado

Se você é novo neste sistema, recomendamos seguir este caminho de aprendizado:

1. **[⚡ Início Rápido](./setup/README.md)** - Execute o sistema localmente
2. **[� Referência da API](./api/README.md)** - Aprenda os endpoints da API
3. **[🏗️ Arquitetura](./concepts/README.md)** - Entenda o design do sistema
4. **[🧪 Testes](./concepts/testing.md)** - Execute testes e valide funcionalidade

## 💡 Exemplos e Casos de Uso

### Operações Comuns
```bash
# Login e obter token
curl -X POST localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@pharmedice.com","senha":"admin123"}'

# Listar documentos (autenticado)  
curl -X GET localhost:8000/api/laudos \
  -H "Authorization: Bearer SEU_TOKEN"

# Buscar documentos
curl -X GET "localhost:8000/api/laudos/buscar?busca=exame" \
  -H "Authorization: Bearer SEU_TOKEN"
```

### Exemplos de Integração
- **Integração Frontend** - Consumo de API React/Vue/Angular
- **Apps Mobile** - Gerenciamento de token JWT para clientes mobile
- **Sistemas Terceiros** - Integração de API com serviços externos

## 🆘 Suporte e Solução de Problemas

### Problemas Comuns
- **[🔧 Problemas de Instalação](./setup/troubleshooting.md)** - Problemas comuns de setup
- **[� Problemas de Instalação](./setup/README.md)** - Problemas comuns de instalação  
- **[� Referência da API](./api/README.md)** - Troubleshooting de API

### Obtendo Ajuda
1. Verifique a seção de documentação relevante primeiro
2. Revise a [documentação de setup](./setup/README.md) para problemas de instalação
3. Olhe os arquivos de teste para exemplos de uso
4. Verifique o [guia de contribuição](../CONTRIBUTING.md) para configuração de desenvolvimento

## 🚀 Próximos Passos

Pronto para começar? Aqui estão seus próximos passos:

1. **Desenvolvimento**: Siga o [Guia de Início Rápido](./setup/README.md)
2. **Integração**: Verifique a [Documentação da API](./api/README.md)
3. **Contribuição**: Revise as [Diretrizes de Contribuição](../CONTRIBUTING.md)

---

**Última Atualização**: Outubro 2025  
**Versão**: 1.0.0  
**Status**: Pronto para Produção ✅