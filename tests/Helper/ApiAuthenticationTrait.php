<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Domain\User\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Trait for authenticating API requests in tests with a real JWT
 *
 * Generates the token with the same JWT manager the application uses
 * (LexikJWTAuthenticationBundle) and sends it in the Authorization header,
 * so the request goes through the real "api" firewall — no security mock.
 *
 * NOTA (out/2026): a API em /{locale}/api exige JWT (security.yaml), com
 * exceção dos fluxos de conta. O usuário precisa existir no banco, porque
 * o firewall recarrega o usuário pelo email do token a cada requisição
 * (provider app_user_provider). As chaves usadas nos testes são geradas
 * por tests/bootstrap.php (ver phpunit.dist.xml).
 */
trait ApiAuthenticationTrait
{
    /**
     * Sends every next request of the client as the given user
     * ("Authorization: Bearer <JWT>")
     *
     * @return string The generated JWT
     */
    private function authenticateClient(KernelBrowser $client, User $user): string
    {
        /** @var JWTTokenManagerInterface $jwtManager */
        $jwtManager = static::getContainer()->get('lexik_jwt_authentication.jwt_manager');

        $token = $jwtManager->create($user);

        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $token);

        return $token;
    }

    /**
     * Makes the next requests of the client anonymous again
     *
     * An empty Authorization header is read by the JWT authenticator as
     * "no token", exactly like a request without the header.
     */
    private function unauthenticateClient(KernelBrowser $client): void
    {
        $client->setServerParameter('HTTP_AUTHORIZATION', '');
    }
}
