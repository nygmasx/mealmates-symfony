<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\Booking;
use App\Entity\User;
use App\Repository\ReviewRepository;
use App\Repository\BookingRepository;
use App\Repository\UserRepository;
use App\Security\Voter\ReviewVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Security;
use Nelmio\ApiDocBundle\Attribute\Model;

#[Route('/reviews')]
final class ReviewController extends AbstractController
{
    public function __construct(
        private readonly ReviewRepository $reviewRepository,
        private readonly BookingRepository $bookingRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "booking_id", type: "string"),
                new OA\Property(property: "review_type", type: "string", enum: ["buyer_to_seller", "seller_to_buyer"]),
                new OA\Property(property: "overall_rating", type: "integer", minimum: 1, maximum: 5),
                new OA\Property(property: "product_quality_rating", type: "integer", minimum: 1, maximum: 5),
                new OA\Property(property: "punctuality_rating", type: "integer", minimum: 1, maximum: 5),
                new OA\Property(property: "friendliness_rating", type: "integer", minimum: 1, maximum: 5),
                new OA\Property(property: "communication_rating", type: "integer", minimum: 1, maximum: 5),
                new OA\Property(property: "reliability_rating", type: "integer", minimum: 1, maximum: 5),
                new OA\Property(property: "comment", type: "string")
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Avis créé avec succès",
        content: new OA\JsonContent(ref: new Model(type: Review::class, groups: ["review:read"]))
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Utilisateur non authentifié")]
    #[OA\Response(response: 403, description: "Accès refusé")]
    #[OA\Tag(name: "Reviews")]
    #[Security(name: "Bearer")]
    #[Route('', name: 'app_reviews_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $this->denyAccessUnlessGranted(ReviewVoter::CREATE);

        $data = json_decode($request->getContent(), true);

