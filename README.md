# Pharmedice Customer Area - Backend API# Pharmedice Customer Area - Backend Documentation



Sistema backend para área de clientes Pharmedice desenvolvido em Laravel 11.Welcome to the comprehensive documentation for the Pharmedice Customer Area backend system.



## 🚀 Tecnologias## 🌍 Choose Your Language / Escolha seu Idioma



- **Laravel 11** - Framework PHP### 🇺🇸 English Documentation

- **MySQL** - Banco de dados- **[📋 Complete Documentation](./docs/en/README.md)** - Full system documentation

- **JWT** - Autenticação- **[🚀 API Reference](./docs/en/api/README.md)** - REST API endpoints and examples

- **Nginx** - Servidor web (produção)- **[⚙️ Setup Guide](./docs/en/setup/README.md)** - Installation and configuration

- **AWS EC2** - Hospedagem- **[💡 System Concepts](./docs/en/concepts/README.md)** - Architecture and design patterns



## 📋 Funcionalidades### 🇧🇷 Documentação em Português  

- **[� Documentação Completa](./docs/pt-br/README.md)** - Documentação completa do sistema

- Sistema de autenticação JWT com verificação de email- **[🚀 Referência da API](./docs/pt-br/api/README.md)** - Endpoints da API REST e exemplos

- Gestão de usuários e permissões (admin/usuário comum)- **[⚙️ Guia de Instalação](./docs/pt-br/setup/README.md)** - Instalação e configuração

- Gerenciamento de laudos médicos- **[💡 Conceitos do Sistema](./docs/pt-br/concepts/README.md)** - Arquitetura e padrões de design

- Consulta pública de laudos

- Recuperação de senha via email## 📚 Documentation Structure

- Sistema de upload e download de arquivos

```

## 🔧 Instalação Localdocs/

├── 🇺🇸 en/                     # English Documentation

### Requisitos│   ├── README.md               # System overview & navigation

- PHP 8.2+│   ├── api/README.md           # Complete API reference

- Composer│   ├── setup/README.md         # Installation guide

- MySQL 8.0+│   └── concepts/README.md      # System architecture

- Node.js (para assets)├── 🇧🇷 pt-br/                 # Portuguese Documentation

│   ├── README.md               # Visão geral & navegação

### Passos│   ├── api/README.md           # Referência completa da API

│   ├── setup/README.md         # Guia de instalação

```bash│   └── concepts/README.md      # Arquitetura do sistema

# Clone o repositório└── CONTRIBUTING.md             # Development guidelines

git clone [repository-url]```

cd customer-area-be

## 🎯 Quick Start

# Instale as dependências

composer install### For English Speakers

npm install1. **Setup**: [Installation Guide](./docs/en/setup/README.md) 

2. **API**: [API Documentation](./docs/en/api/README.md)

# Configure o ambiente3. **Architecture**: [System Concepts](./docs/en/concepts/README.md)

cp .env.example .env

php artisan key:generate### Para Falantes de Português

php artisan jwt:secret1. **Instalação**: [Guia de Instalação](./docs/pt-br/setup/README.md)

2. **API**: [Documentação da API](./docs/pt-br/api/README.md)  

# Configure o banco de dados no .env3. **Arquitetura**: [Conceitos do Sistema](./docs/pt-br/concepts/README.md)

# DB_CONNECTION=mysql

# DB_HOST=127.0.0.1## 🚀 System Overview

# DB_PORT=3306

# DB_DATABASE=pharmedice_customer_areaThe Pharmedice Customer Area backend is a complete Laravel 11 REST API system featuring:

# DB_USERNAME=root

# DB_PASSWORD=- ✅ **JWT Authentication** with email verification

- ✅ **User Management** (admin/client roles)  

# Execute as migrations- ✅ **Document Management** with PDF upload to AWS S3

php artisan migrate- ✅ **Advanced Search** and filtering capabilities

- ✅ **Comprehensive Testing** (15/15 tests passing)

# Inicie o servidor de desenvolvimento- ✅ **Production Ready** with full documentation

php artisan serve

```## 🤝 Contributing



## 🌐 Deploy em EC2 com NginxInterested in contributing? Check our guidelines:



Para instruções completas de deploy em servidor EC2 da Amazon com Nginx, consulte:- **[🤝 Contributing Guide](./docs/CONTRIBUTING.md)** - Development workflow and standards

- **[📝 Navigation Guide](./docs/NAVIGATION.md)** - Documentation navigation and structure

- **[Guia Completo de Deploy EC2](./docs/deploy-ec2-guia-completo.md)** - Passo a passo detalhado

- **[Troubleshooting EC2](./docs/deploy-ec2-troubleshooting.md)** - Solução de problemas comuns---



## 📚 Documentação**Version**: 1.0.0  

**Last Updated**: October 2025  

### 🇺🇸 English Documentation**Status**: Production Ready ✅  

- **[Complete Documentation](./docs/en/README.md)** - Full system documentation**Test Coverage**: 15/15 tests passing (100%)
- **[API Reference](./docs/en/api/README.md)** - REST API endpoints and examples
- **[Setup Guide](./docs/en/setup/README.md)** - Installation and configuration

### 🇧🇷 Documentação em Português  
- **[Documentação Completa](./docs/pt-br/README.md)** - Documentação completa do sistema
- **[Referência da API](./docs/pt-br/api/README.md)** - Endpoints da API REST e exemplos
- **[Guia de Instalação](./docs/pt-br/setup/README.md)** - Instalação e configuração

## 🔑 Principais Endpoints

```
GET  /api                              # Status da API
POST /api/auth/login                   # Login
POST /api/auth/registrar-usuario       # Registro de usuário
POST /api/auth/verificar-email         # Verificação de email
GET  /api/laudos/consultar/{id}        # Consulta pública de laudo
GET  /api/laudos                       # Lista laudos (autenticado)
GET  /api/usuarios                     # Lista usuários (admin)
```

## 📧 Configuração de Email

Configure as variáveis de email no arquivo `.env`:

```env
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
RESEND_KEY=your_resend_api_key
```

## 🔒 Segurança

- Autenticação JWT com tokens refresh
- Middleware de permissões (admin/usuário)
- Verificação de email obrigatória
- Signed URLs para verificação de email
- Rate limiting em rotas sensíveis

## 📝 Licença

Proprietary - Pharmedice

## 👥 Suporte

Para suporte e dúvidas sobre o sistema, consulte a documentação completa na pasta `docs/`.
