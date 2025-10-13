# Pharmedice Customer Area - Backend Documentation

> Complete documentation for the Pharmedi1. 1. Check the relevant documentation section first
2. Review the [setup documentation](./setup/README.md) for installation issues
3. Look at test files for usage examplesck the relevant documentation section first
2. Review the [setup documentation](./setup/README.md) for installation issues
3. Look at test files for usage examples
4. Check the [contributing guide](../CONTRIBUTING.md) for development setupustomer Area REST API system built with Laravel 11, JWT authentication, and AWS S3 integration.

## 🎯 Overview

This system provides a complete backend solution for managing customer documents (laudos) with secure authentication, role-based access control, and cloud storage integration.

### Key Features

- **🔐 JWT Authentication** - Complete auth system with email verification
- **👥 User Management** - Multi-role system (admin/client)  
- **📄 Document Management** - PDF upload, storage, and retrieval
- **🔍 Advanced Search** - Search by title, description, and metadata
- **☁️ Cloud Storage** - AWS S3 integration for secure file storage
- **🛡️ Security** - Role-based access control and data validation
- **📊 Testing** - Comprehensive test suite with 15/15 tests passing

## 🚀 Quick Navigation

### Getting Started
- **[⚡ Quick Start Guide](./setup/README.md)** - Get up and running in minutes

### API Documentation  
- **[📋 API Overview](./api/README.md)** - Complete API reference
- **[🔌 Frontend Integration](./frontend-integration.md)** - Complete guide for frontend developers
- **[💻 Framework Examples](./frontend-examples.md)** - React, Vue, Angular implementation examples

### System Concepts
- **[🏗️ Architecture Overview](./concepts/README.md)** - System architecture and design
- **[ Email Verification](./concepts/email-verification.md)** - Email verification system  
- **[☁️ File Upload & S3](./concepts/file-upload-s3-flow.md)** - File handling and cloud storage

### Testing & Development
- **[🧪 Testing Guide](./concepts/testing.md)** - Running and writing tests
- **[🔄 Contributing](../CONTRIBUTING.md)** - Development workflow and guidelines

## 📊 System Status

### Current Implementation Status
- ✅ **Authentication System** - Complete with email verification
- ✅ **User Management** - Full CRUD with role-based access  
- ✅ **Document Management** - Upload, download, search, public consultation
- ✅ **Cloud Storage** - AWS S3 integration working
- ✅ **API Documentation** - Comprehensive endpoint documentation
- ✅ **Testing Suite** - 15/15 tests passing (100% success rate)

### Technical Specifications
- **Framework**: Laravel 11
- **Database**: PostgreSQL with ULIDs
- **Authentication**: JWT with tymon/jwt-auth
- **Storage**: AWS S3 with Laravel Flysystem
- **Testing**: PHPUnit with Feature & Unit tests
- **API**: RESTful with JSON responses

## 🎓 Learning Path

If you're new to this system, we recommend following this learning path:

1. **[⚡ Quick Start](./setup/README.md)** - Get the system running locally
2. **[� API Reference](./api/README.md)** - Learn the API endpoints
3. **[🏗️ Architecture](./concepts/README.md)** - Understand system design
4. **[🧪 Testing](./concepts/testing.md)** - Run tests and validate functionality

## 💡 Examples & Use Cases

### Common Operations
```bash
# Login and get token
curl -X POST localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@pharmedice.com","senha":"admin123"}'

# List documents (authenticated)  
curl -X GET localhost:8000/api/laudos \
  -H "Authorization: Bearer YOUR_TOKEN"

# Search documents
curl -X GET "localhost:8000/api/laudos/buscar?busca=exame" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Integration Examples
- **Frontend Integration** - React/Vue/Angular API consumption
- **Mobile Apps** - JWT token management for mobile clients
- **Third-party Systems** - API integration with external services

## 🆘 Support & Troubleshooting

### Common Issues
- **[🔧 Setup Issues](./setup/README.md)** - Common installation problems
- **[� API Reference](./api/README.md)** - API-related troubleshooting  
- **[📄 File Upload Issues](./concepts/file-upload-s3-flow.md)** - S3 and upload problems

### Getting Help
1. Check the relevant documentation section first
2. Review the [troubleshooting guides](./setup/troubleshooting.md)
3. Look at the test files for usage examples
4. Check the [contributing guide](../CONTRIBUTING.md) for development setup

## 🚀 Next Steps

Ready to start? Here are your next steps:

1. **Development**: Follow the [Quick Start Guide](./setup/README.md)
2. **Integration**: Check the [API Documentation](./api/README.md)
3. **Contribution**: Review the [Contributing Guidelines](../CONTRIBUTING.md)

---

**Last Updated**: October 2025  
**Version**: 1.0.0  
**Status**: Production Ready ✅