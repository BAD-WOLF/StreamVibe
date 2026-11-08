<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Domain\Exception\UserAlreadyExistsException;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Role;
use App\Infrastructure\Persistence\Repository\UserRepository;
use App\Tests\Helper\UserTestBuilder;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration tests for UserRepository
 *
 * Tests the UserRepository implementation against a real database
 * following the testing pyramid principles.
 */
final class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel(["environment" => "test"]);
        $container = $kernel->getContainer();

        $this->entityManager = $container->get("doctrine.orm.entity_manager");

        // UserRepository é registrado como serviço privado pelo Symfony
        // (ServiceEntityRepository). O container de teste inlina/remove
        // serviços privados não referenciados diretamente, então
        // $container->get(UserRepository::class) quebra com
        // ServiceNotFoundException. O EntityManager usa o localizador de
        // repositórios interno do Doctrine, que não depende de
        // visibilidade pública no container.
        $this->userRepository = $this->entityManager->getRepository(User::class);

        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        $this->entityManager->close();
        parent::tearDown();
    }

    private function cleanDatabase(): void
    {
        $this->entityManager
            ->createQuery("DELETE FROM App\Domain\User\Entity\User u")
            ->execute();
        $this->entityManager->clear();
    }

    public function testRepositoryIsInstanceOfCorrectInterface(): void
    {
        $this->assertInstanceOf(UserRepository::class, $this->userRepository);
        $this->assertInstanceOf(
            UserRepositoryInterface::class,
            $this->userRepository,
        );
    }

    public function testSaveUserPersistsToDatabase(): void
    {
        // Arrange
        $user = UserTestBuilder::create()
                               ->withEmail("test@example.com")
                               ->withPassword("hashed_password")
                               ->build();

        // Act
        $this->userRepository->save($user);

        // Assert
        $this->assertNotNull($user->getId());
        $this->assertEquals("test@example.com", $user->getEmail());

        $foundUser = $this->userRepository->findById($user->getId());
        $this->assertNotNull($foundUser);
        $this->assertEquals("test@example.com", $foundUser->getEmail());
    }

    public function testFindByIdReturnsCorrectUser(): void
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
        $this->assertNotNull($foundUser);
        $this->assertEquals($userId, $foundUser->getId());
        $this->assertEquals("findbyid@example.com", $foundUser->getEmail());
    }

    public function testFindByIdReturnsNullForNonexistentUser(): void
    {
        // Act
        $foundUser = $this->userRepository->findById(999);

        // Assert
        $this->assertNull($foundUser);
    }

    public function testFindByEmailReturnsCorrectUser(): void
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
        $this->assertEquals($user->getId(), $foundUser->getId());
    }

    public function testFindByEmailReturnsNullForNonexistentEmail(): void
    {
        // Act
        $foundUser = $this->userRepository->findByEmail(
            "nonexistent@example.com",
        );

        // Assert
        $this->assertNull($foundUser);
    }

    public function testFindAllReturnsAllUsers(): void
    {
        // Arrange
        $users = [
            UserTestBuilder::create()
                           ->withEmail("user1@example.com")
                           ->withPassword("hash1")
                           ->build(),
            UserTestBuilder::create()
                           ->withEmail("user2@example.com")
                           ->withPassword("hash2")
                           ->build(),
            UserTestBuilder::create()
                           ->withEmail("user3@example.com")
                           ->withPassword("hash3")
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

    public function testFindAllReturnsEmptyArrayWhenNoUsers(): void
    {
        // Act
        $allUsers = $this->userRepository->findAll();

        // Assert
        $this->assertIsArray($allUsers);
        $this->assertEmpty($allUsers);
    }

    public function testRemoveUserDeletesFromDatabase(): void
    {
        // Arrange
        $user = UserTestBuilder::create()
                               ->withEmail("remove@example.com")
                               ->withPassword("password_hash")
                               ->build();

        $this->userRepository->save($user);
        $userId = $user->getId();

        // Verify user exists
        $this->assertNotNull($this->userRepository->findById($userId));

        // Act
        $this->userRepository->remove($user);

        // Assert
        $this->assertNull($this->userRepository->findById($userId));
    }

    public function testUpgradePasswordUpdatesUserPassword(): void
    {
        // Arrange
        $user = UserTestBuilder::create()
                               ->withEmail("upgrade@example.com")
                               ->withPassword("old_password_hash")
                               ->build();

        $this->userRepository->save($user);
        $newHashedPassword = "new_password_hash";

        // Act
        $this->userRepository->upgradePassword($user, $newHashedPassword);

        // Assert
        $updatedUser = $this->userRepository->findById($user->getId());
        $this->assertEquals($newHashedPassword, $updatedUser->getPassword());
    }

    public function testUserPersistenceWithAllProperties(): void
    {
        // Arrange
        $user = UserTestBuilder::create()
                               ->withEmail("complete@example.com")
                               ->withPassword("secure_password_hash")
                               ->withRoles(["ROLE_ADMIN", "ROLE_MODERATOR"])
                               ->verified()
                               ->build();

        // Act
        $this->userRepository->save($user);

        // Assert
        $foundUser = $this->userRepository->findById($user->getId());

        $this->assertEquals("complete@example.com", $foundUser->getEmail());
        $this->assertEquals("secure_password_hash", $foundUser->getPassword());
        $this->assertTrue($foundUser->isVerified());

        $roles = $foundUser->getRoles();
        $this->assertContains("ROLE_USER", $roles);
        $this->assertContains("ROLE_ADMIN", $roles);
        $this->assertContains("ROLE_MODERATOR", $roles);
    }

    /**
     * NOTA (out/2026): o UserRepository::save() passou a traduzir a
     * violação do índice único do email (UniqueConstraintViolationException
     * do DBAL) para UserAlreadyExistsException do Domain — é o que acontece
     * quando dois cadastros com o mesmo email chegam ao mesmo tempo e os
     * dois passam pela checagem do UseCase. Antes a exceção do DBAL subia
     * até o ExceptionListener e virava 500; agora é 409. A exceção original
     * continua como "previous".
     */
    public function testEmailUniqueConstraintViolation(): void
    {
        // Arrange
        $email = "duplicate@example.com";

        $user1 = UserTestBuilder::create()
                                ->withEmail($email)
                                ->withPassword("password1")
                                ->build();
        $user2 = UserTestBuilder::create()
                                ->withEmail($email)
                                ->withPassword("password2")
                                ->build();

        // Act
        $this->userRepository->save($user1);

        try {
            $this->userRepository->save($user2);
            $this->fail("Saving a second user with the same email should fail");
        } catch (UserAlreadyExistsException $exception) {
            // Assert
            $this->assertSame(409, $exception->getCode());
            $this->assertInstanceOf(
                UniqueConstraintViolationException::class,
                $exception->getPrevious(),
            );
        }
    }

    public function testUserEntityRelationshipsWorkCorrectly(): void
    {
        // Arrange
        $user = UserTestBuilder::create()
                               ->withEmail("relationships@example.com")
                               ->withPassword("password_hash")
                               ->setUnverified()
                               ->build();

        // Act
        $this->userRepository->save($user);

        // Clear entity manager to force fresh fetch
        $this->entityManager->clear();

        $foundUser = $this->userRepository->findByEmail(
            "relationships@example.com",
        );

        // Assert
        $this->assertNotNull($foundUser);
        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals(
            "relationships@example.com",
            $foundUser->getEmail(),
        );
        $this->assertFalse($foundUser->isVerified());
    }

    public function testFindMultipleUsersWithDifferentVerificationStatus(): void
    {
        // Arrange
        $verifiedUser = UserTestBuilder::create()
                                       ->withEmail("verified@example.com")
                                       ->withPassword("password")
                                       ->verified()
                                       ->build();

        $unverifiedUser = UserTestBuilder::create()
                                         ->withEmail("unverified@example.com")
                                         ->withPassword("password")
                                         ->setUnverified()
                                         ->build();

        $this->userRepository->save($verifiedUser);
        $this->userRepository->save($unverifiedUser);

        // Act
        $allUsers = $this->userRepository->findAll();

        // Assert
        $this->assertCount(2, $allUsers);

        $verifiedCount = 0;
        $unverifiedCount = 0;

        foreach ($allUsers as $user) {
            if ($user->isVerified()) {
                $verifiedCount++;
            } else {
                $unverifiedCount++;
            }
        }

        $this->assertEquals(1, $verifiedCount);
        $this->assertEquals(1, $unverifiedCount);
    }

    public function testRepositoryHandlesLargeDataSet(): void
    {
        // Arrange - Create multiple users
        $users = [];
        for ($i = 1; $i <= 20; $i++) {
            $users[] = UserTestBuilder::create()
                                      ->withEmail("user{$i}@example.com")
                                      ->withPassword("password{$i}")
                                      ->verified($i % 2 === 0)
                                      ->build();
        }

        // Act - Save all users
        foreach ($users as $user) {
            $this->userRepository->save($user);
        }

        // Assert
        $allUsers = $this->userRepository->findAll();
        $this->assertCount(20, $allUsers);

        // Verify email uniqueness
        $emails = array_map(fn($user) => $user->getEmail(), $allUsers);
        $this->assertCount(20, array_unique($emails));
    }

    // ------------------------------------------------------------------
    // NOTA (nov/2026): a lista da administração de contas (SQL nativo, com o
    // filtro de papel dentro da coluna roles, em JSON).
    // ------------------------------------------------------------------

    public function testFindPageFiltersNewestFirst(): void
    {
        $plain = $this->saveUser("plain@example.com", [], verified: true);
        $this->saveUser("legacy_1%@example.com", ["ROLE_USER"], verified: true);
        $this->saveUser("dev@example.com", ["ROLE_DEV"], verified: false);
        $this->saveUser("admin@example.com", ["ROLE_ADMIN", "ROLE_DEV"], verified: true);
        $blockedOwner = $this->saveUser("owner@example.com", ["ROLE_OWNER"], verified: true, blocked: true);
        $this->entityManager->clear();

        $emails = fn(array $page): array => array_map(static fn(User $user): string => (string) $user->getEmail(), $page["users"]);

        $all = $this->userRepository->findPage(1, 20, null, null, null, null);
        $this->assertSame(["owner@example.com", "admin@example.com", "dev@example.com", "legacy_1%@example.com", "plain@example.com"], $emails($all));
        $this->assertSame(5, $all["total"]);

        $this->assertSame(["legacy_1%@example.com", "plain@example.com"], $emails($this->userRepository->findPage(1, 20, null, Role::User, null, null)));
        $this->assertSame(["dev@example.com"], $emails($this->userRepository->findPage(1, 20, null, Role::Dev, null, null)));
        $this->assertSame(["admin@example.com"], $emails($this->userRepository->findPage(1, 20, null, Role::Admin, null, null)));
        $this->assertSame(["dev@example.com"], $emails($this->userRepository->findPage(1, 20, null, null, false, null)));
        $this->assertSame(["owner@example.com"], $emails($this->userRepository->findPage(1, 20, null, null, null, true)));
        $this->assertSame(["legacy_1%@example.com"], $emails($this->userRepository->findPage(1, 20, "_1%", null, null, null)), "% and _ are text");
        $this->assertSame(["plain@example.com"], $emails($this->userRepository->findPage(1, 20, (string) $plain->getId(), null, null, null)));
        $this->assertSame(5, $this->userRepository->findPage(1, 20, "EXAMPLE.COM", null, null, null)["total"]);
        $this->assertSame(0, $this->userRepository->findPage(1, 20, "99999999999999999999", null, null, null)["total"]);

        $page = $this->userRepository->findPage(2, 2, null, null, null, null);
        $this->assertSame(["dev@example.com", "legacy_1%@example.com"], $emails($page));
        $this->assertSame(5, $page["total"]);

        $found = $this->userRepository->findPage(1, 1, null, Role::Owner, null, null)["users"][0];
        $this->assertSame([$blockedOwner->getId(), true, "test"], [$found->getId(), $found->isBlocked(), $found->getBlockedReason()]);
    }

    public function testCountActiveWithRoleLeavesBlockedAccountsOut(): void
    {
        $this->saveUser("owner1@example.com", ["ROLE_OWNER"], verified: true);
        $this->saveUser("owner2@example.com", ["ROLE_OWNER"], verified: true, blocked: true);
        $this->saveUser("admin@example.com", ["ROLE_ADMIN"], verified: true);

        $this->assertSame(1, $this->userRepository->countActiveWithRole(Role::Owner));
        $this->assertSame(1, $this->userRepository->countActiveWithRole(Role::Admin));
        $this->assertSame(0, $this->userRepository->countActiveWithRole(Role::Dev));
    }

    /**
     * @param list<string> $roles
     */
    private function saveUser(string $email, array $roles, bool $verified, bool $blocked = false): User
    {
        $user = UserTestBuilder::create()
            ->withEmail($email)
            ->withPassword("hashed_password_123")
            ->withRoles($roles)
            ->verified($verified)
            ->build();

        if ($blocked) {
            $user->block("test", new \DateTimeImmutable());
        }

        $this->userRepository->save($user);

        return $user;
    }
}
