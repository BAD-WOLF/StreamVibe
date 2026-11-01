<?php

declare(strict_types = 1);

namespace App\Application\Person\UseCase\ExternalIds;

use App\Application\Person\DTO\ExternalIds\Request\GetPersonExternalIdsRequest;
use App\Application\Person\DTO\ExternalIds\Response\GetPersonExternalIdsResponse;
use App\Application\Shared\Gateway\MovieDatabaseGatewayInterface;
use App\Application\Shared\RequestValidator;
use App\Domain\Exception\BusinessLogicException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Ids de uma pessoa em outros sites (IMDb, Wikidata, Facebook, Instagram,
 * TikTok, X/Twitter, YouTube...), via TMDB /person/{id}/external_ids.
 *
 * NOTA (out/2026): criado agora. Antes existia só o placeholder
 * includeExternalIds no GetPersonDetailsUseCase, que sempre devolvia null.
 * Mesma estrutura dos outros UseCases de pessoa (validação, erros
 * 400/404/502/503).
 */
final readonly class GetPersonExternalIdsUseCase {
    /**
     * @param \App\Application\Shared\Gateway\MovieDatabaseGatewayInterface $movieDatabaseGateway
     * @param \App\Application\Shared\RequestValidator                      $requestValidator
     * @param \Psr\Log\LoggerInterface                                      $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface            $translator
     */
    public function __construct(
        private MovieDatabaseGatewayInterface $movieDatabaseGateway,
        private RequestValidator $requestValidator,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Person\DTO\ExternalIds\Request\GetPersonExternalIdsRequest $request
     *
     * @return \App\Application\Person\DTO\ExternalIds\Response\GetPersonExternalIdsResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\BusinessLogicException
     * @throws \App\Domain\Exception\ExternalServiceException
     */
    public function execute(
        GetPersonExternalIdsRequest $request,
    ): GetPersonExternalIdsResponse {
        $this->requestValidator->validate(
            $request,
            'person_external_ids_request',
            ['person_id' => $request->personId],
        );

        $externalIds = $this->movieDatabaseGateway->getPersonExternalIds(
            $request->personId,
        );

        if (!isset($externalIds['id'])) {
            $this->logger->warning('Person not found for external IDs lookup', [
                'person_id' => $request->personId,
            ]);

            throw BusinessLogicException::notFound('Person', $request->personId);
        }

        $this->logger->info('Person external IDs retrieved successfully', [
            'person_id' => $request->personId,
        ]);

        return GetPersonExternalIdsResponse::success(
            personId: (int) $externalIds['id'],
            externalIds: $externalIds,
            message: $this->translator->trans(
                'Person external IDs retrieved successfully',
            ),
        );
    }
}
