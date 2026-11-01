<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Authentication\UseCase\ResetPassword\UserRequests;

use App\Application\Authentication\DTO\ResetPassword\UserRequests\Request\GetUserPasswordResetRequestsRequest;
use App\Application\Authentication\UseCase\ResetPassword\UserRequests\GetUserPasswordResetRequestsUseCase;
use App\Application\Shared\RequestValidator;
use App\Domain\Authentication\Entity\ResetPasswordSolicitation;
use App\Domain\Authentication\Repository\ResetPasswordSolicitationRepositoryInterface;
use App\Domain\Exception\PermissionDeniedException;
use App\Domain\Exception\UserNotFoundException;
use App\Domain\Exception\UserValidationException;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionProperty;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Validation;

/**
 * GetUserPasswordResetRequestsUseCase
 * (GET /reset-password/requests/{userId}): a regra de acesso — só o
 * próprio usuário ou ROLE_ADMIN — e a ordem validação → permissão →
 * consulta.
 *
 * NOTA (out/2026): criado com a correção do IDOR. Antes a rota devolvia
 * as solicitações de qualquer usuário para qualquer pessoa logada, e não
 * havia teste nenhum dessa regra.
 */
final class GetUserPasswordResetRequestsUseCaseTest extends TestCase
{
    public function testOwnerListsTheirActiveRequests(): void
    {
        $user = $this->makeUser(id: 42);
        $solicitation = $this->makeSolicitation($user, id: 7, expiresAt: new DateTimeImmutable("+30 minutes"));

        $response = $this->makeUseCase(
            $this->userRepositoryReturning($user),
            $this->resetRequestRepositoryReturning([$solicitation]),
        )->execute(new GetUserPasswordResetRequestsRequest(userId: 42, requesterId: 42));

        self::assertSame(42, $response->userId);
        self::assertCount(1, $response->activeRequests);

        $summary = $response->activeRequests[0];
        self::assertSame(7, $summary->id);
        self::assertEquals($solicitation->getRequestedAt(), $summary->createdAt);
        self::assertEquals($solicitation->getExpiresAt(), $summary->expiresAt);
    }

    public function testAnotherUserIsDeniedWithoutTouchingTheDatabase(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->never())->method("findById");

        $resetRequestRepository = $this->createMock(ResetPasswordSolicitationRepositoryInterface::class);
        $resetRequestRepository->expects($this->never())->method("findActiveRequestsForUser");

        $useCase = $this->makeUseCase($userRepository, $resetRequestRepository);

        try {
            $useCase->execute(new GetUserPasswordResetRequestsRequest(userId: 42, requesterId: 99));
            self::fail("A user must not list the requests of another user");
        } catch (PermissionDeniedException $exception) {
            self::assertSame(403, $exception->getCode());
            self::assertSame("permission_denied_error", $exception->getErrorType());
            self::assertSame(
                ["resource" => "password reset requests", "owner_id" => 42, "requester_id" => 99],
                $exception->getContext(),
            );
        }
    }

    public function testAdminListsTheRequestsOfAnotherUser(): void
    {
        $user = $this->makeUser(id: 42);

        $response = $this->makeUseCase(
            $this->userRepositoryReturning($user),
            $this->resetRequestRepositoryReturning([]),
        )->execute(new GetUserPasswordResetRequestsRequest(userId: 42, requesterId: 1, requesterIsAdmin: true));

        self::assertSame(42, $response->userId);
        self::assertSame([], $response->activeRequests);
    }

    /**
     * Validação antes da permissão: o id inválido responde 400 mesmo
     * quando quem pede é outro usuário.
     */
    public function testInvalidUserIdIsRejectedBeforeThePermissionCheck(): void
    {
        $useCase = $this->makeUseCase(
            $this->createStub(UserRepositoryInterface::class),
            $this->createStub(ResetPasswordSolicitationRepositoryInterface::class),
        );

        try {
            $useCase->execute(new GetUserPasswordResetRequestsRequest(userId: 0, requesterId: 99));
            self::fail("userId 0 should be rejected");
        } catch (UserValidationException $exception) {
            self::assertSame(400, $exception->getCode());
            self::assertSame(["User_id: User ID must be a positive integer"], $exception->getErrors());
        }
    }

    public function testUnknownUserIsNotFoundForAnAdmin(): void
    {
        $useCase = $this->makeUseCase(
            $this->userRepositoryReturning(null),
            $this->createStub(ResetPasswordSolicitationRepositoryInterface::class),
        );

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionCode(404);

        $useCase->execute(new GetUserPasswordResetRequestsRequest(userId: 42, requesterId: 1, requesterIsAdmin: true));
    }

    private function makeUseCase(
        UserRepositoryInterface $userRepository,
        ResetPasswordSolicitationRepositoryInterface $resetRequestRepository,
    ): GetUserPasswordResetRequestsUseCase {
        return new GetUserPasswordResetRequestsUseCase(
            userRepository: $userRepository,
            resetRequestRepository: $resetRequestRepository,
            requestValidator: new RequestValidator(
                Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
                new NullLogger(),
            ),
            translator: new IdentityTranslator(),
        );
    }

    private function userRepositoryReturning(?User $user): UserRepositoryInterface
    {
        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method("findById")->willReturn($user);

        return $userRepository;
    }

    /**
     * @param list<\App\Domain\Authentication\Entity\ResetPasswordSolicitation> $solicitations
     */
    private function resetRequestRepositoryReturning(array $solicitations): ResetPasswordSolicitationRepositoryInterface
    {
        $resetRequestRepository = $this->createStub(ResetPasswordSolicitationRepositoryInterface::class);
        $resetRequestRepository->method("findActiveRequestsForUser")->willReturn($solicitations);

        return $resetRequestRepository;
    }

    private function makeUser(int $id): User
    {
        $user = new User();
        $user->setEmail("user{$id}@example.com");
        $user->setPassword("hashed-password");
        $user->setIsVerified(true);
        $user->id = $id;

        return $user;
    }

    /**
     * Entidade real (ver RequestPasswordResetUseCaseTest::makeSolicitation());
     * o id, que o Doctrine preencheria, entra por reflexão (private(set)).
     */
    private function makeSolicitation(User $user, int $id, DateTimeImmutable $expiresAt): ResetPasswordSolicitation
    {
        $solicitation = new ResetPasswordSolicitation(
            user: $user,
            expiresAt: $expiresAt,
            selector: str_repeat("s", 20),
            hashedToken: "hashed-token",
        );

        new ReflectionProperty(ResetPasswordSolicitation::class, "id")->setValue($solicitation, $id);

        return $solicitation;
    }
}
