<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Persistence\Repository\UserRepository;
use App\Tests\Helper\DatabaseTestTrait;
use App\Tests\Helper\UserTestBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Simplified integration tests using pure Doctrine without Symfony container
 *
 * This approach provides stable testing by:
 * - Using direct Doctrine connection without Symfony WebTestCase
 * - Avoiding service container issues
 * - Creating isolated database environment for each test
 * - Focusing on core repository functionality
 *
 * NOTA 1: setupDoctrineEntityManager() habilita os "native lazy objects" do
 * PHP 8.4 via Configuration::enableNativeLazyObjects(true). Sem isso,
 * Doctrine\ORM\Proxy\ProxyFactory cai no modo legado baseado em
 * Symfony\Component\VarExporter\ProxyHelper::generateLazyGhost() — método
 * removido no symfony/var-exporter 8.x (reescrito em cima dos lazy objects
 * nativos do próprio PHP 8.4). Isso fazia todo teste desta classe ser
 * pulado com "Symfony LazyGhost is not available...", e também disparava
 * uma PHPUnit Deprecation avisando que não habilitar o modo nativo ficaria
 * impossível no Doctrine ORM 4.0.
 *
 * NOTA 2: UserRepository estende ServiceEntityRepository, cujo construtor
 * exige um Doctrine\Persistence\ManagerRegistry (não um EntityManager
 * direto) — internamente ele chama
 * $registry->getManagerForClass(User::class) para resolver o manager.
 * UserRepository também exige um TranslatorInterface (usado em mensagens
 * de erro do upgradePassword). Como este teste monta o Doctrine na unha,
 * sem o container do Symfony, fornecemos dublês mínimos das duas
 * dependências em vez de alterar a classe de produção (que está correta —
 * é o padrão de repositório do Symfony).
 */
final class SimpleRepositoryTest extends TestCase
{
    use DatabaseTestTrait;

    private UserRepositoryInterface $userRepository;
    private Connection $connection;

    protected function setUp(): void
    {
        if (!$this->isDatabaseAvailable()) {
            $this->markTestSkipped(
                "Database is not available for integration tests. " .
                    "Ensure PostgreSQL container is running: docker compose up -d",
            );
        }

        try {
            $this->setupDoctrineEntityManager();
            $this->userRepository = new UserRepository(
                $this->makeManagerRegistry($this->entityManager),
                $this->makeTranslator(),
            );
            $this->setupTestDatabase();
        } catch (\Exception $e) {
            $this->markTestSkipped(
                "Could not set up integration test environment: " .
                    $e->getMessage(),
            );
        }
    }

    protected function tearDown(): void
    {
        $this->teardownTestDatabase();
    }

    public function testRepositoryCanSaveUser(): void
    {
        // Arrange
        $user = UserTestBuilder::create()
            ->withEmail("save@example.com")
            ->withPassword("secure_password")
            ->build();

        // Act
        $this->userRepository->save($user);

        // Assert
        $this->assertNotNull($user->getId());
        $savedUser = $this->userRepository->findById($user->getId());
        $this->assertNotNull($savedUser);
        $this->assertEquals("save@example.com", $savedUser->getEmail());
    }

    public function testRepositoryCanFindUserById(): void
    {
        // Arrange
        $user = UserTestBuilder::create()
            ->withEmail("findbyid@example.com")
            ->withPassword("password_hash")
            ->build();

        $this->userRepository->save($user);
        $userId = $user->getId();

        // Act
        $foundUser = $this->userRepository->findById($userId);

        // Assert
        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($userId, $foundUser->getId());
        $this->assertEquals("findbyid@example.com", $foundUser->getEmail());
    }

    public function testRepositoryCanFindUserByEmail(): void
    {
        // Arrange
        $email = "findbyemail@example.com";
        $user = UserTestBuilder::create()
            ->withEmail($email)
            ->withPassword("password_hash")
            ->build();

        $this->userRepository->save($user);

        // Act
        $foundUser = $this->userRepository->findByEmail($email);

        // Assert
        $this->assertNotNull($foundUser);
        $this->assertEquals($email, $foundUser->getEmail());
    }

    public function testRepositoryReturnsNullForNonexistentUser(): void
    {
        // Act & Assert
        $this->assertNull($this->userRepository->findById(999));
        $this->assertNull(
            $this->userRepository->findByEmail("nonexistent@example.com"),
        );
    }

    public function testRepositoryCanRemoveUser(): void
    {
        // Arrange
        $user = UserTestBuilder::create()
            ->withEmail("remove@example.com")
            ->withPassword("password_hash")
            ->build();

        $this->userRepository->save($user);
        $userId = $user->getId();

        // Act
        $this->userRepository->remove($user);

        // Assert
        $this->assertNull($this->userRepository->findById($userId));
    }

    public function testRepositoryHandlesUserRolesCorrectly(): void
    {
        // Arrange
        $user = UserTestBuilder::create()
            ->withEmail("roles@example.com")
            ->withPassword("password_hash")
            ->withRoles(["ROLE_ADMIN", "ROLE_MODERATOR"])
            ->build();

        // Act
        $this->userRepository->save($user);

        // Assert
        $savedUser = $this->userRepository->findById($user->getId());
        $roles = $savedUser->getRoles();

        $this->assertContains("ROLE_USER", $roles);
        $this->assertContains("ROLE_ADMIN", $roles);
        $this->assertContains("ROLE_MODERATOR", $roles);
    }

