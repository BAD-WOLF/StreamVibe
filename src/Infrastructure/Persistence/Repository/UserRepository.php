<?php
declare(strict_types = 1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends ServiceEntityRepository<User>
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
     */
    public function save(User $user, bool $flush = true): void {
        $this->getEntityManager()->persist($user);

        if ($flush) {
            $this->getEntityManager()->flush();
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