<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

final class AuthController extends AbstractController
{
    /** The request is intercepted by the firewall's json_login authenticator. */
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return $this->json(['message' => 'Authentication handled by the security firewall.']);
    }

    /** Small authenticated endpoint used to verify bearer tokens end to end. */
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof UserInterface) {
            throw $this->createAccessDeniedException('A valid bearer token is required.');
        }

        return $this->json([
            'username' => $user->getUserIdentifier(),
            'name' => $user instanceof User ? $user->getName() : $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ]);
    }
}
