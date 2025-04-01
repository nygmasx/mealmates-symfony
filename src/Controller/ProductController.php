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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;

#[Route('/product')]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository            $productRepository,
        private readonly ValidatorInterface           $validator,
        private readonly EntityManagerInterface       $entityManager,
        private readonly DietaryPreferencesRepository $dietaryPreferencesRepository,
        private readonly AvailabilityRepository       $availabilityRepository,
        private readonly SerializerInterface          $serializer
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
        response: 200,
        description: "Retourne les produits de l'utilisateur connecté",
        content: new OA\JsonContent(
            ref: new Model(type: Product::class, groups: ["product:read"])
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Produits non trouvés"
    )]
    #[OA\Tag(name: "Products")]
    #[Security(name: "Bearer")]
    #[Route('/me', name: 'app_products_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $products = $this->productRepository->findBy(['user' => $user]);

        if (!$products) {
            return new JsonResponse(['message' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(
            $products,
            Response::HTTP_OK,
            [],
            ['groups' => ['product:read', 'user:read']]
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
}
