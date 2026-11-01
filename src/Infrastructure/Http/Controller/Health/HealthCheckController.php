<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Health;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Healthcheck do container (Docker/compose): GET /health → 200
 * {"status": "ok"}.
 *
 * Pública e fora de /{_locale}/api de propósito: não passa pelo firewall
 * de JWT nem depende do banco ou do TMDB — responde 200 enquanto o PHP e
 * o kernel do Symfony estão de pé.
 *
 * NOTA (out/2026): criado para o healthcheck de produção
 * (compose.prod.yaml e docker-health-check.sh), que chamava
 * /api/images/sizes: essa rota exige JWT (401), então o container nunca
 * ficava "healthy".
 */
#[AsController]
final class HealthCheckController
{
    #[Route("/health", name: "health_check", methods: ["GET"])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(
            ["status" => "ok"],
            JsonResponse::HTTP_OK,
            ["Cache-Control" => "no-store"],
        );
    }
}
