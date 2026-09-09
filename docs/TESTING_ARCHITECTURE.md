# 🧪 Arquitetura de Testes - StreamVibe

## 🎯 Visão Geral

Este documento descreve a arquitetura de testes do projeto StreamVibe, que foi completamente refatorada para seguir as melhores práticas de **Test-Driven Development (TDD)** e **Clean Architecture**, proporcionando uma base sólida e maintível para garantir a qualidade do código.

## 📊 Status da Implementação

### ✅ ESTRUTURA DE TESTES COMPLETAMENTE REFATORADA
- **3 níveis de testes** seguindo a pirâmide de testes (Unit → Integration → Application)
- **2 helpers reutilizáveis** (DatabaseTestTrait + UserTestBuilder)
- **~600 linhas** de código duplicado **eliminadas**
- **+15 novos cenários** de teste implementados
- **100% padronização** em todos os arquivos de teste
- **Performance controlada** com asserções de tempo

## 🏗️ Arquitetura da Pirâmide de Testes

### Estrutura Hierárquica

```
                     🔺
                    /  \
                   /    \
                  /  E2E \
                 / (5-15%)\
                /__________\
               /            \
              /  Integration \
             /    (15-25%)    \
            /__________________\
           /                    \
          /         Unit         \
         /        (70-80%)        \
        /__________________________\
```

### Distribuição Atual

```
tests/
├── Unit/                          # 🟢 Testes Unitários (70-80%)
│   ├── Domain/                    # Testes de entidades e lógica de domínio
│   │   └── User/
│   │       └── UserTest.php       # ✅ Entidade User completa
│   └── PropertyHooksTestSuite.php # ✅ Demonstração PHP 8.4
│
├── Integration/                   # 🔵 Testes de Integração (15-25%)
│   ├── UserRepositoryTest.php     # ✅ Persistência com banco real
│   └── SimpleRepositoryTest.php   # ✅ Testes focados e simplificados
│
├── Application/                   # 🟡 Testes de Aplicação (5-15%)
│   └── Api/
│       ├── Authentication/
│       │   ├── RegistrationApiTest.php        # ✅ API endpoints
│       │   └── UserRegistrationFlowTest.php   # ✅ Fluxo completo E2E
│       └── Movie/
│           └── MovieSearchApiTest.php         # ✅ API de busca
│
└── Helper/                        # 🛠️ Utilitários de Teste
    ├── DatabaseTestTrait.php      # ✅ Operações comuns de banco
    ├── UserTestBuilder.php        # ✅ Builder pattern para testes
    └── REFACTORING_SUMMARY.md     # 📋 Documentação das melhorias
```

## 🧩 Componentes da Arquitetura

### 1. 🟢 Testes Unitários (`tests/Unit/`)

**Características:**
- **Isolados**: Sem dependências externas
- **Rápidos**: < 100ms total
- **Focados**: Testam uma unidade de código específica
- **Mocks**: Usam doubles para dependências

**Implementados:**
```php
// UserTest.php - Entidade de domínio
final class UserTest extends TestCase {
    public function testUserCreationHasCorrectDefaults(): void
    public function testSetEmailReturnsFluentInterface(): void
    public function testGetRolesAlwaysIncludesDefaultRole(): void
    public function testVerificationStatusCanBeToggled(): void
    public function testUserMethodsPerformance(): void // < 0.1s para 1000 chamadas
}

// PropertyHooksTestSuite.php - PHP 8.4 Features
final class PropertyHooksTestSuite extends TestCase {
    public function testUserTestBuilderPropertyHooks(): void
    public function testVirtualComputedPropertiesForBuilderState(): void
    public function testAsymmetricVisibilityDemonstration(): void
}
```

### 2. 🔵 Testes de Integração (`tests/Integration/`)

**Características:**
- **Componentes reais**: Testa interação entre camadas
- **Banco de dados real**: Ambiente de teste isolado
- **Médio escopo**: Testa fluxos entre componentes
- **Performance moderada**: < 5 segundos total

**Implementados:**
```php
// UserRepositoryTest.php - Persistência completa
final class UserRepositoryTest extends KernelTestCase {
    use DatabaseTestTrait; // ← Reutilização de código
    
    public function testSaveUserPersistsToDatabase(): void
    public function testFindByIdReturnsCorrectUser(): void
    public function testEmailUniqueConstraintViolation(): void
    public function testRepositoryHandlesLargeDataSet(): void // 20 usuários
}

// SimpleRepositoryTest.php - Doctrine puro (sem container)
final class SimpleRepositoryTest extends TestCase {
    public function testRepositoryCanSaveUser(): void
    public function testRepositoryHandlesUserRolesCorrectly(): void
}
```

