# Documentation Navigation

This document provides a navigation guide for the Pharmedice Customer Area Backend documentation.

## 🌍 Language Options

### 🇺🇸 English Documentation
- [Complete Documentation](./en/README.md)
- [API Reference](./en/api/README.md)  
- [Setup Guide](./en/setup/README.md)
- [System Concepts](./en/concepts/README.md)

### 🇧🇷 Documentação em Português  
- [Documentação Completa](./pt-br/README.md)
- [Referência da API](./pt-br/api/README.md)
- [Guia de Instalação](./pt-br/setup/README.md)  
- [Conceitos do Sistema](./pt-br/concepts/README.md)

## 📚 Documentation Structure

```
docs/
├── en/                          # 🇺🇸 English Documentation
│   ├── README.md               # Complete system overview
│   ├── api/
│   │   └── README.md           # API reference & endpoints
│   ├── setup/
│   │   └── README.md           # Installation & configuration
│   └── concepts/
│       ├── README.md           # System architecture
│       └── email-verification.md
├── pt-br/                      # 🇧🇷 Portuguese Documentation  
│   ├── README.md               # Visão geral completa do sistema
│   ├── api/
│   │   └── README.md           # Referência da API & endpoints
│   ├── setup/
│   │   └── README.md           # Instalação & configuração
│   └── concepts/
│       ├── README.md           # Arquitetura do sistema
│       └── email-verification.md
├── CONTRIBUTING.md             # Development guidelines
├── NAVIGATION.md               # This navigation file  
└── README.md                   # Main documentation index
```

## 🎯 Getting Started

### For English Speakers
1. **New Developer Setup**: [Setup Guide](./en/setup/README.md)
2. **API Integration**: [API Reference](./en/api/README.md)  
3. **System Understanding**: [System Concepts](./en/concepts/README.md)

### Para Falantes de Português
1. **Configuração para Novos Desenvolvedores**: [Guia de Instalação](./pt-br/setup/README.md)
2. **Integração da API**: [Referência da API](./pt-br/api/README.md)
3. **Entendimento do Sistema**: [Conceitos do Sistema](./pt-br/concepts/README.md)

## 🔄 Migration Notice

**New Structure**: Documentation has been reorganized into language-specific folders (`en/` and `pt-br/`) for better organization and maintenance.

**Recommendation**: Use the new structured documentation in `en/` or `pt-br/` folders rather than the legacy files in the root directories.

## 🤝 Contributing to Documentation

When adding new documentation:

1. **Choose Language**: Add to both `en/` and `pt-br/` folders when possible
2. **Follow Structure**: Place files in appropriate sections (api/, setup/, concepts/)  
3. **Update Navigation**: Update this NAVIGATION.md file
4. **Consistent Formatting**: Follow existing markdown conventions
5. **Include Examples**: Add code examples where relevant
6. **Update Index**: Update language-specific README.md files if needed

### Translation Guidelines
- Keep technical terms consistent across languages
- Maintain the same file structure in both language folders  
- Update both versions when making changes
- Use appropriate language conventions (e.g., date formats, currency)