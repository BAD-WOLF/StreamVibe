<?php
declare(strict_types = 1);

namespace App\Domain\Authentication\Repository;

use App\Domain\Authentication\Entity\ResetPasswordSolicitation;
use App\Domain\User\Entity\User;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use DateTimeInterface;

/**
 *
 */
interface ResetPasswordSolicitationRepositoryInterface {
    /**
     * @param \App\Domain\Authentication\Entity\ResetPasswordSolicitation $resetPasswordRequest
     * @param bool                                                        $flush
     *
     * @return void
     */
    public function save(
        ResetPasswordSolicitation $resetPasswordRequest,
        bool $flush = false,
    ): void;

    /**
     * @param \App\Domain\Authentication\Entity\ResetPasswordSolicitation $resetPasswordRequest
     * @param bool                                                        $flush
     *
     * @return void
     */
    public function remove(
        ResetPasswordSolicitation $resetPasswordRequest,
        bool $flush = false,
    ): void;

    /**
     * @param \App\Domain\User\Entity\User $user
     *
     * @return \App\Domain\Authentication\Entity\ResetPasswordSolicitation|null
     */
    public function findByUser(User $user): ?ResetPasswordSolicitation;

    /**
     * @param string $selector
     *
     * @return \App\Domain\Authentication\Entity\ResetPasswordSolicitation|null
     */
    public function findBySelector(string $selector): ?ResetPasswordSolicitation;

    /**
     * @param \App\Domain\User\Entity\User $user
     *
     * @return array
     */
    public function findActiveRequestsForUser(User $user): array;

    /**
     * @return int
     */
    public function removeExpiredRequests(): int;

    // Required by SymfonyCasts ResetPasswordBundle

    /**
     * @param object             $user
     * @param \DateTimeInterface $expiresAt
     * @param string             $selector
     * @param string             $hashedToken
     *
     * @return \SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface
     */
    public function createResetPasswordRequest(
        object $user,
        DateTimeInterface $expiresAt,
        string $selector,
        string $hashedToken,
    ): ResetPasswordRequestInterface;

    /**
     * @param \SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface $resetPasswordRequest
     *
     * @return void
     */
    public function persistResetPasswordRequest(
        ResetPasswordRequestInterface $resetPasswordRequest,
    ): void;

    /**
     * @param string $selector
     *
     * @return \SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface|null
     */
    public function findResetPasswordRequest(
        string $selector,
    ): ?ResetPasswordRequestInterface;

    /**
     * @param object $user
     *
     * @return \DateTimeInterface|null
     */
    public function getMostRecentNonExpiredRequestDate(
        object $user,
    ): ?DateTimeInterface;

    /**
     * @param \SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface $resetPasswordRequest
     *
     * @return void
     */
    public function removeResetPasswordRequest(
        ResetPasswordRequestInterface $resetPasswordRequest,
    ): void;

    /**
     * @return int
     */
    public function removeExpiredResetPasswordRequests(): int;
}
