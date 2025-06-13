<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Entity\DietaryPreference;
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
        private readonly OrderRepository        $orderRepository,
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

    #[Route('', name: 'app_product_create', methods: ['POST'])]
    #[OA\RequestBody(
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: "title", type: "string"),
                    new OA\Property(property: "quantity", type: "integer"),
                    new OA\Property(property: "expirationDate", type: "string", format: "date"),
                    new OA\Property(property: "isDonation", type: "boolean"),
                    new OA\Property(property: "price", type: "number", format: "float"),
                    new OA\Property(property: "pickupAddress", type: "string"),
                    new OA\Property(property: "availabilities", description: "JSON array of pickup schedule", type: "string"),
                    new OA\Property(property: "isRecurring", type: "boolean"),
                    new OA\Property(property: "recurringFrequency", type: "string", enum: ["daily", "weekly", "monthly"]),
                    new OA\Property(property: "type", description: "Product type", type: "string"),
                    new OA\Property(property: "dietaryTags", description: "JSON array of dietary preference IDs", type: "string"),
                    new OA\Property(
                        property: "images[]",
                        description: "Product images (max 5)",
                        type: "array",
                        items: new OA\Items(type: "string", format: "binary")
                    )
                ],
                type: "object"
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Produit créé avec succès",
        content: new OA\JsonContent(
            ref: new Model(type: Product::class, groups: ["product:read"])
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Données invalides"
    )]
    #[OA\Response(
        response: 401,
        description: "Utilisateur non authentifié"
    )]
    #[OA\Tag(name: "Products")]
    #[Security(name: "Bearer")]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $title = $request->request->get('title');
            $quantity = (int)$request->request->get('quantity', 1);
            $expirationDate = $request->request->get('expirationDate');
            $isDonation = filter_var($request->request->get('isDonation', false), FILTER_VALIDATE_BOOLEAN);
            $price = $isDonation ? null : (float)$request->request->get('price', 0);
            $pickupAddress = $request->request->get('pickupAddress');
            $availabilities = $request->request->get('availabilities');
            $isRecurring = filter_var($request->request->get('isRecurring', false), FILTER_VALIDATE_BOOLEAN);
            $recurringFrequency = $request->request->get('recurringFrequency', 'weekly');
            $dietaryTags = $request->request->get('dietaryTags');
            $type = $request->request->get('type', 'food');

            $errors = [];
            if (empty($title)) {
                $errors['title'] = 'Le titre est requis';
            }
            if ($quantity < 1) {
                $errors['quantity'] = 'La quantité doit être au moins 1';
            }
            if (empty($expirationDate)) {
                $errors['expirationDate'] = 'La date de péremption est requise';
            } else {
                $expDate = \DateTimeImmutable::createFromFormat('Y-m-d', $expirationDate);
                if (!$expDate || $expDate < new \DateTimeImmutable('today')) {
                    $errors['expirationDate'] = 'La date de péremption doit être aujourd\'hui ou ultérieure';
                }
            }
            if (!$isDonation && $price <= 0) {
                $errors['price'] = 'Le prix doit être positif';
            }
            if (empty($pickupAddress)) {
                $errors['pickupAddress'] = 'L\'adresse de retrait est requise';
            }

            if ($availabilities) {
                $scheduleData = json_decode($availabilities, true);
                if (!$scheduleData || !is_array($scheduleData)) {
                    $errors['availabilities'] = 'Format de planning invalide';
                } else {
                    $hasEnabledDay = false;
                    foreach ($scheduleData as $schedule) {
                        if (isset($schedule['isEnabled']) && $schedule['isEnabled']) {
                            $hasEnabledDay = true;
                            break;
                        }
                    }
                    if (!$hasEnabledDay) {
                        $errors['availabilities'] = 'Au moins un créneau de retrait est requis';
                    }
                }
            } else {
                $errors['availabilities'] = 'Le planning de récupération est requis';
            }

            $uploadedFiles = $request->files->get('images', []);
            if (empty($uploadedFiles)) {
                $errors['images'] = 'Au moins une photo est requise';
            }

            if (!empty($errors)) {
                return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $product = new Product();
            $product->setTitle($title);
            $product->setQuantity($quantity);
            $product->setExpiresAt(\DateTimeImmutable::createFromFormat('Y-m-d', $expirationDate));
            $product->setPrice($price);
            $product->setType($type);
            $product->setPickingAddress($pickupAddress);
            $product->setIsRecurring($isRecurring);
            $product->setRecurringFrequency($recurringFrequency);
            $product->setUpdatedAt(new \DateTimeImmutable());
            $product->setUser($user);

            if ($availabilities) {
                $scheduleData = json_decode($availabilities, true);
                $product->setAvailabilities($scheduleData);
            }

            if ($dietaryTags) {
                $dietaryIds = json_decode($dietaryTags, true);
                if (is_array($dietaryIds)) {
                    foreach ($dietaryIds as $prefId) {
                        $pref = $this->entityManager->getRepository(DietaryPreference::class)->find($prefId);
                        if ($pref) {
                            $product->addDietaryPreference($pref);
                        }
                    }
                }
            }

            $imageUrls = [];
            if (!empty($uploadedFiles)) {
                foreach ($uploadedFiles as $index => $file) {
                    if ($index >= 5) break;

                    if ($file && $file->isValid()) {
                        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
                        if (!in_array($file->getMimeType(), $allowedMimes)) {
                            continue;
                        }

                        if ($file->getSize() > 5 * 1024 * 1024) {
                            continue;
                        }

                        $filename = uniqid() . '.' . $file->guessExtension();

                        $uploadDir = $this->getParameter('uploads_directory') . '/products';
                        $file->move($uploadDir, $filename);

                        $imageUrls[] = '/uploads/products/' . $filename;
                    }
                }
            }

            $product->setImages(json_encode($imageUrls));

            $violations = $validator->validate($product);
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()] = $violation->getMessage();
                }
                return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($product);
            $this->entityManager->flush();

            return $this->json([
                'id' => $product->getId(),
                'message' => 'Produit créé avec succès'
            ], Response::HTTP_CREATED, [], ['groups' => ['product:read']]);

        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Une erreur est survenue lors de la création du produit',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'app_product_update', methods: ['PUT'])]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "title", type: "string"),
                new OA\Property(property: "expiresAt", type: "string", format: "date"),
                new OA\Property(property: "dietaryPreferences", type: "array", items: new OA\Items(type: "string")),
                new OA\Property(property: "price", type: "float"),
                new OA\Property(property: "type", type: "string")
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Produit mis à jour",
        content: new OA\JsonContent(
            ref: new Model(type: Product::class, groups: ["product:read"])
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Produit non trouvé"
    )]
    #[OA\Tag(name: "Products")]
    #[Security(name: "Bearer")]
    public function update(Request $request, Product $product): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        $product->setTitle($data['title']);
        $product->setExpiresAt(new \DateTimeImmutable($data['expiresAt']));
        $product->setPrice($data['price']);
        $product->setType($data['type']);

        $product->getDietaryPreferences()->clear();
        if (!empty($data['dietaryPreferences'])) {
            foreach ($data['dietaryPreferences'] as $prefId) {
                $pref = $this->entityManager->getRepository(DietaryPreference::class)->find($prefId);
                if ($pref) {
                    $product->addDietaryPreference($pref);
                }
            }
        }

        $this->entityManager->flush();

        return $this->json($product, Response::HTTP_OK, [], ['groups' => ['product:read']]);
    }

    #[Route('/show/{id}', name: 'app_product_show_by_id', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: "Retourne un produit",
        content: new OA\JsonContent(
            ref: new Model(type: Product::class, groups: ["product:read"])
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Produit non trouvé"
    )]
    #[OA\Tag(name: "Products")]
    #[Security(name: "Bearer")]
    public function show(Product $product): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json(
            $product,
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

        $products = $this->productRepository->findCustomizedProducts($user->getProfile());

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
