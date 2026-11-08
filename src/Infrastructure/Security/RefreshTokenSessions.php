<?php

declare(strict_types = 1);

namespace App\Infrastructure\Security;

use App\Application\Admin\Port\UserSessionsInterface;
use App\Domain\Authentication\Entity\RefreshToken;
use App\Domain\User\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implementação da UserSessionsInterface: as sessões são os refresh tokens
 * da tabela refresh_tokens do gesdinet. O gesdinet guarda o email da conta
 * na coluna `username` e a validade em `valid` (a data em que vence).
 *
 * Encerrar é o mesmo que a troca de senha faz (RefreshTokenRevoker: apaga
 * todos os tokens da conta); a contagem vem antes, só dos que ainda valiam.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final readonly class RefreshTokenSessions implements UserSessionsInterface {
    /**
     * @param \Doctrine\ORM\EntityManagerInterface            $entityManager
     * @param \App\Infrastructure\Security\RefreshTokenRevoker $refreshTokenRevoker
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RefreshTokenRevoker $refreshTokenRevoker,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function countActiveFor(User $user): int {
        return (int) $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(token.id)')
            ->from(RefreshToken::class, 'token')
            ->where('token.username = :username')
            ->andWhere('token.valid > :now')
            ->setParameter('username', $user->getUserIdentifier())
            ->setParameter('now', new DateTime())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * {@inheritDoc}
     */
    public function revokeAllFor(User $user): int {
        $active = $this->countActiveFor($user);
        $this->refreshTokenRevoker->revokeAllFor($user);

        return $active;
    }
}
