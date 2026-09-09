<?php

namespace App\Application\Movie\DTO\Credits\Response\Model;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\NotExposed;

/**
 * NOTA: #[NotExposed] é necessário porque essa classe não é um
 * #[ApiResource] — sem isso, o pipeline de documentação do API
 * Platform (OpenAPI/Hydra/JSON Schema) não entra nela pra gerar o
 * schema das propriedades, e cast/crew apareceriam como array
 * genérico (ou "unknown_type") em vez de um objeto tipado.
 */
#[NotExposed]
final class CastMember {
    #[ApiProperty]
    public bool $adult;

    #[ApiProperty]
    public int $gender;

    #[ApiProperty]
    public int $id;

    #[ApiProperty]
    public string $known_for_department;

    #[ApiProperty]
    public string $name;

    #[ApiProperty]
    public string $original_name;

    #[ApiProperty]
    public float $popularity;

    #[ApiProperty]
    public ?string $profile_path;

    #[ApiProperty]
    public int $cast_id;

    #[ApiProperty]
    public string $character;

    #[ApiProperty]
    public string $credit_id;

    #[ApiProperty]
    public int $order;

    public function __construct(
        bool $adult,
        int $gender,
        int $id,
        string $known_for_department,
        string $name,
        string $original_name,
        float $popularity,
        ?string $profile_path,
        int $cast_id,
        string $character,
        string $credit_id,
        int $order,
    ) {
        $this->adult = $adult;
        $this->gender = $gender;
        $this->id = $id;
        $this->known_for_department = $known_for_department;
        $this->name = $name;
        $this->original_name = $original_name;
        $this->popularity = $popularity;
        $this->profile_path = $profile_path;
        $this->cast_id = $cast_id;
        $this->character = $character;
        $this->credit_id = $credit_id;
        $this->order = $order;
    }
}
