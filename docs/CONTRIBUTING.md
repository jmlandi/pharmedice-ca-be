# 📝 Guia de Contribuição para Documentação

## 🎯 Objetivo

Manter a documentação sempre atualizada, clara e útil para desenvolvedores, DevOps e stakeholders do projeto.

## 📁 Estrutura da Documentação

```
docs/
├── README.md                    # Índice principal da documentação
├── api/
│   └── README.md               # Documentação completa da API
├── setup/
│   └── README.md               # Guia de instalação e configuração
├── concepts/
│   └── usuario-id.md          # Conceitos específicos do sistema
└── changelogs/
    └── laudos-consulta-publica.md  # Histórico de mudanças
```

## ✅ Diretrizes para Contribuição

### 1. **API Documentation** (`docs/api/README.md`)

**Sempre atualizar quando:**
- Adicionar novo endpoint
- Modificar payload de request/response
- Alterar códigos de status HTTP
- Mudar regras de autenticação/autorização

**Formato padrão:**
```markdown
#### METHOD /endpoint
Descrição do que faz o endpoint.

**Payload:**
```json
{
  "campo": "exemplo"
}
```

**Resposta:**
```json
{
  "success": true,
  "data": {...}
}
```
```

### 2. **Setup Guide** (`docs/setup/README.md`)

**Sempre atualizar quando:**
- Adicionar nova dependência
- Modificar processo de instalação
- Alterar variáveis de ambiente
- Mudar comandos de setup

### 3. **Concepts** (`docs/concepts/`)

**Criar novo arquivo quando:**
- Implementar conceito complexo do negócio
- Definir regras importantes do sistema
- Explicar relacionamentos entre entidades

**Nome do arquivo:** `conceito-principal.md` (kebab-case)

### 4. **Changelogs** (`docs/changelogs/`)

**Criar novo arquivo para cada:**
- Feature significativa
- Breaking change
- Mudança de arquitetura

**Nome do arquivo:** `feature-principal-YYYYMMDD.md`

## 🔄 Processo de Atualização

### Para Desenvolvedores

1. **Ao criar nova feature:**
   ```bash
   # 1. Implementar código
   # 2. Atualizar documentação relevante
   # 3. Criar changelog se necessário
   # 4. Commit tudo junto
   git add . && git commit -m "feat: nova funcionalidade + docs"
   ```

2. **Ao fazer breaking change:**
   - ⚠️ Criar arquivo em `changelogs/` explicando a mudança
   - 📝 Atualizar `docs/api/README.md` com novos contratos
   - 🔄 Atualizar exemplos de código

3. **Ao adicionar endpoint:**
   - 📋 Adicionar na seção correta de `docs/api/README.md`
   - 🧪 Incluir exemplo de request/response
   - 🔒 Documentar requisitos de autenticação

### Para Product Owners

1. **Ao solicitar nova feature:**
   - 💭 Explicar regras de negócio no issue
   - 📝 Revisar documentação após implementação
   - ✅ Validar exemplos de uso

## 📋 Checklist de Revisão

### Para Pull Requests

- [ ] 📚 Documentação da API atualizada (se aplicável)
- [ ] 🛠️ Guia de setup atualizado (se aplicável)
- [ ] 💡 Conceitos documentados (se aplicável)
- [ ] 📝 Changelog criado (se breaking change)
- [ ] 🧪 Exemplos de código testados
- [ ] 🔍 Links internos funcionando
- [ ] 📖 Texto claro e sem erros de português

### Para Releases

- [ ] 🎯 README principal atualizado
- [ ] 📋 API docs refletem todas as mudanças
- [ ] 🔄 Changelogs organizados por data
- [ ] 🚀 Guia de migração (se necessário)

## 💡 Dicas de Escrita

### ✅ Boas Práticas

- **Use emojis** para facilitar navegação visual
- **Seja específico** em exemplos de código
- **Inclua códigos de status** HTTP sempre
- **Teste todos os exemplos** antes de documentar
- **Mantenha linguagem consistente** (brasileiro)

### ❌ Evite

- Documentar implementação interna desnecessária
- Exemplos genéricos sem contexto real
- Links externos que podem quebrar
- Informações que ficam obsoletas rapidamente

## 🔍 Ferramentas Úteis

### Validação de Markdown
```bash
# Instalar markdownlint
npm install -g markdownlint-cli

# Validar arquivos
markdownlint docs/**/*.md
```

### Geração de TOC
```bash
# Instalar markdown-toc
npm install -g markdown-toc

# Gerar TOC
markdown-toc docs/api/README.md
```

## 📞 Contato

Para dúvidas sobre a documentação:
- Abra issue no repositório
- Marque a tag `documentation`
- Mencione `@equipe-docs` (se aplicável)

---

*Lembre-se: Documentação atualizada = Desenvolvedores felizes = Produto melhor* 🚀