    public function testRepositoryCanFindAllUsers(): void
    {
        // Arrange
        $users = [
            UserTestBuilder::create()
                ->withEmail("user1@example.com")
                ->withPassword("pass1")
                ->build(),
            UserTestBuilder::create()
                ->withEmail("user2@example.com")
                ->withPassword("pass2")
                ->build(),
            UserTestBuilder::create()
                ->withEmail("user3@example.com")
                ->withPassword("pass3")
                ->build(),
        ];

        foreach ($users as $user) {
            $this->userRepository->save($user);
        }

        // Act
        $allUsers = $this->userRepository->findAll();

        // Assert
        $this->assertCount(3, $allUsers);
        $emails = array_map(fn($user) => $user->getEmail(), $allUsers);
        $this->assertContains("user1@example.com", $emails);
        $this->assertContains("user2@example.com", $emails);
        $this->assertContains("user3@example.com", $emails);
    }

    public function testRepositoryCanUpgradePassword(): void
    {
        // Arrange
        $user = UserTestBuilder::create()
            ->withEmail("upgrade@example.com")
            ->withPassword("old_password")
            ->build();

        $this->userRepository->save($user);
        $newPassword = "new_secure_password";

        // Act
        $this->userRepository->upgradePassword($user, $newPassword);

        // Assert
        $updatedUser = $this->userRepository->findById($user->getId());
        $this->assertEquals($newPassword, $updatedUser->getPassword());
    }

    public function testRepositoryHandlesEmptyResults(): void
    {
        // Act
        $allUsers = $this->userRepository->findAll();

        // Assert
        $this->assertIsArray($allUsers);
        $this->assertEmpty($allUsers);
    }

    private function setupDoctrineEntityManager(): void
    {
        $config = ORMSetup::createConfiguration(isDevMode: true);
        $driver = new AttributeDriver([__DIR__ . "/../../src/Domain"]);
        $config->setMetadataDriverImpl($driver);

        // Habilita os lazy objects nativos do PHP 8.4 — ver NOTA 1 da classe.
        $config->enableNativeLazyObjects(true);

        $connectionParams = [
            "driver" => "pdo_pgsql",
            "host" => $_ENV["DATABASE_HOST"] ?? "database",
            "port" => (int) ($_ENV["DATABASE_PORT"] ?? 5432),
            "dbname" => $_ENV["DATABASE_NAME"] ?? "app_test",
            "user" => $_ENV["DATABASE_USER"] ?? "app",
            "password" => $_ENV["DATABASE_PASSWORD"] ?? "Gk7xRz92wM",
        ];

        $this->connection = DriverManager::getConnection($connectionParams);
        $this->entityManager = new EntityManager($this->connection, $config);
    }

    /**
     * Dublê mínimo de ManagerRegistry — ver NOTA 2 da classe. Só
     * getManagerForClass()/getManager()/getManagers() precisam funcionar
     * de verdade, pois é só isso que ServiceEntityRepository::__construct()
     * usa. O resto da interface é implementado de forma inofensiva só para
     * satisfazer o contrato.
     */
    private function makeManagerRegistry(EntityManager $entityManager): ManagerRegistry
    {
        return new class ($entityManager) implements ManagerRegistry {
            public function __construct(private readonly EntityManager $entityManager)
            {
            }

            public function getDefaultConnectionName(): string
            {
                return "default";
            }

            public function getConnection(?string $name = null): object
            {
                return $this->entityManager->getConnection();
            }

            public function getConnections(): array
            {
                return ["default" => $this->entityManager->getConnection()];
            }

            public function getConnectionNames(): array
            {
                return ["default" => "default"];
            }

            public function getDefaultManagerName(): string
            {
                return "default";
            }

            public function getManager(?string $name = null): ObjectManager
            {
                return $this->entityManager;
            }

            public function getManagers(): array
            {
                return ["default" => $this->entityManager];
            }

            public function resetManager(?string $name = null): ObjectManager
            {
                return $this->entityManager;
            }

            public function getAliasNamespace(string $alias): string
            {
                throw new LogicException("Not implemented in test double.");
            }

            public function getManagerNames(): array
            {
                return ["default" => "default"];
            }

            public function getRepository(
                string $persistentObject,
                ?string $persistentManagerName = null,
            ): ObjectRepository {
                return $this->entityManager->getRepository($persistentObject);
            }

            public function getManagerForClass(string $class): ?ObjectManager
            {
                return $this->entityManager;
            }
        };
    }

    /**
     * Dublê mínimo de TranslatorInterface — ver NOTA 2 da classe. Devolve
     * o próprio id da tradução, suficiente para os testes exercitarem
     * upgradePassword() sem precisar do componente de tradução real.
     */
    private function makeTranslator(): TranslatorInterface
    {
        return new class implements TranslatorInterface {
            public function trans(
                string $id,
                array $parameters = [],
                ?string $domain = null,
                ?string $locale = null,
            ): string {
                return strtr($id, $parameters);
            }

            public function getLocale(): string
            {
                return "en";
            }
        };
    }

    private function isDatabaseAvailable(): bool
    {
        try {
            $connectionParams = [
                "driver" => "pdo_pgsql",
                "host" => $_ENV["DATABASE_HOST"] ?? "database",
                "port" => (int) ($_ENV["DATABASE_PORT"] ?? 5432),
                "dbname" => $_ENV["DATABASE_NAME"] ?? "app_test",
                "user" => $_ENV["DATABASE_USER"] ?? "app",
                "password" => $_ENV["DATABASE_PASSWORD"] ?? "Gk7xRz92wM",
            ];

            $connection = DriverManager::getConnection($connectionParams);
            $connection->executeQuery('SELECT 1');
            $connection->close();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