        if (!isset($data['booking_id']) || !isset($data['review_type']) || !isset($data['overall_rating'])) {
            return new JsonResponse(['message' => 'Champs requis manquants'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $booking = $this->bookingRepository->find($data['booking_id']);
            if (!$booking) {
                return new JsonResponse(['message' => 'Réservation non trouvée'], Response::HTTP_NOT_FOUND);
            }

            if (!$booking->isDelivered()) {
                return new JsonResponse(['message' => 'La transaction doit être terminée avant de laisser un avis'], Response::HTTP_BAD_REQUEST);
            }

            $reviewType = $data['review_type'];
            $isBuyer = $booking->getUser() === $user;
            $isSeller = $booking->getProduct()->getUser() === $user;

            if (!$isBuyer && !$isSeller) {
                return new JsonResponse(['message' => 'Vous ne pouvez pas évaluer cette transaction'], Response::HTTP_FORBIDDEN);
            }

            if ($reviewType === 'buyer_to_seller' && !$isBuyer) {
                return new JsonResponse(['message' => 'Seul l\'acheteur peut évaluer le vendeur'], Response::HTTP_FORBIDDEN);
            }

            if ($reviewType === 'seller_to_buyer' && !$isSeller) {
                return new JsonResponse(['message' => 'Seul le vendeur peut évaluer l\'acheteur'], Response::HTTP_FORBIDDEN);
            }

            $existingReview = $this->reviewRepository->findByBookingAndType($booking, $reviewType);
            if ($existingReview) {
                return new JsonResponse(['message' => 'Vous avez déjà laissé un avis pour cette transaction'], Response::HTTP_BAD_REQUEST);
            }

            $reviewedUser = $reviewType === 'buyer_to_seller' ? $booking->getProduct()->getUser() : $booking->getUser();

            $review = new Review();
            $review->setAuthor($user)
                ->setReviewedUser($reviewedUser)
                ->setBooking($booking)
                ->setReviewType($reviewType)
                ->setOverallRating($data['overall_rating'])
                ->setCreatedAt(new \DateTimeImmutable())
                ->setIsVisible(true);

            if (isset($data['product_quality_rating'])) {
                $review->setProductQualityRating($data['product_quality_rating']);
            }
            if (isset($data['punctuality_rating'])) {
                $review->setPunctualityRating($data['punctuality_rating']);
            }
            if (isset($data['friendliness_rating'])) {
                $review->setFriendlinessRating($data['friendliness_rating']);
            }
            if (isset($data['communication_rating'])) {
                $review->setCommunicationRating($data['communication_rating']);
            }
            if (isset($data['reliability_rating'])) {
                $review->setReliabilityRating($data['reliability_rating']);
            }
            if (isset($data['comment'])) {
                $review->setComment($data['comment']);
            }

            $this->entityManager->persist($review);

            if ($reviewType === 'buyer_to_seller') {
                $booking->setBuyerReviewLeft(true);
            } else {
                $booking->setSellerReviewLeft(true);
            }

            $this->entityManager->flush();

            return new JsonResponse([
                'id' => $review->getId(),
                'review_type' => $review->getReviewType(),
                'overall_rating' => $review->getOverallRating(),
                'product_quality_rating' => $review->getProductQualityRating(),
                'punctuality_rating' => $review->getPunctualityRating(),
                'friendliness_rating' => $review->getFriendlinessRating(),
                'communication_rating' => $review->getCommunicationRating(),
                'reliability_rating' => $review->getReliabilityRating(),
                'comment' => $review->getComment(),
                'created_at' => $review->getCreatedAt()->format('c'),
                'author' => [
                    'id' => $review->getAuthor()->getId(),
                    'name' => $review->getAuthor()->getFirstName() . ' ' . $review->getAuthor()->getLastName()
                ],
                'reviewed_user' => [
                    'id' => $review->getReviewedUser()->getId(),
                    'name' => $review->getReviewedUser()->getFirstName() . ' ' . $review->getReviewedUser()->getLastName()
                ],
                'message' => 'Avis créé avec succès'
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Erreur lors de la création de l\'avis: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Response(
        response: 200,
        description: "Liste des avis pour un utilisateur",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: Review::class, groups: ["review:read"]))
        )
    )]
    #[OA\Response(response: 404, description: "Utilisateur non trouvé")]
    #[OA\Tag(name: "Reviews")]
    #[Route('/user/{id}', name: 'app_reviews_user', methods: ['GET'])]
    public function getUserReviews(string $id): JsonResponse
    {
        try {
            $user = $this->userRepository->find($id);
            if (!$user) {
                return new JsonResponse(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
            }

            $reviews = $this->reviewRepository->findVisibleReviewsForUser($user);

            $reviewsData = array_map(function ($review) {
                return [
                    'id' => $review->getId(),
                    'review_type' => $review->getReviewType(),
                    'overall_rating' => $review->getOverallRating(),
                    'product_quality_rating' => $review->getProductQualityRating(),
                    'punctuality_rating' => $review->getPunctualityRating(),
                    'friendliness_rating' => $review->getFriendlinessRating(),
                    'communication_rating' => $review->getCommunicationRating(),
                    'reliability_rating' => $review->getReliabilityRating(),
                    'comment' => $review->getComment(),
                    'created_at' => $review->getCreatedAt()->format('c'),
                    'author' => [
                        'id' => $review->getAuthor()->getId(),
                        'name' => $review->getAuthor()->getFirstName() . ' ' . $review->getAuthor()->getLastName()
                    ],
                    'booking' => [
                        'id' => $review->getBooking()->getId(),
                        'product_title' => $review->getBooking()->getProduct()->getTitle()
                    ]
                ];
            }, $reviews);

            return new JsonResponse($reviewsData);

        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Erreur lors de la récupération des avis: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Response(
        response: 200,
        description: "Statistiques d'avis pour un utilisateur",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "averageOverallRating", type: "number"),
                new OA\Property(property: "totalReviews", type: "integer"),
                new OA\Property(property: "averageProductQuality", type: "number"),
                new OA\Property(property: "averagePunctuality", type: "number"),
                new OA\Property(property: "averageFriendliness", type: "number"),
                new OA\Property(property: "averageCommunication", type: "number"),
                new OA\Property(property: "averageReliability", type: "number")
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Utilisateur non trouvé")]
    #[OA\Tag(name: "Reviews")]
    #[Route('/user/{id}/stats', name: 'app_reviews_user_stats', methods: ['GET'])]
    public function getUserReviewStats(string $id): JsonResponse
    {
        try {
            $user = $this->userRepository->find($id);
            if (!$user) {
                return new JsonResponse(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
            }

            $stats = $this->reviewRepository->getAverageRatingsForUser($user);

            return new JsonResponse($stats);

        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Erreur lors de la récupération des statistiques: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Response(
        response: 200,
        description: "Statut des avis pour une réservation",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "booking_id", type: "string"),
                new OA\Property(property: "can_review_seller", type: "boolean"),
                new OA\Property(property: "can_review_buyer", type: "boolean"),
                new OA\Property(property: "buyer_review_left", type: "boolean"),
                new OA\Property(property: "seller_review_left", type: "boolean"),
                new OA\Property(property: "buyer_review", type: "object"),
                new OA\Property(property: "seller_review", type: "object")
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Réservation non trouvée")]
    #[OA\Response(response: 401, description: "Utilisateur non authentifié")]
    #[OA\Tag(name: "Reviews")]
    #[Security(name: "Bearer")]
    #[Route('/booking/{id}/status', name: 'app_reviews_booking_status', methods: ['GET'])]
    public function getBookingReviewStatus(string $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $booking = $this->bookingRepository->find($id);
            if (!$booking) {
                return new JsonResponse(['message' => 'Réservation non trouvée'], Response::HTTP_NOT_FOUND);
            }

            $isBuyer = $booking->getUser() === $user;
            $isSeller = $booking->getProduct()->getUser() === $user;

            if (!$isBuyer && !$isSeller) {
                return new JsonResponse(['message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
            }

            $buyerReview = $this->reviewRepository->findByBookingAndType($booking, 'buyer_to_seller');
            $sellerReview = $this->reviewRepository->findByBookingAndType($booking, 'seller_to_buyer');

            $canReviewSeller = $isBuyer && $booking->isDelivered() && !$buyerReview;
            $canReviewBuyer = $isSeller && $booking->isDelivered() && !$sellerReview;

            $response = [
                'booking_id' => $booking->getId(),
                'can_review_seller' => $canReviewSeller,
                'can_review_buyer' => $canReviewBuyer,
                'buyer_review_left' => $buyerReview !== null,
                'seller_review_left' => $sellerReview !== null,
                'buyer_review' => null,
                'seller_review' => null
            ];

            if ($buyerReview) {
                $response['buyer_review'] = [
                    'id' => $buyerReview->getId(),
                    'overall_rating' => $buyerReview->getOverallRating(),
                    'comment' => $buyerReview->getComment(),
                    'created_at' => $buyerReview->getCreatedAt()->format('c')
                ];
            }

            if ($sellerReview) {
                $response['seller_review'] = [
                    'id' => $sellerReview->getId(),
                    'overall_rating' => $sellerReview->getOverallRating(),
                    'comment' => $sellerReview->getComment(),
                    'created_at' => $sellerReview->getCreatedAt()->format('c')
                ];
            }

            return new JsonResponse($response);

        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Erreur lors de la récupération du statut: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Response(response: 200, description: "Avis récupéré")]
    #[OA\Response(response: 404, description: "Avis non trouvé")]
    #[OA\Tag(name: "Reviews")]
    #[Route('/{id}', name: 'app_reviews_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        try {
            $review = $this->reviewRepository->find($id);
            if (!$review) {
                return new JsonResponse(['message' => 'Avis non trouvé'], Response::HTTP_NOT_FOUND);
            }

            $this->denyAccessUnlessGranted(ReviewVoter::VIEW, $review);

            return new JsonResponse([
                'id' => $review->getId(),
                'review_type' => $review->getReviewType(),
                'overall_rating' => $review->getOverallRating(),
                'product_quality_rating' => $review->getProductQualityRating(),
                'punctuality_rating' => $review->getPunctualityRating(),
                'friendliness_rating' => $review->getFriendlinessRating(),
                'communication_rating' => $review->getCommunicationRating(),
                'reliability_rating' => $review->getReliabilityRating(),
                'comment' => $review->getComment(),
                'created_at' => $review->getCreatedAt()->format('c'),
                'author' => [
                    'id' => $review->getAuthor()->getId(),
                    'name' => $review->getAuthor()->getFirstName() . ' ' . $review->getAuthor()->getLastName()
                ],
                'reviewed_user' => [
                    'id' => $review->getReviewedUser()->getId(),
                    'name' => $review->getReviewedUser()->getFirstName() . ' ' . $review->getReviewedUser()->getLastName()
                ],
                'booking' => [
                    'id' => $review->getBooking()->getId(),
                    'product_title' => $review->getBooking()->getProduct()->getTitle()
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Erreur lors de la récupération de l\'avis: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Response(
        response: 200,
        description: "Liste des avis nécessitant une modération"
    )]
    #[OA\Tag(name: "Reviews")]
    #[Security(name: "Bearer")]
    #[Route('/moderation', name: 'app_reviews_moderation', methods: ['GET'])]
    public function getReviewsNeedingModeration(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $this->denyAccessUnlessGranted(ReviewVoter::MODERATE);

        try {
            $reviews = $this->reviewRepository->findReviewsNeedingModeration();

            $reviewsData = array_map(function ($review) {
                return [
                    'id' => $review->getId(),
                    'review_type' => $review->getReviewType(),
                    'overall_rating' => $review->getOverallRating(),
                    'comment' => $review->getComment(),
                    'created_at' => $review->getCreatedAt()->format('c'),
                    'author' => [
                        'id' => $review->getAuthor()->getId(),
                        'name' => $review->getAuthor()->getFirstName() . ' ' . $review->getAuthor()->getLastName()
                    ],
                    'reviewed_user' => [
                        'id' => $review->getReviewedUser()->getId(),
                        'name' => $review->getReviewedUser()->getFirstName() . ' ' . $review->getReviewedUser()->getLastName()
                    ]
                ];
            }, $reviews);

            return new JsonResponse($reviewsData);

        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Erreur lors de la récupération des avis: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}