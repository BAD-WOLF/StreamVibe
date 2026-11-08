<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Admin\UseCase;

use App\Application\Admin\DTO\User\Role\Request\ChangeUserRoleRequest;
use App\Application\Admin\UseCase\User\Role\ChangeUserRoleUseCase;
use App\Domain\Exception\BusinessLogicException;
use App\Domain\Exception\PermissionDeniedException;
use App\Domain\Exception\UserValidationException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Service\UserAdministrationPolicy;
use App\Domain\User\ValueObject\Role;
use App\Tests\Unit\Application\Admin\AdminTestCase;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * ChangeUserRoleUseCase: pela API, com a regra de quem administra quem; pelo
 * console (actorId null), sem ela; nos dois, a do último owner.
 *
 * NOTA (nov/2026): criado com a hierarquia de papéis.
 */
final class ChangeUserRoleUseCaseTest extends AdminTestCase
{
    public function testAnAdminPromotesAUserToDev(): void
    {
        $admin = $this->user(1, Role::Admin);
        $user = $this->user(2, Role::User);

        $response = $this->useCase($this->users($admin, $user))
            ->execute(new ChangeUserRoleRequest(userId: 2, actorId: 1, role: 'ROLE_DEV'));

        self::assertSame(2, $response->userId);
        self::assertSame('ROLE_DEV', $response->role);
        self::assertSame('ROLE_USER', $response->previousRole);
        self::assertSame('User role changed successfully', $response->message);
        self::assertSame(Role::Dev, $user->getRole());

        // a entrada vai junto com a gravação da conta (mesma transação)
        self::assertSame(['audit:role_changed:no-flush', 'save:2'], $this->events);
        $entry = $this->onlyEntry();
        self::assertSame([1, 'user1@example.com', 2, 'user2@example.com'], [$entry->actorId, $entry->actorEmail, $entry->targetUserId, $entry->targetEmail]);
        self::assertSame(['role' => 'ROLE_DEV', 'previous_role' => 'ROLE_USER'], $entry->details);
    }

    public function testAnAdminCannotGiveItsOwnRole(): void
    {
        try {
            $this->useCase($this->users($this->user(1, Role::Admin), $this->user(2, Role::User)))
                ->execute(new ChangeUserRoleRequest(userId: 2, actorId: 1, role: 'ROLE_ADMIN'));
            self::fail('an admin should not give ROLE_ADMIN');
        } catch (PermissionDeniedException $exception) {
            self::assertSame('role_above_yours', $exception->getContext()['rule']);
        }

        self::assertSame([], $this->events);
    }

    public function testAnAdminCannotChangeAnotherAdmin(): void
    {
        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessage('Only a role above ROLE_ADMIN can manage this account');

        $this->useCase($this->users($this->user(1, Role::Admin), $this->user(2, Role::Admin)))
            ->execute(new ChangeUserRoleRequest(userId: 2, actorId: 1, role: 'ROLE_USER'));
    }

    public function testNobodyChangesTheirOwnRole(): void
    {
        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessage('You cannot do this to your own account');

        $this->useCase($this->users($this->user(1, Role::Owner)))
            ->execute(new ChangeUserRoleRequest(userId: 1, actorId: 1, role: 'ROLE_USER'));
    }

    public function testAnOwnerGivesAnyRole(): void
    {
        $this->activeOwners = 1;

        $response = $this->useCase($this->users($this->user(1, Role::Owner), $this->user(2, Role::Admin)))
            ->execute(new ChangeUserRoleRequest(userId: 2, actorId: 1, role: 'ROLE_OWNER'));

        self::assertSame(['ROLE_OWNER', 'ROLE_ADMIN'], [$response->role, $response->previousRole]);
    }

    public function testTheSameRoleChangesNothing(): void
    {
        try {
            $this->useCase($this->users($this->user(1, Role::Owner), $this->user(2, Role::Dev)))
                ->execute(new ChangeUserRoleRequest(userId: 2, actorId: 1, role: 'ROLE_DEV'));
            self::fail('the same role should be refused');
        } catch (BusinessLogicException $exception) {
            self::assertSame(409, $exception->getCode());
            self::assertSame('This account already has this role', $exception->getMessage());
        }

        self::assertSame([], $this->events);
    }

    /**
     * O console dá o primeiro owner: sem quem administra quem, e a entrada
     * fica sem actor.
     */
    public function testTheConsoleGivesTheFirstOwner(): void
    {
        $user = $this->user(5, Role::User);

        $response = $this->useCase($this->users($user))
            ->execute(new ChangeUserRoleRequest(userId: 5, actorId: null, role: 'ROLE_OWNER'));

        self::assertSame('ROLE_OWNER', $response->role);
        self::assertSame(Role::Owner, $user->getRole());
        $entry = $this->onlyEntry();
        self::assertNull($entry->actorId);
        self::assertNull($entry->actorEmail);
    }

    public function testTheLastActiveOwnerKeepsTheRole(): void
    {
        $this->activeOwners = 1;

        try {
            $this->useCase($this->users($this->user(5, Role::Owner)))
                ->execute(new ChangeUserRoleRequest(userId: 5, actorId: null, role: 'ROLE_ADMIN'));
            self::fail('the last active owner should keep the role');
        } catch (BusinessLogicException $exception) {
            self::assertSame('last_owner', $exception->getContext()['rule']);
        }

        self::assertSame([], $this->events);
    }

    public function testAnOwnerIsDemotedWhenAnotherOneStays(): void
    {
        $this->activeOwners = 2;

        $response = $this->useCase($this->users($this->user(1, Role::Owner), $this->user(2, Role::Owner)))
            ->execute(new ChangeUserRoleRequest(userId: 2, actorId: 1, role: 'ROLE_ADMIN'));

        self::assertSame('ROLE_OWNER', $response->previousRole);
    }

    public function testAnUnknownRoleIsRejected(): void
    {
        try {
            $this->useCase($this->users($this->user(1, Role::Owner), $this->user(2, Role::User)))
                ->execute(new ChangeUserRoleRequest(userId: 2, actorId: 1, role: 'ROLE_ROOT'));
            self::fail('an unknown role should be rejected');
        } catch (UserValidationException $exception) {
            self::assertSame(400, $exception->getCode());
            self::assertSame(['Role: Role must be one of: ROLE_USER, ROLE_DEV, ROLE_ADMIN, ROLE_OWNER'], $exception->getErrors());
        }
    }

    private function useCase(UserRepositoryInterface $users): ChangeUserRoleUseCase
    {
        return new ChangeUserRoleUseCase(
            accounts: $this->accounts($users),
            userRepository: $users,
            policy: new UserAdministrationPolicy(),
            auditTrail: $this->auditTrail(),
            requestValidator: $this->validator(),
            translator: new IdentityTranslator(),
        );
    }
}
