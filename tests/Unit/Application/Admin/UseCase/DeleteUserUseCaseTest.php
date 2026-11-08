<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Admin\UseCase;

use App\Application\Admin\DTO\User\Delete\Request\DeleteUserRequest;
use App\Application\Admin\UseCase\User\Delete\DeleteUserUseCase;
use App\Domain\Exception\BusinessLogicException;
use App\Domain\Exception\PermissionDeniedException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Service\UserAdministrationPolicy;
use App\Domain\User\ValueObject\Role;
use App\Tests\Unit\Application\Admin\AdminTestCase;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * DeleteUserUseCase: as sessões saem antes da conta (o refresh token guarda
 * o email: uma conta nova com o mesmo email as herdaria), a entrada do log
 * vai junto com a exclusão, e o id da resposta é o de antes (o Doctrine o
 * zera ao excluir).
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class DeleteUserUseCaseTest extends AdminTestCase
{
    public function testDeletesTheAccountAfterEndingItsSessions(): void
    {
        $user = $this->user(2, Role::Dev);

        $response = $this->useCase($this->users($this->user(1, Role::Admin), $user), activeSessions: 2)
            ->execute(new DeleteUserRequest(userId: 2, actorId: 1));

        self::assertSame(2, $response->userId);
        self::assertSame(2, $response->revokedSessions);
        self::assertSame('User deleted successfully', $response->message);
        self::assertNull($user->getId(), 'the repository double resets the id like Doctrine');
        self::assertSame(['revoke:2', 'audit:user_deleted:no-flush', 'remove:2'], $this->events);

        $entry = $this->onlyEntry();
        self::assertSame([2, 'user2@example.com'], [$entry->targetUserId, $entry->targetEmail]);
        self::assertSame(['role' => 'ROLE_DEV', 'revoked_sessions' => 2], $entry->details);
    }

    public function testAnAdminCannotDeleteAnOwner(): void
    {
        $this->expectException(PermissionDeniedException::class);

        $this->useCase($this->users($this->user(1, Role::Admin), $this->user(2, Role::Owner)))
            ->execute(new DeleteUserRequest(userId: 2, actorId: 1));
    }

    public function testTheLastActiveOwnerIsNotDeleted(): void
    {
        $this->activeOwners = 1;

        try {
            $this->useCase($this->users($this->user(1, Role::Owner), $this->user(2, Role::Owner)))
                ->execute(new DeleteUserRequest(userId: 2, actorId: 1));
            self::fail('the last active owner should not be deleted');
        } catch (BusinessLogicException $exception) {
            self::assertSame('last_owner', $exception->getContext()['rule']);
        }

        self::assertSame([], $this->events);
    }

    private function useCase(UserRepositoryInterface $users, int $activeSessions = 0): DeleteUserUseCase
    {
        return new DeleteUserUseCase(
            accounts: $this->accounts($users),
            userRepository: $users,
            policy: new UserAdministrationPolicy(),
            sessions: $this->sessions($activeSessions),
            auditTrail: $this->auditTrail(),
            requestValidator: $this->validator(),
            translator: new IdentityTranslator(),
        );
    }
}
