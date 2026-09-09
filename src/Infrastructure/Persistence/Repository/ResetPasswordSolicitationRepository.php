<?php
declare(strict_types = 1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Authentication\Entity\ResetPasswordSolicitation;
use App\Domain\Authentication\Repository\ResetPasswordSolicitationRepositoryInterface;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Persistence\Repository\ResetPasswordRequestRepositoryTrait;
use SymfonyCasts\Bundle\ResetPassword\Persistence\ResetPasswordRequestRepositoryInterface as SymfonyResetPasswordRequestRepositoryInterface;
use DateTime;
use DateTimeInterface;

/**
 * @extends ServiceEntityRepository<ResetPasswordSolicitation>
 */
class ResetPasswordSolicitationRepository extends ServiceEntityRepository implements
    ResetPasswordSolicitationRepositoryInterface,
    SymfonyResetPasswordRequestRepositoryInterface {
    use ResetPasswordRequestRepositoryTrait;

    /**
     * @param \Doctrine\Persistence\ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, ResetPasswordSolicitation::class);
    }

    /**
     * @param \App\Domain\Authentication\Entity\ResetPasswordSolicitation $resetPasswordRequest
     * @param bool                                                        $flush
     *
     * @return void
     */
    public function save(
        ResetPasswordSolicitation $resetPasswordRequest,
        bool $flush = false,
    ): void {
        $this->getEntityManager()->persist($resetPasswordRequest);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param \App\Domain\User\Entity\User $user
     *
     * @return \App\Domain\Authentication\Entity\ResetPasswordSolicitation|null
     */
    public function findByUser(User $user): ?ResetPasswordSolicitation {
        return $this->findOneBy(['user' => $user], ['id' => 'DESC']);
    }

    /**
     * @param string $selector
     *
     * @return \App\Domain\Authentication\Entity\ResetPasswordSolicitation|null
     */
    public function findBySelector(string $selector): ?ResetPasswordSolicitation {
        return $this->findOneBy(['selector' => $selector]);
    }

    /**
     * Required by SymfonyCasts ResetPasswordBundle
     * Create a new ResetPasswordSolicitation object.
     *
     * @param object $user        User entity - typically implements Symfony\Component\Security\Core\User\UserInterface
     * @param string $selector    A non-hashed random string used to fetch a request from persistence
     * @param string $hashedToken The hashed token used to verify a reset request
     */
    public function createResetPasswordRequest(
        object $user,
        DateTimeInterface $expiresAt,
        string $selector,
        string $hashedToken,
    ): ResetPasswordRequestInterface {
        return new ResetPasswordSolicitation(
            $user,
            $expiresAt,
            $selector,
            $hashedToken,
        );
    }

    /**
     * @param \App\Domain\User\Entity\User $user
     *
     * @return array
     */
    public function findActiveRequestsForUser(User $user): array {
        return $this->createQueryBuilder('r')
                    ->andWhere('r.user = :user')
                    ->andWhere('r.expiresAt > :now')
                    ->setParameter('user', $user)
                    ->setParameter('now', new DateTime())
                    ->orderBy('r.requestedAt', 'DESC')
                    ->getQuery()
                    ->getResult();
    }

    /**
     * @param \SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface $resetPasswordRequest
     *
     * @return void
     */
    public function persistResetPasswordRequest(
        ResetPasswordRequestInterface $resetPasswordRequest,
    ): void {
        $this->getEntityManager()->persist($resetPasswordRequest);
        $this->getEntityManager()->flush();
    }

    /**
     * @param string $selector
     *
     * @return \SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface|null
     */
    public function findResetPasswordRequest(
        string $selector,
    ): ?ResetPasswordRequestInterface {
        return $this->findOneBy(['selector' => $selector]);
    }

    // Required methods by SymfonyCasts\Bundle\ResetPassword

    /**
     * @param object $user
     *
     * @return \DateTimeInterface|null
     */
    public function getMostRecentNonExpiredRequestDate(
        object $user,
    ): ?DateTimeInterface {
        $result = $this->createQueryBuilder('r')
                       ->select('r.requestedAt')
                       ->andWhere('r.user = :user')
                       ->andWhere('r.expiresAt > :now')
                       ->setParameter('user', $user)
                       ->setParameter('now', new DateTime())
                       ->orderBy('r.requestedAt', 'DESC')
                       ->setMaxResults(1)
                       ->getQuery()
                       ->getOneOrNullResult();

        return $result ? $result['requestedAt'] : null;
    }

    /**
     * @param \SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface $resetPasswordRequest
     *
     * @return void
     */
    public function removeResetPasswordRequest(
        ResetPasswordRequestInterface $resetPasswordRequest,
    ): void {
        $this->getEntityManager()->remove($resetPasswordRequest);
        $this->getEntityManager()->flush();
    }

    /**
     * @param \App\Domain\Authentication\Entity\ResetPasswordSolicitation $resetPasswordRequest
     * @param bool                                                        $flush
     *
     * @return void
     */
    public function remove(
        ResetPasswordSolicitation $resetPasswordRequest,
        bool $flush = false,
    ): void {
        $this->getEntityManager()->remove($resetPasswordRequest);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return int
     */
    public function removeExpiredResetPasswordRequests(): int {
        return $this->removeExpiredRequests();
    }

    /**
     * @return int
     */
    public function removeExpiredRequests(): int {
        return $this->createQueryBuilder('r')
                    ->delete()
                    ->where('r.expiresAt < :now')
                    ->setParameter('now', new DateTime())
                    ->getQuery()
                    ->execute();
    }
}
