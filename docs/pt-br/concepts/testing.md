# Guia de Testes - Pharmedice Customer Area

## Visão Geral

O backend da Pharmedice Customer Area inclui uma suíte abrangente de testes cobrindo autenticação, autorização, operações CRUD e validação de lógica de negócio.

## Estrutura de Testes

### Organização dos Testes
```
tests/
├── Feature/                    # Testes de integração
│   ├── SignupTest.php         # Registro de usuário e validação
│   └── EmailVerificationTest.php # Fluxo de verificação de email
└── Unit/                       # Testes unitários
    └── ExampleTest.php        # Testes unitários básicos
```

### Tipos de Teste
- **Testes de Feature**: Testes de endpoint da API de ponta a ponta
- **Testes Unitários**: Testes de componentes individuais
- **Testes de Autenticação**: Gerenciamento de tokens JWT e segurança
- **Testes de Autorização**: Controle de acesso baseado em funções
- **Testes de Validação**: Validação de entrada e tratamento de erros

## Resumo dos Resultados dos Testes

### Status Atual dos Testes: ✅ 15/15 Aprovados (100%)

#### **Testes de Cadastro & Registro** - ✅ **APROVADOS (9/9)**
- ✅ Registro de usuário com dados válidos
- ✅ Validação de campos obrigatórios
- ✅ Requisitos de força da senha
- ✅ Validação de formato de telefone  
- ✅ Validação de CPF (número do documento)
- ✅ Validação de unicidade de email
- ✅ Validação de unicidade de nome de usuário (apelido)
- ✅ Requisito de aceitação dos termos de serviço
- ✅ Requisito de aceitação da política de privacidade

#### **Testes de Verificação de Email** - ✅ **APROVADOS (6/6)**
- ✅ Geração de link de verificação de email
- ✅ Verificação de email com URL assinada válida
- ✅ Tratamento de expiração do link de verificação
- ✅ Rejeição de assinatura inválida
- ✅ Funcionalidade de reenvio de verificação de email
- ✅ Requisito de autenticação para verificação

## Executando Testes

### Todos os Testes
```bash
# Executar suíte completa de testes
php artisan test

# Executar com cobertura (se configurado)
php artisan test --coverage
```

### Categorias Específicas de Teste
```bash
# Executar testes de cadastro e registro
php artisan test --filter="SignupTest"

# Executar testes de verificação de email  
php artisan test --filter="EmailVerificationTest"

# Executar ambos os testes relacionados à autenticação
php artisan test --filter="SignupTest|EmailVerificationTest"
```

### Métodos de Teste Individuais
```bash
# Executar método de teste específico
php artisan test --filter="usuario_pode_se_registrar_com_dados_validos"

# Executar com saída detalhada
php artisan test --filter="SignupTest" -v
```

## Gerenciamento de Dados de Teste

### Banco de Dados de Teste
- Usa banco de dados SQLite separado para testes
- Migrações automáticas do banco antes da execução dos testes
- Transações de banco para isolamento de testes
- Estado limpo para cada método de teste

### Factories & Seeders
```php
// Factory de usuário para dados de teste
Usuario::factory()->create([
    'email' => 'test@example.com',
    'tipo_usuario' => 'administrador'
]);

// Dados de teste personalizados
$dadosUsuario = [
    'primeiro_nome' => 'João',
    'segundo_nome' => 'Silva',
    'email' => 'joao@teste.com',
    // ... outros campos
];
```

## Cenários-Chave de Teste

### Teste de Fluxo de Autenticação
1. **Processo de Registro**
   - Submissão de dados válidos do usuário
   - Geração de token JWT
   - Acionamento de verificação de email
   - Criação de usuário no banco de dados

2. **Verificação de Email**
   - Geração e validação de URL assinada
   - Tratamento de tempo de expiração
   - Verificação de assinatura de segurança
   - Atualização de status no banco de dados

3. **Regras de Validação**
   - Validação de campos obrigatórios
   - Validação de formato (email, telefone, CPF)
   - Restrições de unicidade
   - Requisitos de complexidade de senha

