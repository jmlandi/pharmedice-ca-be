# 📚 Mapa da Documentação

## 🗂️ Estrutura Organizada

A documentação do projeto foi reorganizada para facilitar a navegação e manutenção:

```
📁 docs/
├── 📄 README.md                          # 👈 Índice principal da documentação
├── 📄 CONTRIBUTING.md                    # 👈 Guia para contribuir com a documentação
├── 📁 api/
│   └── 📄 README.md                      # 👈 Documentação completa da API REST
├── 📁 setup/
│   └── 📄 README.md                      # 👈 Guia detalhado de instalação
├── 📁 concepts/
│   └── 📄 usuario-id.md                  # 👈 Conceito sobre o campo usuario_id
└── 📁 changelogs/
    └── 📄 laudos-consulta-publica.md     # 👈 Mudança para consulta pública de laudos
```

## 🎯 Navegação por Perfil

### 👨‍💻 **Desenvolvedor Frontend**
1. **Começar por**: [📋 API Documentation](./api/README.md)
2. **Entender conceitos**: [💡 Usuario ID](./concepts/usuario-id.md)
3. **Ver mudanças**: [📝 Changelogs](./changelogs/)

### ⚙️ **DevOps/Infra**
1. **Começar por**: [🛠️ Setup Guide](./setup/README.md)
2. **Configuração completa**: [📋 API Reference](./api/README.md)
3. **Mudanças recentes**: [📝 Changelogs](./changelogs/)

### 📊 **Product Owner**
1. **Funcionalidades**: [📝 Laudos Consulta Pública](./changelogs/laudos-consulta-publica.md)
2. **Regras de negócio**: [💡 Concepts](./concepts/)
3. **Capacidades da API**: [📋 API Overview](./api/README.md)

### 🆕 **Novo no Projeto**
1. **Visão geral**: [📄 README Principal](../README.md)
2. **Instalação**: [🛠️ Setup Guide](./setup/README.md)
3. **Teste da API**: [📋 API Examples](./api/README.md)
4. **Conceitos**: [💡 Concepts](./concepts/)

## 📋 Conteúdo de Cada Seção

### 🔗 [API Documentation](./api/README.md)
- ✅ Todos os endpoints disponíveis
- ✅ Exemplos de request/response
- ✅ Códigos de status HTTP
- ✅ Autenticação JWT
- ✅ Filtros e paginação
- ✅ Estrutura do banco de dados

### 🔗 [Setup Guide](./setup/README.md)
- ✅ Pré-requisitos do sistema
- ✅ Configuração passo a passo
- ✅ Variáveis de ambiente
- ✅ Comandos de inicialização
- ✅ Testes básicos da API
- ✅ Troubleshooting comum

### 🔗 [Concepts](./concepts/)
- ✅ **Usuario ID**: Explicação sobre quem é o criador do laudo
- ✅ Relacionamentos entre entidades
- ✅ Regras de negócio específicas
- ✅ Fluxos de dados importantes

### 🔗 [Changelogs](./changelogs/)
- ✅ **Consulta Pública de Laudos**: Implementação completa
- ✅ Breaking changes documentadas
- ✅ Novas funcionalidades explicadas
- ✅ Impacto no frontend/integração

## 🚀 Links Rápidos

| Preciso de... | Ir para... |
|---------------|------------|
| 🔌 **Integrar com a API** | [📋 API Docs](./api/README.md) |
| ⚙️ **Instalar o projeto** | [🛠️ Setup Guide](./setup/README.md) |
| 🧠 **Entender regras de negócio** | [💡 Concepts](./concepts/) |
| 📰 **Ver o que mudou** | [📝 Changelogs](./changelogs/) |
| 📝 **Contribuir com docs** | [🤝 Contributing](./CONTRIBUTING.md) |
| 🏠 **Visão geral do projeto** | [📄 README Principal](../README.md) |

## 💡 Como Usar Esta Documentação

### 🆕 **Primeira vez no projeto?**
```
1. 📄 README Principal          (visão geral)
2. 🛠️ Setup Guide             (instalar)
3. 📋 API Docs                 (testar endpoints)
4. 💡 Concepts                 (entender regras)
```

### 🔧 **Desenvolvendo feature?**
```
1. 📝 Changelogs               (mudanças recentes)
2. 📋 API Docs                 (endpoints existentes)
3. 💡 Concepts                 (regras de negócio)
4. 🤝 Contributing             (como documentar)
```

### 🚀 **Deploy/Produção?**
```
1. 🛠️ Setup Guide             (configuração completa)
2. 📝 Changelogs               (breaking changes)
3. 📋 API Docs                 (validar endpoints)
```

## 🎯 Benefícios da Organização

- ✅ **Fácil navegação** por tipo de conteúdo
- ✅ **Manutenção simples** - cada doc tem propósito específico
- ✅ **Onboarding rápido** - fluxo claro para novos desenvolvedores
- ✅ **Versionamento claro** - changelogs organizados por feature
- ✅ **Separação de responsabilidades** - setup vs API vs conceitos

---

*A documentação é a ponte entre o código e quem vai usá-lo. Mantenha-a sempre atualizada! 🌉*