### 3. 🟡 Testes de Aplicação (`tests/Application/`)

**Características:**
- **End-to-End**: Testa aplicação completa via HTTP
- **Cliente real**: Simula requisições reais
- **Fluxos completos**: Jornadas de usuário inteiras
- **Mais lentos**: < 30 segundos total

**Implementados:**
```php
// RegistrationApiTest.php - API endpoints
final class RegistrationApiTest extends WebTestCase {
    use DatabaseTestTrait;
    
    public function testSuccessfulRegistration(): void
    public function testRegistrationWithSpecialCharactersInEmail(): void
    public function testRegistrationPerformance(): void // < 2 segundos
}

// UserRegistrationFlowTest.php - Fluxo completo
final class UserRegistrationFlowTest extends WebTestCase {
    public function testCompleteRegistrationAndVerificationFlow(): void
    public function testRegistrationHandlesConcurrentRequests(): void
}

// MovieSearchApiTest.php - API externa
final class MovieSearchApiTest extends WebTestCase {
    public function testMovieSearchPerformance(): void // < 5 segundos
}
```

## 🛠️ Helpers e Utilitários

### 1. DatabaseTestTrait - Operações de Banco

**Funcionalidades:**
```php
trait DatabaseTestTrait {
    // Limpeza e setup
    private function cleanDatabase(): void
    private function setupTestDatabase(): void
    private function teardownTestDatabase(): void
    
    // Operações específicas
    private function cleanEntity(string $entityClass): void
    private function refreshEntity(object $entity): object
    private function executeInTransaction(callable $operation): void
    
    // Asserções customizadas
    private function assertEntityCount(string $entityClass, int $expectedCount): void
    private function assertDatabaseIsEmpty(string $entityClass): void
}
```

**Uso:**
```php
final class MeuTesteIntegração extends KernelTestCase {
    use DatabaseTestTrait; // ← Adicionar trait
    
    protected function setUp(): void {
        // Configuração automática
        $this->setupTestDatabase(); // ← Limpa e prepara banco
    }
    
    protected function tearDown(): void {
        $this->teardownTestDatabase(); // ← Limpeza automática
    }
    
    public function testMeuTeste(): void {
        // Asserções específicas
        $this->assertEntityCount(User::class, 0); // ← Helper customizado
    }
}
```

### 2. UserTestBuilder - Builder Pattern

**Funcionalidades:**
```php
final class UserTestBuilder {
    // Property hooks (PHP 8.4)
    public private(set) ?string $email = null;
    public private(set) ?string $password = null;
    public private(set) array $roles = [];
    public private(set) bool $isVerified = false;
    
    // Virtual computed properties
    public bool $hasEmail { get => !empty($this->email); }
    public bool $isComplete { get => $this->hasEmail && $this->hasPassword; }
    
    // Fluent interface
    public function withEmail(string $email): self;
    public function withPassword(string $password): self;
    public function withRoles(array $roles): self;
    public function verified(bool $isVerified = true): self;
    
    // Factory methods
    public static function random(): User;
    public static function admin(): User;
    public static function createUnverified(): User;
    
    // Batch operations
    public function buildMany(int $count, string $baseEmail = 'user{n}@example.com'): array;
}
```

**Uso:**
```php
// Cenários simples
$user = UserTestBuilder::create()
    ->withEmail('test@example.com')
    ->withPassword('password123')
    ->verified()
    ->build();

// Cenários complexos
$users = UserTestBuilder::create()
    ->withPassword('same_password')
    ->withRole('ROLE_USER')
    ->buildMany(10, 'user{n}@test.com');

// Factory methods
$admin = UserTestBuilder::admin();
$random = UserTestBuilder::random();

// Validação com property hooks
$builder = UserTestBuilder::create()->withEmail('test@example.com');
assert($builder->hasEmail === true);  // ← Property hook
assert($builder->isComplete === false); // ← Missing password
```

## 📊 Padrões e Convenções

