<?php
declare(strict_types = 1);

namespace App\Domain\User\Entity;

use App\Domain\User\ValueObject\Role;
use DateTimeImmutable;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * NOTA (out/2026): o mapeamento do Doctrine saiu dos atributos desta
 * classe e foi para
 * src/Infrastructure/Persistence/Doctrine/Mapping/User/User.orm.xml.
 * O atributo #[ORM\Entity(repositoryClass: UserRepository::class)] obrigava
 * o Domain a importar App\Infrastructure\Persistence\Repository\UserRepository
 * — dependência apontando para fora, ao contrário da Clean Architecture.
 * O XML mantém exatamente a mesma tabela, colunas, índice único e
 * repositório (o $entityManager->getRepository(User::class) dos testes
 * continua devolvendo o UserRepository). A regra UniqueEntity (email
 * único) foi junto para config/validator/User.yaml, porque também é
 * detalhe de persistência (consulta o banco).
 *
 * NOTA (nov/2026): a conta ganhou datas (criação e último login), o
 * bloqueio (quando e por quê) e o papel na hierarquia
 * (App\Domain\User\ValueObject\Role): getRole() é o mais alto dos
 * roles guardados, e assignRole() guarda um papel só. Usados pela
 * administração de contas.
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface {
    public ?int $id = null {
        get {
            return $this->id;
        }
    }

    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    private array $roles = [];

    /**
     * The hashed password
     * @var ?string $password
     */
    private ?string $password = null;

    private bool $isVerified = false;

    /**
     * NOTA (nov/2026): quando a conta foi criada. null nas contas criadas
     * antes desta coluna existir: a data delas não foi registrada.
     */
    private ?DateTimeImmutable $createdAt = null;

    /**
     * NOTA (nov/2026): o último login com email e senha (POST
     * /api/login_check). As renovações do access token não contam.
     */
    private ?DateTimeImmutable $lastLoginAt = null;

    /**
     * NOTA (nov/2026): quando a conta foi bloqueada (null = liberada) e o
     * motivo que o admin escreveu.
     */
    private ?DateTimeImmutable $blockedAt = null;

    private ?string $blockedReason = null;

    /**
     * NOTA (nov/2026): uma conta nova registra a data de criação. O
     * Doctrine não chama o construtor ao carregar uma conta do banco.
     */
    public function __construct() {
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string {
        return (string)$this->email;
    }

    /**
     * Getter explícito para $id.
     *
     * Necessário porque testes e outros consumidores (UserRepositoryTest,
     * etc.) chamam $user->getId() em vez de acessar a propriedade pública
     * diretamente. A propriedade $id já é pública (com property hook), mas
     * sem este método a chamada quebra com "Call to undefined method
     * getId()".
     *
     * @return int|null
     */
    public function getId(): ?int {
        return $this->id;
    }

    /**
     * @return list<string>
     * @see UserInterface
     *
     */
    public function getRoles(): array {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique(array: $roles);
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    // Getters

    /**
     * @return string|null
     */
    public function getEmail(): ?string {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string {
        return $this->password;
    }

    /**
     * @return bool
     */
    public function isVerified(): bool {
        return $this->isVerified;
    }

    /**
     * O papel da conta na hierarquia: o mais alto dos roles guardados.
     *
     * @return \App\Domain\User\ValueObject\Role
     */
    public function getRole(): Role {
        return Role::highestOf($this->getRoles());
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getCreatedAt(): ?DateTimeImmutable {
        return $this->createdAt;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getLastLoginAt(): ?DateTimeImmutable {
        return $this->lastLoginAt;
    }

    /**
     * @return bool
     */
    public function isBlocked(): bool {
        return $this->blockedAt !== null;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getBlockedAt(): ?DateTimeImmutable {
        return $this->blockedAt;
    }

    /**
     * @return string|null
     */
    public function getBlockedReason(): ?string {
        return $this->blockedReason;
    }

    // Setters

    /**
     * @param string $email
     *
     * @return $this
     */
    public function setEmail(string $email): static {
        $this->email = $email;

        return $this;
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @param string $password
     *
     * @return $this
     */
    public function setPassword(string $password): static {
        $this->password = $password;

        return $this;
    }

    /**
     * @param bool $isVerified
     *
     * @return \App\Domain\User\Entity\User
     */
    public function setIsVerified(bool $isVerified): static {
        $this->isVerified = $isVerified;

        return $this;
    }

    /**
     * Guarda um papel só (ROLE_USER não precisa ser guardado: getRoles()
     * sempre o inclui).
     *
     * @param \App\Domain\User\ValueObject\Role $role
     *
     * @return $this
     */
    public function assignRole(Role $role): static {
        $this->roles = $role === Role::User ? [] : [$role->value];

        return $this;
    }

    /**
     * @param \DateTimeImmutable $at
     *
     * @return $this
     */
    public function recordLogin(DateTimeImmutable $at): static {
        $this->lastLoginAt = $at;

        return $this;
    }

    /**
     * @param string             $reason
     * @param \DateTimeImmutable $at
     *
     * @return $this
     */
    public function block(string $reason, DateTimeImmutable $at): static {
        $this->blockedAt = $at;
        $this->blockedReason = $reason;

        return $this;
    }

    /**
     * @return $this
     */
    public function unblock(): static {
        $this->blockedAt = null;
        $this->blockedReason = null;

        return $this;
    }
}
