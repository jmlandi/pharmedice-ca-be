# Pharmedice Customer Area - Backend

> API REST para o sistema de área do cliente da Pharmedice, desenvolvida em Laravel com autenticação JWT e integração AWS S3.

## ⚡ Quick Start

```bash
# 1. Instalar dependências
composer install

# 2. Configurar ambiente
cp .env.example .env
# Edite o .env com suas configurações

# 3. Gerar chaves
php artisan key:generate
php artisan jwt:secret

# 4. Configurar banco
php artisan migrate
php artisan db:seed

# 5. Iniciar servidor
php artisan serve
```

## 🚀 Tecnologias

- **Laravel 11** - Framework PHP
- **PostgreSQL** - Banco de dados principal
- **JWT Auth** - Autenticação via tokens
- **AWS S3** - Armazenamento de arquivos PDF

## 📋 Funcionalidades

- ✅ **Autenticação JWT** completa (login/logout/refresh)
- ✅ **Gestão de usuários** (administradores e clientes)
- ✅ **Gestão de laudos** com upload de PDF
- ✅ **Consulta pública** de laudos por qualquer usuário autenticado
- ✅ **Busca avançada** por título e nome do arquivo
- ✅ **Integração AWS S3** para armazenamento seguro
- ✅ **API RESTful** padronizada

## 👥 Usuários de Teste

Após executar `php artisan db:seed`:

- **Admin**: `admin@pharmedice.com` / `admin123`
- **Cliente**: `joao@exemplo.com` / `123456`

## 📚 Documentação

- **[📋 API Reference](./docs/api/README.md)** - Documentação completa da API
- **[🛠️ Setup Guide](./docs/setup/README.md)** - Guia detalhado de instalação  
- **[💡 Concepts](./docs/concepts/)** - Conceitos e arquitetura do sistema
- **[📝 Changelogs](./docs/changelogs/)** - Histórico de mudanças

## 🚀 Exemplo de Uso

```bash
# 1. Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@pharmedice.com","senha":"admin123"}'

# 2. Usar o token retornado
curl -X GET http://localhost:8000/api/laudos \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"

# 3. Buscar laudos
curl -X GET "http://localhost:8000/api/laudos/buscar?busca=exame" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

## 🏗️ Arquitetura

```
app/
├── DTOs/              # Data Transfer Objects
├── Http/Controllers/  # Controllers da API  
├── Http/Middleware/   # Middlewares de autenticação
├── Models/           # Models Eloquent
├── Services/         # Lógica de negócio
└── ...

docs/                 # 📚 Documentação organizada
├── api/             # Documentação da API
├── setup/           # Guias de instalação
├── concepts/        # Conceitos do sistema
└── changelogs/      # Histórico de mudanças
```

## � Segurança

- ✅ JWT Authentication com refresh tokens
- ✅ Role-based access control (Admin/Cliente)
- ✅ Validação rigorosa de arquivos PDF
- ✅ Armazenamento seguro no AWS S3
- ✅ Hash de senhas com bcrypt

## 🤝 Contribuição

1. Fork o projeto
2. Crie sua branch (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## � Licença

Distribuído sob a licença MIT. Veja `LICENSE` para mais informações.

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
