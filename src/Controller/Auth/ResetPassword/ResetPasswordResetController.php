<?php

namespace App\Controller\Auth\ResetPassword;


use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use App\Entity\User;
use App\ApiResource\Auth\ResetPassword\ResetPasswordEntryPoint;

// 2. Validate & Perform Password Reset
#[Route(name: 'api_validate_reset_token',
    defaults: [
        '_api_resource_class' => ResetPasswordEntryPoint::class,
        '_api_operation_name' => 'get_validate_reset_token',
    ],
    methods: ['GET'])]
#[Route(name: 'api_reset_password',
    defaults: [
        '_api_resource_class' => ResetPasswordEntryPoint::class,
        '_api_operation_name' => 'post_reset_password',
    ],
    methods: ['POST'])]
class ResetPasswordResetController extends AbstractController {
    /**
     * @param \SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface $resetPasswordHelper
     * @param \Doctrine\ORM\EntityManagerInterface                            $entityManager
     */
    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request                            $request
     * @param string|null                                                          $token
     * @param \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $hasher
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function __invoke(
        Request $request,
        ?string $token,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        if ($request->isMethod('GET')) {
            // Validate token without state
            if (!$token) {
                return $this->json(['success' => false, 'error' => 'Token is required'], 400);
            }
            try {
                $this->resetPasswordHelper->validateTokenAndFetchUser($token);

                return $this->json(['success' => true, 'result' => ['status' => 'token_valid']], 200);
            } catch (ResetPasswordExceptionInterface $e) {
                return $this->json(['success' => false, 'error' => 'Invalid or expired token'], 400);
            }
        }

        // POST: perform reset
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['success' => false, 'error' => 'Invalid JSON payload'], 400);
        }

        $reqToken = $data['token'] ?? null;
        $plainPassword = $data['plainPassword'] ?? null;

        if (!$reqToken || !$plainPassword) {
            return $this->json(['success' => false, 'error' => 'Token and plainPassword are required'], 400);
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($reqToken);
        } catch (ResetPasswordExceptionInterface) {
            return $this->json(['success' => false, 'error' => 'Invalid or expired token'], 400);
        }

        $user->setPassword($hasher->hashPassword($user, $plainPassword));
        $this->entityManager->flush();
        $this->resetPasswordHelper->removeResetRequest($reqToken);

        return $this->json(['success' => true, 'result' => ['status' => 'password_changed']], 200);
    }
}
