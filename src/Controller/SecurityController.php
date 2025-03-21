<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

class SecurityController extends AbstractController
{
    public const SCOPES = [
        'google' => []
    ];
    #[Route("/login_check", name: "api_login_check", methods: ["POST"])]
    #[OA\Post(
        path: "/api/login_check",
        summary: "Authentification pour obtenir un token JWT",
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "email", type: "string"),
                    new OA\Property(property: "password", type: "string")
                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Retourne un token JWT",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "token", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Identifiants invalides"
            )
        ]
    )]
    public function loginCheck(): JsonResponse
    {
        throw new \LogicException('This method should not be reached!');
    }

    #[Route("/login", name: "auth_oauth_login", methods: ['GET'])]
    public function login(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }
        return $this->json([
            'message' => 'Welcome to your new controller!'
        ]);
        # r eturn $this->render('security/login.html.twig');
    }
    #[Route("/logout", name: "auth_oauth_logout", methods: ["GET"])]
    public function logout(): never
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }
    #[Route("/oauth/connect/{service}", name: "auth_oauth_connect", methods: ["GET"])]
    public function connect(string $service, ClientRegistory $clientRegistory): RedirectResponse
    {
        if (! in_array($service, array_keys(self::SCOPES), strict: true)) {
            throw new \InvalidArgumentException('Invalid service');
        }
        return $clientRegistory->getClient($service)->redirect(self::SCOPES[$service]);
    }
    #[Route("/oauth/check/{service}", name: "auth_oauth_check", methods: ["GET", "POST"])]
    public function check(): Response
    {
        return new Response(status: 200);
    }
}
