# StreamVibe Tests

This directory contains the complete test suite for the StreamVibe application, organized following **Test-Driven Development (TDD)** principles and **Clean Architecture**.

## Test Structure

The tests are organized into three main categories following the testing pyramid:

```
tests/
├── Unit/                    # Fast, isolated unit tests
│   ├── Domain/             # Domain layer tests (entities, value objects)
│   └── Application/        # Application layer tests (use cases, DTOs)
├── Integration/            # Integration tests (services, repositories)
└── Application/            # End-to-end API tests
    └── Api/               # API endpoint tests
```

## Test Categories

### 🔸 Unit Tests (`tests/Unit/`)

**Fast, isolated tests** that verify individual units of code in isolation.

- **Domain Tests**: Test entities, value objects, and domain logic
- **Application Tests**: Test use cases, DTOs, and application services
- **Characteristics**:
  - No external dependencies (database, HTTP calls, file system)
  - Use mocks and stubs
  - Execute in milliseconds
  - Should make up 70-80% of your test suite

**Example**: Testing User entity methods, RegisterUserUseCase logic

### 🔸 Integration Tests (`tests/Integration/`)

**Medium-scope tests** that verify the interaction between different components.

- Test repository implementations with real database
- Test service integrations
- Test configuration and dependency injection
- **Characteristics**:
  - Use real dependencies where needed
  - Test component interactions
  - Slower than unit tests
  - Should make up 15-25% of your test suite

**Example**: UserRepository database operations, service container configuration

### 🔸 Application Tests (`tests/Application/`)

**End-to-end tests** that verify the complete application behavior through HTTP API calls.

- Test complete user journeys
- Test API endpoints
- Test authentication flows
- **Characteristics**:
  - Test through HTTP interface
  - Use real database (test environment)
  - Slowest tests
  - Should make up 5-15% of your test suite

**Example**: Complete user registration flow, movie search API

## Running Tests

### Run All Tests
```bash
php bin/phpunit
```

### Run by Test Suite
```bash
# Unit tests only (fastest)
php bin/phpunit --testsuite=Unit

# Integration tests
php bin/phpunit --testsuite=Integration

# Application/API tests
php bin/phpunit --testsuite=Application
```

### Run Specific Test Files
```bash
# Single test file
php bin/phpunit tests/Unit/Domain/User/UserTest.php

# All tests in a directory
php bin/phpunit tests/Unit/Application/Authentication/
```

### Run with Coverage
```bash
php bin/phpunit --coverage-html var/coverage/html
```

## Test Environment Setup

### Database Configuration

Tests use a separate test database configuration:

```yaml
# .env.test
DATABASE_URL="sqlite:///%kernel.project_dir%/var/test.db"
KERNEL_CLASS='App\Kernel'
```

### Database Solicitation

The test suite uses **DAMA Doctrine Test Bundle** to automatically:
- Begin a database transaction before each test
- Roll back the transaction after each test
- Ensure test isolation without manual cleanup

## Writing Tests

### TDD Approach

Follow the **Red-Green-Refactor** cycle:

1. **🔴 RED**: Write a failing test first
2. **🟢 GREEN**: Write minimal code to make test pass
3. **🔵 REFACTOR**: Improve code while keeping tests green

### Test Structure

Use the **Arrange-Act-Assert** pattern:

```php
public function testUserCanBeCreatedWithEmail(): void
{
    // Arrange
    $email = 'test@example.com';
    $user = new User();
    
    // Act
    $user->setEmail($email);
    
    // Assert
    $this->assertEquals($email, $user->getEmail());
}
```

### Naming Conventions

- Test classes end with `Test`: `UserTest`, `RegisterUserUseCaseTest`
- Test methods start with `test`: `testUserRegistrationSucceeds`
- Use descriptive names: `testRegistrationFailsWithInvalidEmail`

### Mock Guidelines

- Mock external dependencies in unit tests
- Use `createMock()` for simple mocks
- Use `createStub()` when you only need method returns
- Verify interactions with `expects()` when behavior matters

