<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Requisições JSON nos testes de API e leitura da resposta.
 *
 *     $this->postJson($this->client, "/pt_BR/api/register", ["email" => "..."]);
 *     $data = $this->responseData($this->client);
 *
 * NOTA (out/2026): criado com os testes novos de autenticação, filmes,
 * pessoas e imagens. Os testes antigos repetiam em cada chamada os cinco
 * argumentos de $client->request() para mandar JSON e o
 * json_decode(getContent()) para ler a resposta.
 */
trait ApiRequestTrait
{
    /**
     * POST com corpo JSON. $body string vai como está (para mandar JSON
     * malformado de propósito).
     *
     * @param array<array-key, mixed>|string $body
     */
    private function postJson(KernelBrowser $client, string $uri, array|string $body): void
    {
        $client->request(
            "POST",
            $uri,
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            is_string($body) ? $body : json_encode($body, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * O corpo JSON da última resposta, decodificado.
     *
     * @return array<string, mixed>
     */
    private function responseData(KernelBrowser $client): array
    {
        $content = (string) $client->getResponse()->getContent();

        $this->assertJson($content, "The response is not JSON: " . substr($content, 0, 300));

        return json_decode($content, true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Confere o corpo de erro do ExceptionListener e devolve o `error`.
     *
     * @return array<string, mixed>
     */
    private function assertApiError(KernelBrowser $client, int $status, string $type): array
    {
        $this->assertResponseStatusCodeSame($status);

        $data = $this->responseData($client);

        $this->assertFalse($data["success"]);
        $this->assertSame($type, $data["error"]["type"] ?? null, "Unexpected error: " . json_encode($data["error"] ?? null));

        return $data["error"];
    }
}
