<?php
declare(strict_types = 1);
namespace App\Domain\Authentication\Entity;

use Gesdinet\JWTRefreshTokenBundle\Model\AbstractRefreshToken as BaseRefreshToken;

/**
 * NOTA (out/2026): mapeamento do Doctrine movido para
 * src/Infrastructure/Persistence/Doctrine/Mapping/Authentication/RefreshToken.orm.xml,
 * junto com as outras entidades do Domain (mesma tabela refresh_tokens,
 * mesmas colunas e o mesmo repositório do gesdinet).
 */
class RefreshToken extends BaseRefreshToken {
    protected $id;

    protected $refreshToken;

    protected $username;

    protected $valid;

    /**
     * @return int|null
     */
    public function getId(): ?int {
        return $this->id;
    }
}
