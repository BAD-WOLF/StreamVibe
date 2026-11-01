<?php
declare(strict_types = 1);

namespace App\Infrastructure\Security;

use App\Application\Authentication\Port\RefreshTokenRevokerInterface;
use App\Domain\Authentication\Entity\RefreshToken;
use App\Domain\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implementação da RefreshTokenRevokerInterface: apaga os refresh tokens
 * do usuário na tabela do gesdinet (refresh_tokens). O gesdinet guarda o
 * identificador do usuário (o email) na coluna `username`.
 *
 * NOTA (out/2026): criado junto com POST /reset-password/confirm. Um
 * DELETE em DQL resolve com uma query só; o RefreshTokenManager do
 * gesdinet não tem "apagar todos de um usuário".
 */
final readonly class RefreshTokenRevoker implements RefreshTokenRevokerInterface {
    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function revokeAllFor(User $user): int {
        return (int) $this->entityManager
            ->createQueryBuilder()
            ->delete(RefreshToken::class, 'token')
            ->where('token.username = :username')
            ->setParameter('username', $user->getUserIdentifier())
            ->getQuery()
            ->execute();
    }
}
