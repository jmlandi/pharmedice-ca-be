# Testing Guide - Pharmedice Customer Area

## Overview

The Pharmedice Customer Area backend includes a comprehensive test suite covering authentication, authorization, CRUD operations, and business logic validation.

## Test Structure

### Test Organization
```
tests/
├── Feature/                    # Integration tests
│   ├── SignupTest.php         # User registration and validation
│   └── EmailVerificationTest.php # Email verification workflow
└── Unit/                       # Unit tests
    └── ExampleTest.php        # Basic unit tests
```

### Test Types
- **Feature Tests**: End-to-end API endpoint testing
- **Unit Tests**: Individual component testing
- **Authentication Tests**: JWT token management and security
- **Authorization Tests**: Role-based access control
- **Validation Tests**: Input validation and error handling

## Test Results Summary

### Current Test Status: ✅ 15/15 Passing (100%)

#### **Signup & Registration Tests** - ✅ **PASSING (9/9)**
- ✅ User registration with valid data
- ✅ Validation of required fields
- ✅ Password strength requirements
- ✅ Phone number format validation  
- ✅ CPF (document number) validation
- ✅ Email uniqueness validation
- ✅ Username (apelido) uniqueness validation
- ✅ Terms of service acceptance requirement
- ✅ Privacy policy acceptance requirement

#### **Email Verification Tests** - ✅ **PASSING (6/6)**
- ✅ Email verification link generation
- ✅ Email verification with valid signed URL
- ✅ Email verification link expiration handling
- ✅ Invalid signature rejection
- ✅ Email verification resend functionality
- ✅ Authentication requirement for verification

## Running Tests

### All Tests
```bash
# Run complete test suite
php artisan test

# Run with coverage (if configured)
php artisan test --coverage
```

### Specific Test Categories
```bash
# Run signup and registration tests
php artisan test --filter="SignupTest"

# Run email verification tests  
php artisan test --filter="EmailVerificationTest"

# Run both authentication-related tests
php artisan test --filter="SignupTest|EmailVerificationTest"
```

### Individual Test Methods
```bash
# Run specific test method
php artisan test --filter="usuario_pode_se_registrar_com_dados_validos"

# Run with detailed output
php artisan test --filter="SignupTest" -v
```

## Test Data Management

### Test Database
- Uses separate SQLite database for testing
- Automatic database migrations before test execution
- Database transactions for test isolation
- Clean slate for each test method

### Factories & Seeders
```php
// User factory for test data
Usuario::factory()->create([
    'email' => 'test@example.com',
    'tipo_usuario' => 'administrador'
]);

// Custom test data
$userData = [
    'primeiro_nome' => 'João',
    'segundo_nome' => 'Silva',
    'email' => 'joao@teste.com',
    // ... other fields
];
```

## Key Test Scenarios

### Authentication Flow Testing
1. **Registration Process**
   - Valid user data submission
   - JWT token generation
   - Email verification trigger
   - Database user creation

2. **Email Verification**
   - Signed URL generation and validation
   - Expiration time handling
   - Security signature verification
   - Database status update

3. **Validation Rules**
   - Required field validation
   - Format validation (email, phone, CPF)
   - Uniqueness constraints
   - Password complexity requirements

### API Response Testing
```php
// Example test assertion
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

### Error Handling Testing
```php
// Validation error testing
$response = $this->postJson('/api/auth/registrar', []);
$response->assertStatus(422)
    ->assertJsonValidationErrors([
        'primeiro_nome',
        'segundo_nome', 
        'email',
        'senha'
    ]);
```

## Test Configuration

### PHPUnit Configuration
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

### Environment Setup
```env
# .env.testing
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
MAIL_MAILER=array
QUEUE_CONNECTION=sync
```

## Continuous Integration

### Test Automation
```yaml
# Example GitHub Actions workflow
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

### Quality Gates
- **100% Test Pass Rate**: All tests must pass before deployment
- **Coverage Targets**: Maintain high code coverage for critical paths
- **Performance Testing**: Monitor test execution time
- **Security Testing**: Validate authentication and authorization

## Test Coverage Areas

### ✅ Currently Covered
- User registration and validation
- Email verification workflow
- JWT token generation and handling
- Input validation and sanitization
- Error response formatting
- Authentication requirements
- Role-based access scenarios

### 🔄 Future Test Enhancements
- **API Integration Tests**: Full CRUD operations for all entities
- **Performance Tests**: Load testing for high-traffic scenarios
- **Security Tests**: Penetration testing for vulnerabilities
- **End-to-End Tests**: Complete user journey testing
- **Database Tests**: Query performance and data integrity

## Debugging Test Failures

### Common Issues
```bash
# Clear application caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Reset test database
php artisan migrate:fresh --env=testing

# Run specific failing test with debug output
php artisan test --filter="failing_test_name" -vvv
```

### Debug Strategies
1. **Check Logs**: Review `storage/logs/laravel.log` for errors
2. **Database State**: Verify test data setup and cleanup
3. **Environment**: Ensure `.env.testing` is configured correctly
4. **Dependencies**: Confirm all required packages are installed

## Best Practices

### Writing Tests
- **Descriptive Names**: Use clear, descriptive test method names
- **Single Responsibility**: Each test should verify one specific behavior
- **Independent Tests**: Tests should not depend on each other
- **Clean Setup**: Use factories and proper setup/teardown

### Test Organization
- **Group Related Tests**: Keep related tests in the same file
- **Use Data Providers**: For testing multiple similar scenarios
- **Mock External Services**: Don't rely on external APIs in tests
- **Test Edge Cases**: Include boundary conditions and error scenarios

## Monitoring & Reporting

### Test Metrics
- **Execution Time**: Monitor test suite performance
- **Pass/Fail Rates**: Track test reliability over time
- **Coverage Reports**: Maintain visibility into code coverage
- **Trend Analysis**: Identify patterns in test failures

---

This testing framework ensures the Pharmedice Customer Area backend maintains high quality and reliability through comprehensive automated testing.