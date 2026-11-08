<?php

declare(strict_types=1);

namespace App\Tests\Application\Console;

use App\Domain\Audit\Entity\AuditLogEntry;
use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\Role;
use App\Tests\Helper\DatabaseTestTrait;
use App\Tests\Helper\UserTestBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * app:user:role: o console dá o primeiro owner (e qualquer papel), com a
 * regra do último owner e a entrada no log sem actor.
 *
 * NOTA (nov/2026): criado com a hierarquia de papéis.
 */
final class ChangeUserRoleCommandTest extends KernelTestCase
{
    use DatabaseTestTrait;

    private CommandTester $command;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->setupTestDatabase();

        $this->command = new CommandTester(new Application(self::$kernel)->find('app:user:role'));
    }

    protected function tearDown(): void
    {
        $this->teardownTestDatabase();
        parent::tearDown();
    }

    public function testGivesTheFirstOwner(): void
    {
        $user = $this->makeUser('ana@example.com', verified: true);

        $status = $this->command->execute(['email' => 'ana@example.com', 'role' => 'owner']);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertStringContainsString('ana@example.com: ROLE_USER -> ROLE_OWNER', $this->command->getDisplay());
        $this->assertStringNotContainsString('has not confirmed', $this->command->getDisplay());

        $this->entityManager->clear();
        $stored = $this->entityManager->find(User::class, $user->getId());
        $this->assertInstanceOf(User::class, $stored);
        $this->assertSame(Role::Owner, $stored->getRole());

        $entries = $this->entityManager->getRepository(AuditLogEntry::class)->findAll();
        $this->assertCount(1, $entries);
        $this->assertSame('role_changed', $entries[0]->action->value);
        $this->assertNull($entries[0]->actorId, 'the console has no actor');
        $this->assertSame(['role' => 'ROLE_OWNER', 'previous_role' => 'ROLE_USER'], $entries[0]->details);
    }

    public function testWarnsWhenTheEmailIsNotConfirmed(): void
    {
        $this->makeUser('pending@example.com', verified: false);

        $status = $this->command->execute(['email' => 'pending@example.com', 'role' => 'ROLE_ADMIN']);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertStringContainsString('has not confirmed its email yet', $this->command->getDisplay());
    }

    public function testRefusesWhatTheUseCaseRefuses(): void
    {
        $this->makeUser('owner@example.com', verified: true, role: Role::Owner);

        $this->assertSame(Command::FAILURE, $this->command->execute(['email' => 'owner@example.com', 'role' => 'admin']));
        $this->assertStringContainsString('The last owner cannot lose the owner role', $this->command->getDisplay());

        $this->assertSame(Command::FAILURE, $this->command->execute(['email' => 'owner@example.com', 'role' => 'owner']));
        $this->assertStringContainsString('This account already has this role', $this->command->getDisplay());

        $this->assertSame(Command::FAILURE, $this->command->execute(['email' => 'owner@example.com', 'role' => 'root']));
        $this->assertStringContainsString('Role must be one of', $this->command->getDisplay());

        $this->assertSame(Command::FAILURE, $this->command->execute(['email' => 'nobody@example.com', 'role' => 'owner']));
        $this->assertStringContainsString('There is no account with the email "nobody@example.com"', $this->command->getDisplay());

        $this->assertSame([], $this->entityManager->getRepository(AuditLogEntry::class)->findAll());
    }

    private function makeUser(string $email, bool $verified, Role $role = Role::User): User
    {
        $user = UserTestBuilder::create()
            ->withEmail($email)
            ->withPassword('hashed_password_123')
            ->verified($verified)
            ->build();
        $user->assignRole($role);

        /** @var EntityManagerInterface $em */
        $em = $this->entityManager;
        $em->persist($user);
        $em->flush();

        return $user;
    }
}
