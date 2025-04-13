<?php

namespace App\Controller;

use App\Entity\DietaryPreference;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\DietaryPreferencesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[Route('/dietary-preferences')]
final class DietaryPreferenceController extends AbstractController
{
    public function __construct(
        private readonly DietaryPreferencesRepository $productRepository,
    )
    {
    }

    #[OA\Response(
        response: 200,
        description: "Retourne les préférences alimentaires",
        content: new OA\JsonContent(
            ref: new Model(type: DietaryPreference::class, groups: ["preferences:read"])
        )
    )]
    #[OA\Response(
        response: 404,
        description: "préférences alimentaires non trouvées"
    )]
    #[OA\Tag(name: "Dietary Preferences")]
    #[Security(name: "Bearer")]
    #[Route('/', name: 'app_preferences_all', methods: ['GET'])]
    public function findAll(): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $products = $this->productRepository->findAll();

        return $this->json(
            $products,
            Response::HTTP_OK,
            [],
            ['groups' => ['product:read', 'user:read', 'preferences:read']]
        );
    }
}