```php
// Example mock usage
$mockRepository = $this->createMock(UserRepositoryInterface::class);
$mockRepository->expects($this->once())
    ->method('save')
    ->with($this->isInstanceOf(User::class));
```

## Test Data

### Fixtures

Create test data using:
- **Object Mother** pattern for complex entities
- **Builder** pattern for flexible test data construction
- **Factories** for generating test data variations

### Email Testing

Email testing is configured automatically:
```php
// Assert emails in tests
$this->assertEmailCount(1);
$messages = $this->getMailerMessages();
$this->assertEmailAddressContains($messages[0], 'to', 'user@example.com');
```

## Clean Architecture Test Organization

### Domain Layer Tests
```php
// tests/Unit/Domain/User/UserTest.php
class UserTest extends TestCase
{
    // Test entity behavior, business rules
    // No framework dependencies
    // Pure PHP unit tests
}
```

### Application Layer Tests
```php
// tests/Unit/Application/Authentication/RegisterUserUseCaseTest.php
class RegisterUserUseCaseTest extends TestCase
{
    // Test use case logic
    // Mock repository interfaces
    // Verify business workflows
}
```

### Infrastructure Layer Tests
```php
// tests/Integration/UserRepositoryTest.php  
class UserRepositoryTest extends KernelTestCase
{
    // Test repository implementations
    // Use real database
    // Verify persistence behavior
}
```

### API Tests
```php
// tests/Application/Api/Authentication/RegistrationApiTest.php
class RegistrationApiTest extends WebTestCase
{
    // Test HTTP endpoints
    // Verify complete request/response cycle
    // Test authentication, validation, etc.
}
```

## Best Practices

### ✅ Do's

- Write tests first (TDD)
- Keep tests simple and focused
- Use descriptive test names
- Test behavior, not implementation
- Mock external dependencies in unit tests
- Use real database for integration tests
- Maintain test independence
- Keep tests fast (especially unit tests)

### ❌ Don'ts

- Don't test framework code
- Don't test private methods directly
- Don't share state between tests
- Don't make tests too complex
- Don't ignore failing tests
- Don't skip test documentation

## Debugging Tests

### Common Issues

1. **Database State**: Tests fail due to shared state
   - Solution: Use DAMA bundle for transaction isolation

2. **Mock Configuration**: Mocks not configured correctly
   - Solution: Verify mock expectations and return values

3. **Environment**: Test environment differs from expected
   - Solution: Check `.env.test` configuration

### Debug Commands
```bash
# Run single test with verbose output
php bin/phpunit --verbose tests/Unit/Domain/User/UserTest.php

# Stop on first failure
php bin/phpunit --stop-on-failure

# Show detailed error information
php bin/phpunit --debug
```

## Continuous Integration

Tests run automatically on:
- Every commit (pre-commit hook)
- Pull requests
- Main branch pushes

### Performance Targets

- **Unit tests**: < 100ms total
- **Integration tests**: < 5 seconds total  
- **Application tests**: < 30 seconds total
- **All tests**: < 60 seconds total

## Migration from Legacy Tests

The old monolithic tests have been refactored to follow Clean Architecture:

- ❌ `LoginControllerTest.php` → ✅ `tests/Application/Api/Authentication/`
- ❌ `RegistrationControllerTest.php` → ✅ `tests/Application/Api/Authentication/`
- ❌ `ResetPasswordControllerTest.php` → ✅ `tests/Application/Api/Authentication/`

The new structure provides:
- Better test organization
- Faster feedback loops
- Improved maintainability
- Clear separation of concerns

## Contributing

When adding new features:

1. Start with failing tests (TDD)
2. Write unit tests for domain logic
3. Add integration tests for infrastructure
4. Create API tests for user-facing features
5. Ensure all tests pass
6. Verify test coverage is maintained

For questions or issues with tests, please refer to the project's main documentation or open an issue.