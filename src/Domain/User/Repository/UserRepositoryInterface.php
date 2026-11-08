<?php

declare(strict_types = 1);

namespace App\Domain\User\Repository;

use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\Role;

/**
 * Repositório de usuários (implementado por
 * Infrastructure\Persistence\Repository\UserRepository).
 *
 * NOTA (out/2026):
 * - save()/remove() declaravam $flush = false aqui, mas a implementação
 *   sempre usou true — quem chamava pela interface sem o argumento
 *   recebia o flush mesmo assim. O padrão daqui passou a ser o real (true).
 * - save() lança UserAlreadyExistsException quando o banco recusa o email
 *   repetido (corrida entre dois cadastros simultâneos): antes era 500.
 *
 * NOTA (nov/2026): findPage() e countActiveWithRole(), para a
 * administração de contas (a lista com filtros e a regra do último owner).
 */
interface UserRepositoryInterface {
    /**
     * @param \App\Domain\User\Entity\User $user
     * @param bool                         $flush
     *
     * @return void
     * @throws \App\Domain\Exception\UserAlreadyExistsException quando o email já existe no banco
     */
    public function save(User $user, bool $flush = true): void;

    /**
     * @param \App\Domain\User\Entity\User $user
     * @param bool                         $flush
     *
     * @return void
     */
    public function remove(User $user, bool $flush = true): void;

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

    /**
     * Uma página das contas, da mais nova para a mais antiga, com os
     * filtros que vierem (null = sem aquele filtro).
     *
     * @param int                                         $page     a partir de 1
     * @param int                                         $perPage
     * @param string|null                                 $search   trecho do email (sem diferenciar maiúsculas) ou o id
     * @param \App\Domain\User\ValueObject\Role|null      $role     o papel da conta (o mais alto)
     * @param bool|null                                   $verified
     * @param bool|null                                   $blocked
     *
     * @return array{users: list<\App\Domain\User\Entity\User>, total: int}
     */
    public function findPage(
        int $page,
        int $perPage,
        ?string $search,
        ?Role $role,
        ?bool $verified,
        ?bool $blocked,
    ): array;

    /**
     * Quantas contas ativas (não bloqueadas) têm $role como papel (o mais
     * alto).
     *
     * @param \App\Domain\User\ValueObject\Role $role
     *
     * @return int
     */
    public function countActiveWithRole(Role $role): int;
}
