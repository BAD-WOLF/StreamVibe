<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\System\Logs\Read\Model;

use App\Application\System\DTO\Logs\Read\Response\ReadLogFileResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/system/logs/{file}: `data` é o
 * ReadLogFileResponse (ver o exemplo lá), com o envelope de sempre
 * ({"success": true, "data": {...}, "message": "Log file read successfully"}).
 *
 * Mapeado do ReadLogFileResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de ReadLogFileResponse::$message. É esta classe que o
 * controller serializa e que o Swagger documenta.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
#[AutoMapFromDto(ReadLogFileResponse::class)]
final readonly class ReadLogFileOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\System\DTO\Logs\Read\Response\ReadLogFileResponse $data
     * @param string|null $message
     */
    public function __construct(
        public ReadLogFileResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
