<?php
declare(strict_types = 1);

namespace App\Domain\Authentication\Entity;

use App\Domain\User\Entity\User;
use App\Infrastructure\Persistence\Repository\ResetPasswordSolicitationRepository;
use Doctrine\ORM\Mapping as ORM;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestTrait;
use DateTimeInterface;

/**
 *
 */
#[ORM\Entity(repositoryClass: ResetPasswordSolicitationRepository::class)]
#[ORM\Table(name: 'reset_password_solicitation')]
class ResetPasswordSolicitation implements ResetPasswordRequestInterface {
    use ResetPasswordRequestTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public private(set) ?int $id = null {
        /**
         * @return int|null
         */
        get => $this->id;
    }

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    public private(set) ?User $user = null {
        /**
         * @return \App\Domain\User\Entity\User|null
         */
        get => $this->user;
        /**
         * @return mixed
         */
        set => $this->user = $value;
    }

    /**
     * @param \App\Domain\User\Entity\User $user
     * @param \DateTimeInterface           $expiresAt
     * @param string                       $selector
     * @param string                       $hashedToken
     */
    public function __construct(
        User $user,
        DateTimeInterface $expiresAt,
        string $selector,
        string $hashedToken,
    ) {
        $this->user = $user;
        $this->initialize($expiresAt, $selector, $hashedToken);
    }

    // Compatibility methods for ResetPasswordRequestInterface

    /**
     * @return int|null
     */
    public function getId(): ?int {
        return $this->id;
    }

    /**
     * @return \App\Domain\User\Entity\User
     */
    public function getUser(): User {
        return $this->user;
    }
}
