<?php

declare(strict_types = 1);

namespace App\Domain\User\Repository;

use App\Domain\User\Entity\User;

/**
 *
 */
interface UserRepositoryInterface {
    /**
     * @param \App\Domain\User\Entity\User $user
     * @param bool                         $flush
     *
     * @return void
     */
    public function save(User $user, bool $flush = false): void;

    /**
     * @param \App\Domain\User\Entity\User $user
     * @param bool                         $flush
     *
     * @return void
     */
    public function remove(User $user, bool $flush = false): void;

    /**
     * @param int $id
     *
     * @return \App\Domain\User\Entity\User|null
     */
    public function findById(int $id): ?User;

    /**
     * @param string $email
     *
     * @return \App\Domain\User\Entity\User|null
     */
    public function findByEmail(string $email): ?User;

    /**
     * @return array
     */
    public function findAll(): array;

    /**
     * @return array
     */
    public function findVerifiedUsers(): array;

    /**
     * @return array
     */
    public function findUnverifiedUsers(): array;

    /**
     * @param \App\Domain\User\Entity\User $user
     * @param string                       $newHashedPassword
     *
     * @return void
     */
    public function upgradePassword(
        User $user,
        string $newHashedPassword,
    ): void;
}
