<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\User;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/profile')]
final class ProfileController extends AbstractController
{

    public function __construct(
        private readonly ProfileRepository $profileRepository,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "firstName", type: "string"),
                new OA\Property(property: "lastName", type: "string"),
                new OA\Property(property: "email", type: "string"),
                new OA\Property(property: "password", type: "string")
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Retourne le profil d'un utilisateur",
        content: new OA\JsonContent(
            ref: new Model(type: Profile::class, groups: ["user:read"])
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Données invalides"
    )]
    #[OA\Tag(name: "Users")]
    #[Security(name: "Bearer")]
    #[Route('', name: 'app_profile_index', methods: ['GET'])]
    public function index(): JsonResponse
    {

    }
}
