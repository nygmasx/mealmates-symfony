<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\AvailabilityRepository;
use App\Repository\DietaryPreferencesRepository;
use App\Repository\OrderRepository;
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
        private readonly OrderRepository      $orderRepository,
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
                        new OA\Property(property: "dietaryPreferences", type: "array", items: new OA\Items(type: "string"), example: ["1", "3"]),
                        new OA\Property(property: "maxPrice", type: "number", format: "float", example: 10.0),
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
    #[Route('/filter', name: 'app_products_filter', methods: ['POST'])]
    public function getByFilters(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $filters = $data['filters'] ?? [];

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
    }

    #[OA\Response(
        response: 200,
        description: "Retourne les offres des vendeurs auprès desquels l'acheteur a déjà commandé",
        content: new OA\JsonContent(
            ref: new Model(type: Product::class, groups: ["product:read"])
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Utilisateur non authentifié"
    )]
    #[OA\Tag(name: "Products")]
    #[Security(name: "Bearer")]
    #[Route('/filter/recommander-a-nouveau', name: 'app_products_recommend_again', methods: ['GET'])]
    public function getRecommendAgain(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $profile = $user->getProfile();

        if (!$profile) {
            return new JsonResponse(['message' => 'Profil utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $products = $this->productRepository->findCustomizedProducts($profile);

        return $this->json(
            $products,
            Response::HTTP_OK,
            [],
            ['groups' => ['product:read', 'user:read', 'preferences:read']]
        );
    }

    #[OA\Response(
        response: 200,
        description: "Retourne les produits qui expirent aujourd'hui",
        content: new OA\JsonContent(
            ref: new Model(type: Product::class, groups: ["product:read"])
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Utilisateur non authentifié"
    )]
    #[OA\Tag(name: "Products")]
    #[Security(name: "Bearer")]
    #[Route('/filter/derniere-chance', name: 'app_products_last_chance', methods: ['GET'])]
    public function getLastChance(): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $products = $this->productRepository->findExpiringToday();

        return $this->json(
            $products,
            Response::HTTP_OK,
            [],
            ['groups' => ['product:read', 'user:read', 'preferences:read']]
        );
    }

    #[OA\Response(
        response: 200,
        description: "Retourne uniquement des produits vegan",
        content: new OA\JsonContent(
            ref: new Model(type: Product::class, groups: ["product:read"])
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Utilisateur non authentifié"
    )]
    #[OA\Tag(name: "Products")]
    #[Security(name: "Bearer")]
    #[Route('/filter/ce-soir-je-mange-vegan', name: 'app_products_vegan', methods: ['GET'])]
    public function getVegan(): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $products = $this->productRepository->findVeganProducts();

        return $this->json(
            $products,
            Response::HTTP_OK,
            [],
            ['groups' => ['product:read', 'user:read', 'preferences:read']]
        );
    }

    #[OA\Response(
        response: 200,
        description: "Retourne les produits populaires dans la zone de recherche",
        content: new OA\JsonContent(
            ref: new Model(type: Product::class, groups: ["product:read"])
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Utilisateur non authentifié"
    )]
    #[OA\Tag(name: "Products")]
    #[Security(name: "Bearer")]
    #[Route('/filter/tendances-locales', name: 'app_products_local_trends', methods: ['GET'])]
    public function getLocalTrends(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $latitude = $request->query->get('latitude');
        $longitude = $request->query->get('longitude');

        if ($latitude && $longitude) {
            $products = $this->productRepository->findLocalTrendingProducts((float)$latitude, (float)$longitude);
        } else {
            $userProfile = $user->getProfile();

            if ($userProfile && $userProfile->getLatitude() && $userProfile->getLongitude()) {
                $products = $this->productRepository->findLocalTrendingProducts(
                    $userProfile->getLatitude(),
                    $userProfile->getLongitude()
                );
            } else {
                $products = $this->productRepository->findTrendingProducts();
            }
        }

        return $this->json(
            $products,
            Response::HTTP_OK,
            [],
            ['groups' => ['product:read', 'user:read', 'preferences:read']]
        );
    }

    #[OA\Response(
        response: 200,
        description: "Retourne des recommandations personnalisées",
        content: new OA\JsonContent(
            ref: new Model(type: Product::class, groups: ["product:read"])
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Utilisateur non authentifié"
    )]
    #[OA\Tag(name: "Products")]
    #[Security(name: "Bearer")]
    #[Route('/filter/sur-mesure-pour-vous', name: 'app_products_customized', methods: ['GET'])]
    public function getCustomized(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $products = $this->productRepository->findCustomizedProducts($user);

        return $this->json(
            $products,
            Response::HTTP_OK,
            [],
            ['groups' => ['product:read', 'user:read', 'preferences:read']]
        );
    }

    private function getLocalTrendsProducts(Request $request, User $user): array
    {
        $latitude = $request->query->get('latitude');
        $longitude = $request->query->get('longitude');

        if ($latitude && $longitude) {
            return $this->productRepository->findLocalTrendingProducts((float)$latitude, (float)$longitude);
        }

        $userProfile = $user->getProfile();

        if ($userProfile && $userProfile->getLatitude() && $userProfile->getLongitude()) {
            return $this->productRepository->findLocalTrendingProducts(
                $userProfile->getLatitude(),
                $userProfile->getLongitude()
            );
        }

        return $this->productRepository->findTrendingProducts();
    }
}