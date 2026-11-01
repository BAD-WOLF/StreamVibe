<?php
declare(strict_types = 1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Exception\UserAlreadyExistsException;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * NOTA (out/2026): save() traduz a violação do índice único de email
 * (UNIQ_IDENTIFIER_EMAIL) em UserAlreadyExistsException (409). O
 * RegisterUserUseCase já confere o email antes de criar o usuário, mas
 * dois cadastros simultâneos com o mesmo email passavam os dois pela
 * conferência e o segundo estourava no banco — 500 com a mensagem do
 * PostgreSQL no log. É o repositório (Infrastructure) que conhece o
 * Doctrine, então a tradução fica aqui, e o Domain/Application só veem a
 * exceção de domínio.
 */
class UserRepository extends ServiceEntityRepository implements
    UserRepositoryInterface,
    PasswordUpgraderInterface {
    /**
     * @param \Doctrine\Persistence\ManagerRegistry              $registry
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     */
    public function __construct(
        ManagerRegistry $registry,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(
        PasswordAuthenticatedUserInterface $user,
        string $newHashedPassword,
    ): void {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf(
                    $this->translator->trans(
                        'Instances of "%s" are not supported.',
                    ),
                    $user::class,
                ),
            );
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @param \App\Domain\User\Entity\User $user
     * @param bool                         $flush
     *
     * @return void
     * @throws \App\Domain\Exception\UserAlreadyExistsException
     */
    public function save(User $user, bool $flush = true): void {
        $this->getEntityManager()->persist($user);

        if (!$flush) {
            return;
        }

        try {
            $this->getEntityManager()->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw new UserAlreadyExistsException(
                message: $this->translator->trans('A user with this email already exists'),
                previous: $exception,
                context: ['email' => $user->getEmail()],
            );
        }
    }

    /**
     * @param \App\Domain\User\Entity\User $user
     * @param bool                         $flush
     *
     * @return void
     */
    public function remove(User $user, bool $flush = true): void {
        $this->getEntityManager()->remove($user);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param string $email
     *
     * @return \App\Domain\User\Entity\User|null
     */
    public function findByEmail(string $email): ?User {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * @param int $id
     *
     * @return \App\Domain\User\Entity\User|null
     */
    public function findById(int $id): ?User {
        return $this->find($id);
    }

    /**
     * @return array
     */
    public function findAll(): array {
        return parent::findAll();
    }

    /**
     * @return array
     */
    public function findVerifiedUsers(): array {
        return $this->createQueryBuilder('u')
                    ->andWhere('u.isVerified = :verified')
                    ->setParameter('verified', true)
                    ->orderBy('u.id', 'ASC')
                    ->getQuery()
                    ->getResult();
    }

    /**
     * @return array
     */
    public function findUnverifiedUsers(): array {
        return $this->createQueryBuilder('u')
                    ->andWhere('u.isVerified = :verified')
                    ->setParameter('verified', false)
                    ->orderBy('u.id', 'ASC')
                    ->getQuery()
                    ->getResult();
    }
}
