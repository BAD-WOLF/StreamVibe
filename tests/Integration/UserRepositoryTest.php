<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
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

        // Act & Assert
        $this->userRepository->save($user1);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->userRepository->save($user2);
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
}