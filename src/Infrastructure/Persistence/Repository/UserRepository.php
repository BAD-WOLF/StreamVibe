<?php
declare(strict_types = 1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Exception\UserAlreadyExistsException;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
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
 *
 * NOTA (nov/2026): findPage() e countActiveWithRole(), da administração de
 * contas. Os dois em SQL nativo: o filtro de papel olha dentro da coluna
 * roles (JSON), e o DQL não tem operador para isso.
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

    /**
     * {@inheritDoc}
     */
    public function findPage(
        int $page,
        int $perPage,
        ?string $search,
        ?Role $role,
        ?bool $verified,
        ?bool $blocked,
    ): array {
        [$where, $parameters] = $this->filters($search, $role, $verified, $blocked);

        $mapping = new ResultSetMappingBuilder($this->getEntityManager());
        $mapping->addRootEntityFromClassMetadata(User::class, 'u');

        /** @var list<\App\Domain\User\Entity\User> $users */
        $users = $this->getEntityManager()
                      ->createNativeQuery(
                          sprintf(
                              'SELECT %s FROM "user" u%s ORDER BY u.id DESC LIMIT %d OFFSET %d',
                              $mapping->generateSelectClause(),
                              $where,
                              $perPage,
                              ($page - 1) * $perPage,
                          ),
                          $mapping,
                      )
                      ->setParameters($parameters)
                      ->getResult();

        return ['users' => $users, 'total' => $this->countMatching($where, $parameters)];
    }

    /**
     * {@inheritDoc}
     */
    public function countActiveWithRole(Role $role): int {
        [$where, $parameters] = $this->filters(null, $role, null, false);

        return $this->countMatching($where, $parameters);
    }

    /**
     * O WHERE (vazio sem filtros) e os parâmetros dele.
     *
     * - search: trecho do email, sem diferenciar maiúsculas (% e _ valem
     *   como texto); só com dígitos, também o id exato;
     * - role: o papel é o mais alto da lista (Role::highestOf()). Para
     *   ROLE_DEV, a lista tem ROLE_DEV e não tem ROLE_ADMIN nem ROLE_OWNER;
     *   para ROLE_USER, não tem nenhum dos três;
     * - verified e blocked: is_verified e blocked_at (bloqueada = tem data).
     *
     * @param string|null                            $search
     * @param \App\Domain\User\ValueObject\Role|null $role
     * @param bool|null                              $verified
     * @param bool|null                              $blocked
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private function filters(?string $search, ?Role $role, ?bool $verified, ?bool $blocked): array {
        $conditions = [];
        $parameters = [];

        if ($search !== null && $search !== '') {
            $parameters['search'] = '%' . addcslashes($search, '%_\\') . '%';
            $conditions[] = 'u.email ILIKE :search';

            if (ctype_digit($search)) {
                $parameters['id'] = $search;
                $conditions[] = '(' . array_pop($conditions) . ' OR CAST(u.id AS TEXT) = :id)';
            }
        }

        if ($role !== null) {
            foreach (Role::cases() as $candidate) {
                if ($candidate === Role::User || !$candidate->includes($role)) {
                    continue;
                }

                $parameter = 'role_' . strtolower($candidate->name);
                $parameters[$parameter] = json_encode([$candidate->value], JSON_THROW_ON_ERROR);
                $contains = sprintf('CAST(u.roles AS JSONB) @> CAST(:%s AS JSONB)', $parameter);
                $conditions[] = $candidate === $role ? $contains : sprintf('NOT (%s)', $contains);
            }
        }

        if ($verified !== null) {
            $conditions[] = $verified ? 'u.is_verified = TRUE' : 'u.is_verified = FALSE';
        }

        if ($blocked !== null) {
            $conditions[] = $blocked ? 'u.blocked_at IS NOT NULL' : 'u.blocked_at IS NULL';
        }

        return [$conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions), $parameters];
    }

    /**
     * Quantas contas passam no WHERE de filters() (o count() herdado do
     * EntityRepository só aceita critérios de igualdade).
     *
     * @param string                $where
     * @param array<string, string> $parameters
     *
     * @return int
     */
    private function countMatching(string $where, array $parameters): int {
        return (int) $this->getEntityManager()
                          ->getConnection()
                          ->fetchOne(sprintf('SELECT COUNT(*) FROM "user" u%s', $where), $parameters);
    }
}
