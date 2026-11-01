<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\User\Entity\User;
use App\Tests\Helper\UserTestBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Test suite demonstrating PHP 8.4 Property Hooks in testing scenarios
 *
 * This test class showcases how property hooks enhance testing by:
 * - Providing direct property access instead of method calls
 * - Enabling virtual computed properties for test assertions
 * - Improving test readability and maintainability
 * - Demonstrating asymmetric visibility in practice
 */
final class PropertyHooksTestSuite extends TestCase
{
    public function testUserEntityWithStandardGetterMethods(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail("hooks@example.com");
        $user->setPassword("password123");
        $user->setRoles(["ROLE_ADMIN"]);
        $user->setIsVerified(true);

        // Act & Assert - Using standard getter methods
        $this->assertEquals("hooks@example.com", $user->getEmail());
        $this->assertEquals("password123", $user->getPassword());
        $this->assertTrue($user->isVerified());

        // Test roles with automatic ROLE_USER addition
        $roles = $user->getRoles();
        $this->assertContains("ROLE_USER", $roles);
        $this->assertContains("ROLE_ADMIN", $roles);
        $this->assertCount(2, $roles);
    }

    public function testUserTestBuilderPropertyHooks(): void
    {
        // Arrange & Act - Using UserTestBuilder with property hooks
        $builder = UserTestBuilder::create()
            ->withEmail("builder@example.com")
            ->withPassword("secure_password")
            ->withRole("ROLE_MODERATOR")
            ->verified();

        // Assert - Access to builder properties via hooks
        $this->assertEquals("builder@example.com", $builder->email);
        $this->assertEquals("secure_password", $builder->password);
        $this->assertTrue($builder->isVerified);
        $this->assertContains("ROLE_MODERATOR", $builder->roles);
    }

    public function testVirtualComputedPropertiesForBuilderState(): void
    {
        // Arrange
        $builder = UserTestBuilder::create();

        // Assert initial state
        $this->assertFalse($builder->hasEmail);
        $this->assertFalse($builder->hasPassword);
        $this->assertFalse($builder->isComplete);

        // Act & Assert - Add email
        $builder->withEmail("virtual@example.com");
        $this->assertTrue($builder->hasEmail);
        $this->assertFalse($builder->isComplete); // Still missing password

        // Act & Assert - Add password
        $builder->withPassword("virtual_password");
        $this->assertTrue($builder->hasPassword);
        $this->assertTrue($builder->isComplete);
    }

    public function testVirtualArrayPropertyForBuilderSummary(): void
    {
        // Arrange
        $builder = UserTestBuilder::create()
            ->withEmail("summary@example.com")
            ->withPassword("summary_password")
            ->withRoles(["ROLE_ADMIN", "ROLE_MODERATOR"])
            ->verified();

        // Act - Access virtual array property
        $summary = $builder->summary;

        // Assert - Virtual property computes summary dynamically
        $this->assertIsArray($summary);
        $this->assertEquals("summary@example.com", $summary["email"]);
        $this->assertTrue($summary["has_password"]);
        $this->assertEquals(2, $summary["roles_count"]);
        $this->assertTrue($summary["is_verified"]);
        $this->assertTrue($summary["is_complete"]);
    }

    public function testAsymmetricVisibilityDemonstration(): void
    {
        // Arrange
        $builder = UserTestBuilder::create()
            ->withEmail("asymmetric@example.com")
            ->withPassword("asymmetric_password");

        // Assert - Properties can be read publicly
        $this->assertEquals("asymmetric@example.com", $builder->email);
        $this->assertEquals("asymmetric_password", $builder->password);
        $this->assertFalse($builder->isVerified);

        // Note: Direct property assignment would fail due to private(set):
        // $builder->email = 'new@example.com'; // Fatal error
        // This demonstrates asymmetric visibility: public read, private write
    }