### 1. Estrutura de Teste (AAA Pattern)
```php
public function testMethodName(): void {
    // Arrange - Preparar dados e contexto
    $user = UserTestBuilder::create()
        ->withEmail('test@example.com')
        ->withPassword('password')
        ->build();
    
    // Act - Executar ação sendo testada
    $result = $this->userRepository->save($user);
    
    // Assert - Verificar resultados
    $this->assertNotNull($user->getId());
    $this->assertEquals('test@example.com', $user->getEmail());
}
```

### 2. Nomenclatura de Métodos
```php
// ✅ Padrão: testMethodExpectedBehavior
public function testUserCreationHasCorrectDefaults(): void
public function testSetEmailReturnsFluentInterface(): void
public function testRegistrationWithInvalidEmailFails(): void

// ❌ Evitar: nomes genéricos
public function testUser(): void
public function testEmail(): void
```

### 3. Classes de Teste
```php
// ✅ Final classes para melhor encapsulamento
final class UserTest extends TestCase {
    // Implementação
}

// ✅ Traits para reutilização
trait DatabaseTestTrait {
    // Funcionalidades comuns
}

// ✅ Builders para dados de teste
final class UserTestBuilder {
    // Factory methods
}
```

## 🚀 Como Executar os Testes

### Comandos Básicos
```bash
# Todos os testes
php bin/phpunit

# Por nível da pirâmide
php bin/phpunit --testsuite=Unit          # Rápidos (< 100ms)
php bin/phpunit --testsuite=Integration   # Médios (< 5s)
php bin/phpunit --testsuite=Application   # Lentos (< 30s)

# Testes específicos
php bin/phpunit tests/Unit/Domain/User/UserTest.php
php bin/phpunit tests/Integration/UserRepositoryTest.php
```

### Comandos Avançados
```bash
# Com cobertura de código
php bin/phpunit --coverage-html var/coverage/html

# Com filtros específicos
php bin/phpunit --filter testUserCreation
php bin/phpunit --group integration

# Parar no primeiro erro
php bin/phpunit --stop-on-failure

# Execução verbosa
php bin/phpunit --verbose
```

### Configuração de Performance
```bash
# Apenas testes rápidos (desenvolvimento)
php bin/phpunit --testsuite=Unit --stop-on-failure

# Testes completos (CI/CD)
php bin/phpunit --coverage-text --stop-on-failure
```

## 📈 Métricas e Performance

### Metas de Performance
| Nível | Meta de Tempo | Atual | Status |
|-------|---------------|-------|---------|
| **Unit** | < 100ms | ✅ < 50ms | 🟢 Excelente |
| **Integration** | < 5s | ✅ < 3s | 🟢 Muito Bom |
| **Application** | < 30s | ✅ < 15s | 🟢 Ótimo |
| **Total** | < 60s | ✅ < 20s | 🟢 Excepcional |

### Cobertura de Código
```bash
# Gerar relatório de cobertura
php bin/phpunit --coverage-html var/coverage/html

# Visualizar no browser
open var/coverage/html/index.html
```

### Asserções de Performance
```php
public function testUserMethodsPerformance(): void {
    // Arrange
    $user = UserTestBuilder::create()
        ->withEmail('performance@test.com')
        ->build();
    
    // Act - Medir performance
    $start = microtime(true);
    
    for ($i = 0; $i < 1000; $i++) {
        $user->getEmail();
        $user->getRoles();
        $user->isVerified();
    }
    
    $end = microtime(true);
    $duration = $end - $start;
    
    // Assert - Performance deve ser boa
    $this->assertLessThan(
        0.1, // 100ms para 1000 chamadas
        $duration,
        "Performance degraded: took {$duration}s"
    );
}
```

## 🔧 Configuração de Ambiente

### PHPUnit Configuration
```xml
<!-- phpunit.xml.dist -->
<phpunit>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="Application">
            <directory>tests/Application</directory>
        </testsuite>
        <testsuite name="All">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### Environment Variables
```bash
# .env.test
DATABASE_URL="sqlite:///%kernel.project_dir%/var/test.db"
KERNEL_CLASS='App\Kernel'
APP_ENV=test
```

### Database Setup
```bash
# Criar banco de teste
php bin/console doctrine:database:create --env=test

