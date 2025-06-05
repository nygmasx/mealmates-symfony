<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\ProfileRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/review')]
final class ReviewController extends AbstractController
{
    public function __construct(
        private readonly ProfileRepository $profileRepository,
        private readonly ReviewRepository $reviewRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[OA\Response(
        response: 200,
        description: "Retourne une review",
        content: new OA\JsonContent(
            ref: new Model(type: Review::class, groups: ["review:read"])
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Review non trouvée"
    )]
    #[OA\Tag(name: "Reviews")]
    #[Security(name: "Bearer")]
    #[Route('/{id}', name: 'app_review_show', methods: ['GET'])]
    public function show(Review $review): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json(
            $review,
            Response::HTTP_OK,
            [],
            ['groups' => ['review:read', 'user:read', 'profile:read']]
        );
    }

    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "reviewedUserProfile", type: "string", example: "profile-uuid"),
                new OA\Property(property: "star", type: "integer", minimum: 1, maximum: 5, example: 4)
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Review créée avec succès",
        content: new OA\JsonContent(
            ref: new Model(type: Review::class, groups: ["review:read"])
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
    #[OA\Tag(name: "Reviews")]
    #[Security(name: "Bearer")]
    #[Route('/', name: 'app_review_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        $profileId = $data['reviewedUserProfile'] ?? null;
        if (!$profileId) {
            return new JsonResponse(['message' => 'ID du profil requis'], Response::HTTP_BAD_REQUEST);
        }

        $profile = $this->profileRepository->find($profileId);
        if (!$profile) {
            return new JsonResponse(['message' => 'Profil non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if ($profile->getUser() === $user) {
            return new JsonResponse(['message' => 'Vous ne pouvez pas vous évaluer vous-même'], Response::HTTP_BAD_REQUEST);
        }

        $existingReview = $this->reviewRepository->findOneBy([
            'author' => $user,
            'reviewedUserProfile' => $profile
        ]);

        if ($existingReview) {
            return new JsonResponse(['message' => 'Vous avez déjà évalué ce profil'], Response::HTTP_BAD_REQUEST);
        }

        $star = $data['star'] ?? null;
        if (!is_int($star) || $star < 1 || $star > 5) {
            return new JsonResponse(['message' => 'La note doit être comprise entre 1 et 5'], Response::HTTP_BAD_REQUEST);
        }

        $review = new Review();
        $review->setAuthor($user);
        $review->setReviewedUserProfile($profile);
        $review->setStar($star);

        $this->entityManager->persist($review);
        $this->entityManager->flush();

        return $this->json(
            $review,
            Response::HTTP_CREATED,
            [],
            ['groups' => ['review:read', 'user:read', 'profile:read']]
        );
    }

    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "star", type: "integer", minimum: 1, maximum: 5, example: 4)
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Review modifiée avec succès",
        content: new OA\JsonContent(
            ref: new Model(type: Review::class, groups: ["review:read"])
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Données invalides"
    )]
    #[OA\Response(
        response: 403,
        description: "Accès refusé"
    )]
    #[OA\Response(
        response: 404,
        description: "Review non trouvée"
    )]
    #[OA\Tag(name: "Reviews")]
    #[Security(name: "Bearer")]
    #[Route('/{id}', name: 'app_review_update', methods: ['PUT'])]
    public function update(Request $request, Review $review): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if ($review->getAuthor() !== $user) {
            return new JsonResponse(['message' => 'Vous ne pouvez modifier que vos propres reviews'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['star'])) {
            $star = $data['star'];
            if (!is_int($star) || $star < 1 || $star > 5) {
                return new JsonResponse(['message' => 'La note doit être comprise entre 1 et 5'], Response::HTTP_BAD_REQUEST);
            }
            $review->setStar($star);
        }

        $this->entityManager->flush();

        return $this->json(
            $review,
            Response::HTTP_OK,
            [],
            ['groups' => ['review:read', 'user:read', 'profile:read']]
        );
    }

    #[OA\Response(
        response: 204,
        description: "Review supprimée"
    )]
    #[OA\Response(
        response: 403,
        description: "Accès refusé"
    )]
    #[OA\Response(
        response: 404,
        description: "Review non trouvée"
    )]
    #[OA\Tag(name: "Reviews")]
    #[Security(name: "Bearer")]
    #[Route('/{id}', name: 'app_review_delete', methods: ['DELETE'])]
    public function delete(Review $review): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if ($review->getAuthor() !== $user) {
            return new JsonResponse(['message' => 'Vous ne pouvez supprimer que vos propres reviews'], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($review);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[OA\Response(
        response: 200,
        description: "Retourne toutes les reviews d'un profil",
        content: new OA\JsonContent(
            ref: new Model(type: Review::class, groups: ["review:read"])
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Profil non trouvé"
    )]
    #[OA\Tag(name: "Reviews")]
    #[Security(name: "Bearer")]
    #[Route('/profile/{id}', name: 'app_review_by_profile', methods: ['GET'])]
    public function findByProfile(string $id): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $profile = $this->profileRepository->find($id);
        if (!$profile) {
            return new JsonResponse(['message' => 'Profil non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $reviews = $this->reviewRepository->findBy(['reviewedUserProfile' => $profile]);

        return $this->json(
            $reviews,
            Response::HTTP_OK,
            [],
            ['groups' => ['review:read', 'user:read', 'profile:read']]
        );
    }

    #[OA\Response(
        response: 200,
        description: "Retourne toutes les reviews créées par un utilisateur",
        content: new OA\JsonContent(
            ref: new Model(type: Review::class, groups: ["review:read"])
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Utilisateur non trouvé"
    )]
    #[OA\Tag(name: "Reviews")]
    #[Security(name: "Bearer")]
    #[Route('/user/{id}', name: 'app_review_by_user', methods: ['GET'])]
    public function findByUser(string $id): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $reviews = $this->reviewRepository->findBy(['author' => $user]);

        return $this->json(
            $reviews,
            Response::HTTP_OK,
            [],
            ['groups' => ['review:read', 'user:read', 'profile:read']]
        );
    }
}