    public function testBuilderPerformanceWithPropertyHooks(): void
    {
        // Arrange - Create test users using builder
        $users = [];
        for ($i = 0; $i < 50; $i++) {
            $users[] = UserTestBuilder::create()
                ->withEmail("perf{$i}@example.com")
                ->withPassword("password{$i}")
                ->verified($i % 2 === 0)
                ->build();
        }

        // Act - Measure property access performance
        $start = microtime(true);

        $verifiedCount = 0;
        $emailsWithNumbers = 0;

        foreach ($users as $user) {
            if ($user->isVerified()) {
                $verifiedCount++;
            }

            if (preg_match("/\d+/", $user->getEmail())) {
                $emailsWithNumbers++;
            }
        }

        $end = microtime(true);
        $duration = $end - $start;

        // Assert
        $this->assertEquals(25, $verifiedCount); // Half should be verified
        $this->assertEquals(50, $emailsWithNumbers); // All have numbers
        $this->assertLessThan(
            0.01,
            $duration,
            "Property access should be fast",
        );
    }

    public function testComplexBuilderPatternsWithCloning(): void
    {
        // Arrange - Create base admin builder
        $adminBuilder = UserTestBuilder::create()
            ->withDefaultTestData()
            ->asAdmin()
            ->withRole("ROLE_SUPER_ADMIN");

        // Act - Clone and modify for different user type
        $moderatorBuilder = $adminBuilder
            ->clone()
            ->withEmail("moderator@example.com")
            ->asModerator();

        // Assert - Verify property hook access on different builders
        $this->assertTrue($adminBuilder->hasCustomRoles);
        $this->assertContains("ROLE_ADMIN", $adminBuilder->roles);
        $this->assertContains("ROLE_SUPER_ADMIN", $adminBuilder->roles);

        $this->assertTrue($moderatorBuilder->hasCustomRoles);
        $this->assertContains("ROLE_ADMIN", $moderatorBuilder->roles); // From clone
        $this->assertContains("ROLE_MODERATOR", $moderatorBuilder->roles);
        $this->assertEquals("moderator@example.com", $moderatorBuilder->email);
    }

    public function testBuildMultipleUsersWithIncrementalData(): void
    {
        // Arrange
        $builder = UserTestBuilder::create()
            ->withPassword("bulk_password")
            ->withRole("ROLE_USER")
            ->verified();

        // Act - Build multiple users with incremental emails
        $users = $builder->buildMany(5, "bulk{n}@example.com");

        // Assert
        $this->assertCount(5, $users);

        foreach ($users as $index => $user) {
            $expectedEmail = "bulk" . ($index + 1) . "@example.com";
            $this->assertEquals($expectedEmail, $user->getEmail());
            $this->assertEquals("bulk_password", $user->getPassword());
            $this->assertTrue($user->isVerified());
            $this->assertContains("ROLE_USER", $user->getRoles());
        }
    }

    public function testStaticFactoryMethodsCreateValidUsers(): void
    {
        // Act - Use static factory methods
        $randomUser = UserTestBuilder::random();
        $adminUser = UserTestBuilder::admin();
        $unverifiedUser = UserTestBuilder::createUnverified();

        // Assert - Verify factory-created users
        $this->assertNotEmpty($randomUser->getEmail());
        $this->assertNotEmpty($randomUser->getPassword());

        $this->assertEquals("admin@example.com", $adminUser->getEmail());
        $this->assertContains("ROLE_ADMIN", $adminUser->getRoles());
        $this->assertTrue($adminUser->isVerified());

        $this->assertEquals(
            "unverified@example.com",
            $unverifiedUser->getEmail(),
        );
        $this->assertFalse($unverifiedUser->isVerified());
    }

    public function testBuilderValidationWithIncompleteData(): void
    {
        // Arrange - Create incomplete builder
        $incompleteBuilder = UserTestBuilder::create()->withEmail(
            "incomplete@example.com",
        );

        // Assert - Virtual properties detect incomplete state
        $this->assertTrue($incompleteBuilder->hasEmail);
        $this->assertFalse($incompleteBuilder->hasPassword);
        $this->assertFalse($incompleteBuilder->isComplete);

        // Assert - Building incomplete user throws exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "UserTestBuilder requires email and password",
        );

