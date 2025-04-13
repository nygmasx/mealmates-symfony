<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\AvailabilityRepository;
use App\Repository\DietaryPreferencesRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;

#[Route('/product')]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository      $productRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    #[OA\Response(
        response: 200,
        description: "Retourne le profil d'un utilisateur",
        content: new OA\JsonContent(
            ref: new Model(type: Product::class, groups: ["product:read"])
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Données invalides"
    )]
    #[OA\Response(
        response: 404,
        description: "Profil non trouvé"
    )]
    #[OA\Tag(name: "Products")]
    #[Security(name: "Bearer")]
    #[Route('/', name: 'app_products_all', methods: ['GET'])]
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

    #[OA\Tag(name: "Products")]
    #[Security(name: "Bearer")]
    #[Route('/locations', name: 'app_products_location', methods: ['GET'])]
    public function getWithLocation(): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $products = $this->productRepository->findProducsWithtLocations();

        if (!$products) {
            return new JsonResponse(['message' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(
            $products,
            Response::HTTP_OK,
            [],
            ['groups' => ['product:read', 'preferences:read']]
        );
    }

    #[OA\Response(
        response: 200,
        description: "Retourne les produits d'un utilisateur",
        content: new OA\JsonContent(
            ref: new Model(type: Product::class, groups: ["product:read"])
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Données invalides"
    )]
    #[OA\Response(
        response: 404,
        description: "Produits non trouvés"
    )]
    #[OA\Tag(name: "Products")]
    #[Security(name: "Bearer")]
    #[Route('/{user}', name: 'app_products_show', methods: ['GET'])]
    public function findByUser(User $user): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $products = $this->productRepository->findBy(['user' => $user]);

        return $this->json(
            $products,
            Response::HTTP_OK,
            [],
            ['groups' => ['product:read', 'user:read', 'preferences:read']]
        );
    }

    #[OA\Response(
        response: 204,
        description: "Produit supprimé"
    )]
    #[OA\Response(
        response: 404,
        description: "Produit non trouvé"
    )]
    #[OA\Tag(name: "Products")]
    #[Security(name: "Bearer")]
    #[Route('/{id}', name: 'app_products_delete', methods: ['DELETE'])]
    public function delete(Product $product): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: "filters",
                    properties: [
                        new OA\Property(property: "type", type: "string", example: "fruits"),
                        new OA\Property(property: "expiresAt", type: "string", format: "date", example: "2025-05-01"),
                        new OA\Property(property: "dietaryPreferences", type: "array", items: new OA\Items(type: "string"), example: ["1f017c88-d919-6e34-bc7f-636d01396ed1", "1f017c88-d919-6ea2-b016-636d01396ed1"]),
                        new OA\Property(property: "maxPrice", type: "number", format: "float", example: 10.0),
                        new OA\Property(property: "minRating", type: "number", format: "float", example: 4.0)
                    ],
                    type: "object"
                )
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Retourne les produits selon les filtres appliqués",
        content: new OA\JsonContent(
            ref: new Model(type: Product::class, groups: ["product:read"])
        )
    )]
    #[OA\Tag(name: "Products")]
    #[Security(name: "Bearer")]
    #[Route('/filter', name: 'app_products_filter', methods: ['GET'])]
    public function getByFilters(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $content = $request->getContent();
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse([
                    'message' => 'Format JSON invalide',
                    'error' => json_last_error_msg(),
                    'content' => $content
                ], Response::HTTP_BAD_REQUEST);
            }

            $filters = $data['filters'] ?? [];

            if (isset($filters['dietaryPreferences']) && !is_array($filters['dietaryPreferences'])) {
                $filters['dietaryPreferences'] = [$filters['dietaryPreferences']];
            }

            $products = $this->productRepository->findProductsByFilters($filters);

            if (empty($products)) {
                return $this->json(
                    ['message' => 'Aucun produit ne correspond aux critères de recherche'],
                    Response::HTTP_OK,
                    [],
                    ['groups' => ['product:read', 'user:read', 'preferences:read']]
                );
            }

            return $this->json(
                $products,
                Response::HTTP_OK,
                [],
                ['groups' => ['product:read', 'user:read', 'preferences:read']]
            );
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Une erreur est survenue lors du filtrage des produits',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
