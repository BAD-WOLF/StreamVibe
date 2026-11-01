<?php
declare(strict_types = 1);

namespace App\Domain\Authentication\Entity;

use App\Domain\User\Entity\User;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestTrait;
use DateTimeInterface;

/**
 * NOTA (out/2026): o mapeamento do Doctrine (inclusive dos 4 campos que
 * vêm do ResetPasswordRequestTrait: selector, hashedToken, requestedAt e
 * expiresAt) está em
 * src/Infrastructure/Persistence/Doctrine/Mapping/Authentication/ResetPasswordSolicitation.orm.xml.
 * Mesmo motivo do User: o atributo repositoryClass fazia o Domain
 * importar App\Infrastructure\Persistence\Repository\ResetPasswordSolicitationRepository.
 * Tabela, colunas e chave estrangeira continuam as mesmas da migration
 * Version20260822104709.
 */
class ResetPasswordSolicitation implements ResetPasswordRequestInterface {
    use ResetPasswordRequestTrait;

    public private(set) ?int $id = null {
        /**
         * @return int|null
         */
        get => $this->id;
    }

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

    // getUser() é exigido pela ResetPasswordRequestInterface; getId() é o
    // que a aplicação usa (ex.: PasswordResetRequestSummary).

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
