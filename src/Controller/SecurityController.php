<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

class SecurityController extends AbstractController
{
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
}
