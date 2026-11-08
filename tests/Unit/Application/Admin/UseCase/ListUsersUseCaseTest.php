<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Admin\UseCase;

use App\Application\Admin\DTO\User\List\Request\ListUsersRequest;
use App\Application\Admin\UseCase\User\List\ListUsersUseCase;
use App\Domain\Exception\UserValidationException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Role;
use App\Tests\Unit\Application\Admin\AdminTestCase;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * ListUsersUseCase: os filtros chegam ao repositório como tipos do Domain
 * (Role, bool, null = sem filtro), e a paginação sai do total (20 por
 * página).
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class ListUsersUseCaseTest extends AdminTestCase
{
    public function testFiltersAndPagination(): void
    {
        $user = $this->user(7, Role::Dev, verified: false);
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())
                   ->method('findPage')
                   ->with(2, 20, 'ana', Role::Dev, false, null)
                   ->willReturn(['users' => [$user], 'total' => 45]);

        $response = $this->useCase($repository)->execute(
            new ListUsersRequest(page: 2, search: '  ana ', role: 'ROLE_DEV', verified: false, blocked: null),
        );

        self::assertCount(1, $response->users);
        $summary = $response->users[0];
        self::assertSame([7, 'user7@example.com', 'ROLE_DEV', false], [$summary->id, $summary->email, $summary->role, $summary->verified]);
        self::assertSame($user->getCreatedAt(), $summary->createdAt);
        self::assertNull($summary->lastLoginAt);
        self::assertNull($summary->blockedAt);
        self::assertSame([2, 3, 45], [$response->page, $response->totalPages, $response->totalResults]);
        self::assertTrue($response->hasNext);
        self::assertTrue($response->hasPrevious);
        self::assertSame([3, 1], [$response->nextPage, $response->previousPage]);
        self::assertSame('Users retrieved successfully', $response->message);
    }

    public function testABlankSearchIsNoFilter(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())
                   ->method('findPage')
                   ->with(1, 20, null, null, null, true)
                   ->willReturn(['users' => [], 'total' => 0]);

        $response = $this->useCase($repository)->execute(new ListUsersRequest(search: '   ', blocked: true));

        self::assertSame([], $response->users);
        self::assertSame([1, 1, 0], [$response->page, $response->totalPages, $response->totalResults]);
        self::assertFalse($response->hasNext);
        self::assertNull($response->previousPage);
    }

    public function testInvalidFiltersAreRejected(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->never())->method('findPage');

        try {
            $this->useCase($repository)->execute(new ListUsersRequest(page: 0, role: 'ROLE_ROOT'));
            self::fail('page 0 and an unknown role should be rejected');
        } catch (UserValidationException $exception) {
            self::assertSame(
                ['Page: Page must be a positive integer', 'Role: Role must be one of: ROLE_USER, ROLE_DEV, ROLE_ADMIN, ROLE_OWNER'],
                $exception->getErrors(),
            );
        }
    }

    private function useCase(UserRepositoryInterface $repository): ListUsersUseCase
    {
        return new ListUsersUseCase($repository, $this->validator(), new IdentityTranslator());
    }
}