        $incompleteBuilder->build();
    }

    public function testUserEntityMethodConsistency(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail("consistency@example.com");
        $user->setPassword("consistency_password");
        $user->setRoles(["ROLE_ADMIN"]);
        $user->setIsVerified(true);

        // Assert - Standard getter methods work correctly
        $this->assertEquals("consistency@example.com", $user->getEmail());
        $this->assertEquals("consistency_password", $user->getPassword());
        $this->assertTrue($user->isVerified());

        $roles = $user->getRoles();
        $this->assertContains("ROLE_USER", $roles);
        $this->assertContains("ROLE_ADMIN", $roles);

        // User identifier should match email
        $this->assertEquals($user->getEmail(), $user->getUserIdentifier());
    }

    public function testPropertyHooksInArrayOperations(): void
    {
        // Arrange - Create users with different verification statuses
        $users = [
            UserTestBuilder::create()
                ->withEmail("user1@test.com")
                ->withPassword("pass1")
                ->verified()
                ->build(),
            UserTestBuilder::create()
                ->withEmail("user2@test.com")
                ->withPassword("pass2")
                ->setUnverified()
                ->build(),
            UserTestBuilder::create()
                ->withEmail("user3@test.com")
                ->withPassword("pass3")
                ->verified()
                ->build(),
        ];

        // Act - Use standard methods in array operations
        $verifiedUsers = array_filter($users, fn($user) => $user->isVerified());
        $userEmails = array_map(fn($user) => $user->getEmail(), $users);

        // Assert
        $this->assertCount(2, $verifiedUsers);
        $this->assertEquals(
            ["user1@test.com", "user2@test.com", "user3@test.com"],
            $userEmails,
        );

        // Verify all users have ROLE_USER automatically
        foreach ($users as $user) {
            $this->assertContains("ROLE_USER", $user->getRoles());
        }
    }

    public function testBuilderResetFunctionality(): void
    {
        // Arrange
        $builder = UserTestBuilder::create()
            ->withEmail("reset@example.com")
            ->withPassword("password")
            ->withRole("ROLE_ADMIN")
            ->verified();

        // Verify initial state
        $this->assertTrue($builder->isComplete);
        $this->assertTrue($builder->hasCustomRoles);

        // Act - Reset builder
        $builder->reset();

        // Assert - Builder is back to initial state
        $this->assertFalse($builder->hasEmail);
        $this->assertFalse($builder->hasPassword);
        $this->assertFalse($builder->hasCustomRoles);
        $this->assertFalse($builder->isVerified);
        $this->assertFalse($builder->isComplete);
    }

    public function testBuilderCloningBehavior(): void
    {
        // Arrange
        $originalBuilder = UserTestBuilder::create()
            ->withEmail("original@example.com")
            ->withPassword("password")
            ->withRoles(["ROLE_ADMIN", "ROLE_MODERATOR"])
            ->verified();

        // Act - Clone builder
        $clonedBuilder = $originalBuilder->clone();

        // Assert - Clone has same data
        $this->assertEquals($originalBuilder->email, $clonedBuilder->email);
        $this->assertEquals(
            $originalBuilder->password,
            $clonedBuilder->password,
        );
        $this->assertEquals($originalBuilder->roles, $clonedBuilder->roles);
        $this->assertEquals(
            $originalBuilder->isVerified,
            $clonedBuilder->isVerified,
        );

        // Act - Modify clone
        $clonedBuilder->withEmail("clone@example.com");

        // Assert - Original unchanged, clone modified
        $this->assertEquals("original@example.com", $originalBuilder->email);
        $this->assertEquals("clone@example.com", $clonedBuilder->email);
    }
}