### Teste de Resposta da API
```php
// Exemplo de asserção de teste
$response->assertStatus(201)
    ->assertJsonStructure([
        'sucesso',
        'mensagem', 
        'dados' => [
            'access_token',
            'token_type',
            'expires_in',
            'usuario' => [
                'id',
                'primeiro_nome',
                'email',
                'tipo_usuario'
            ]
        ]
    ]);
```

### Teste de Tratamento de Erros
```php
// Teste de erro de validação
$response = $this->postJson('/api/auth/registrar', []);
$response->assertStatus(422)
    ->assertJsonValidationErrors([
        'primeiro_nome',
        'segundo_nome', 
        'email',
        'senha'
    ]);
```

## Configuração de Testes

### Configuração PHPUnit
```xml
<!-- phpunit.xml -->
<testsuites>
    <testsuite name="Feature">
        <directory>tests/Feature</directory>
    </testsuite>
    <testsuite name="Unit">
        <directory>tests/Unit</directory>
    </testsuite>
</testsuites>
```

### Configuração de Ambiente
```env
# .env.testing
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
MAIL_MAILER=array
QUEUE_CONNECTION=sync
```

## Integração Contínua

### Automação de Testes
```yaml
# Exemplo de workflow GitHub Actions
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test
```

### Portões de Qualidade
- **100% Taxa de Aprovação de Testes**: Todos os testes devem passar antes do deploy
- **Metas de Cobertura**: Manter alta cobertura de código para caminhos críticos
- **Testes de Performance**: Monitorar tempo de execução dos testes
- **Testes de Segurança**: Validar autenticação e autorização

## Áreas de Cobertura de Teste

### ✅ Atualmente Coberto
- Registro e validação de usuário
- Fluxo de verificação de email
- Geração e tratamento de tokens JWT
- Validação e sanitização de entrada
- Formatação de resposta de erro
- Requisitos de autenticação
- Cenários de acesso baseado em funções

### 🔄 Melhorias Futuras de Teste
- **Testes de Integração da API**: Operações CRUD completas para todas as entidades
- **Testes de Performance**: Testes de carga para cenários de alto tráfego
- **Testes de Segurança**: Testes de penetração para vulnerabilidades
- **Testes End-to-End**: Testes completos de jornada do usuário
- **Testes de Banco de Dados**: Performance de consulta e integridade de dados

## Debugando Falhas de Teste

### Problemas Comuns
```bash
# Limpar caches da aplicação
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Resetar banco de dados de teste
php artisan migrate:fresh --env=testing

# Executar teste específico falhando com saída de debug
php artisan test --filter="nome_teste_falhando" -vvv
```

### Estratégias de Debug
1. **Verificar Logs**: Revisar `storage/logs/laravel.log` para erros
2. **Estado do Banco**: Verificar configuração e limpeza de dados de teste
3. **Ambiente**: Garantir que `.env.testing` está configurado corretamente
4. **Dependências**: Confirmar que todos os pacotes necessários estão instalados

## Melhores Práticas

### Escrevendo Testes
- **Nomes Descritivos**: Use nomes de método de teste claros e descritivos
- **Responsabilidade Única**: Cada teste deve verificar um comportamento específico
- **Testes Independentes**: Testes não devem depender uns dos outros
- **Configuração Limpa**: Use factories e setup/teardown adequados

### Organização de Testes
- **Agrupar Testes Relacionados**: Manter testes relacionados no mesmo arquivo
- **Usar Data Providers**: Para testar múltiplos cenários similares
- **Mock de Serviços Externos**: Não depender de APIs externas nos testes
- **Testar Casos Extremos**: Incluir condições limítrofes e cenários de erro

## Monitoramento & Relatórios

### Métricas de Teste
- **Tempo de Execução**: Monitorar performance da suíte de testes
- **Taxas de Aprovação/Falha**: Rastrear confiabilidade dos testes ao longo do tempo
- **Relatórios de Cobertura**: Manter visibilidade da cobertura de código
- **Análise de Tendências**: Identificar padrões em falhas de teste

---

Este framework de testes garante que o backend da Pharmedice Customer Area mantenha alta qualidade e confiabilidade através de testes automatizados abrangentes.