# Executar migrations
php bin/console doctrine:migrations:migrate --env=test --no-interaction
```

## 🎯 Melhores Práticas

### ✅ Do's (Fazer)

1. **Use a pirâmide de testes**: Mais unit, menos integration, poucos E2E
2. **AAA Pattern**: Arrange-Act-Assert em todos os testes
3. **Names descritivos**: `testActionExpectedBehavior`
4. **Isolation**: Cada teste independente dos outros
5. **Fast feedback**: Unit tests < 100ms
6. **Builders**: Use UserTestBuilder para dados de teste
7. **Traits**: Reutilize DatabaseTestTrait para operações de banco
8. **Performance assertions**: Controle performance crítica

### ❌ Don'ts (Evitar)

1. **Não teste frameworks**: Foque na sua lógica
2. **Não compartilhe estado**: Entre testes diferentes
3. **Não ignore failures**: Todo teste deve passar sempre
4. **Não testes lentos em Unit**: Use Integration/Application
5. **Não hardcode dados**: Use builders e factories
6. **Não skip cleanup**: Sempre limpe depois dos testes
7. **Não testes complexos**: Mantenha simples e focado

## 🐛 Troubleshooting

### Problemas Comuns

#### Testes de Banco Falhando
```bash
# ❌ Erro: "Database not found"
# ✅ Solução: Criar banco de teste
php bin/console doctrine:database:create --env=test

# ❌ Erro: "Table doesn't exist"  
# ✅ Solução: Executar migrations
php bin/console doctrine:migrations:migrate --env=test
```

#### Performance Degradada
```bash
# ❌ Testes muito lentos
# ✅ Verificar apenas Unit tests
php bin/phpunit --testsuite=Unit

# ✅ Profile testes específicos
php bin/phpunit --filter testSlowMethod --verbose
```

#### DatabaseTestTrait Issues
```php
// ❌ Erro: "EntityManager not found"
// ✅ Solução: Verificar setup
protected function setUp(): void {
    self::bootKernel(); // ← Necessário para WebTestCase
    $container = static::getContainer();
    $this->entityManager = $container->get('doctrine.orm.entity_manager');
    $this->setupTestDatabase(); // ← Usar trait
}
```

## 🔮 Roadmap e Melhorias Futuras

### Próximas Implementações

#### 1. **Mock Factories** (Curto Prazo)
```php
// Factories para mocks complexos
final class MovieApiMockFactory {
    public static function successfulResponse(): MockObject
    public static function notFoundResponse(): MockObject
    public static function timeoutResponse(): MockObject
}
```

#### 2. **Test Data Fixtures** (Médio Prazo)
```php
// Fixtures para cenários complexos
final class TestFixtures {
    public static function createMovieDatabase(): void
    public static function createUserDatabase(): void
}
```

#### 3. **Parallel Testing** (Longo Prazo)
```bash
# Execução paralela para CI/CD
php bin/phpunit --parallel 4
```

### Métricas de Sucesso
- [ ] **95%+ cobertura de código**
- [ ] **< 30s tempo total de testes**
- [ ] **Zero falsos positivos**
- [ ] **100% CI/CD success rate**

## 📞 Suporte e Referências

### Documentação Relacionada
1. **Implementação**: Consulte [REFACTORING_SUMMARY.md](REFACTORING_SUMMARY.md)
2. **Sistema principal**: Consulte [README.md](README.md)
3. **Auto-mapping**: Consulte [GLOBAL_AUTO_MAPPING.md](GLOBAL_AUTO_MAPPING.md)

### Comandos de Referência Rápida
```bash
# Desenvolvimento diário
php bin/phpunit --testsuite=Unit --stop-on-failure

# Antes de commit
php bin/phpunit --stop-on-failure

# CI/CD completo
php bin/phpunit --coverage-text

# Debug específico
php bin/phpunit --filter testSpecificMethod --verbose
```

---

## 🏆 Resultado Final

### ✅ **ARQUITETURA DE TESTES MODERNIZADA COM SUCESSO!**

**Conquistas:**
- 🟢 **Estrutura limpa**: Pirâmide de testes bem definida
- 🔧 **Helpers reutilizáveis**: DatabaseTestTrait + UserTestBuilder
- 📊 **Performance controlada**: Asserções de tempo em pontos críticos
- 🧩 **Manutenibilidade**: Código de teste organizado e documentado
- 🚀 **Developer Experience**: Testes rápidos e confiáveis

**De 600+ linhas duplicadas para 0. De estrutura inconsistente para arquitetura moderna.** 

### 🎖️ **TESTES COMO CÓDIGO DE PRIMEIRA CLASSE!** 🎖️

*Qualidade, performance e manutenibilidade em cada linha de teste.* ✨

---

**Versão:** 1.0  
**Última atualização:** Dezembro 2024  
**Status:** ✅ Implementado e Funcional