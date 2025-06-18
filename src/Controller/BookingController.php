<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\ProductRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[Route('/bookings')]
final class BookingController extends AbstractController
{
    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly ProductRepository $productRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationService $notificationService,
        private readonly SerializerInterface $serializer
    ) {}

    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "product_id", type: "string"),
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Réservation créée avec succès",
        content: new OA\JsonContent(ref: new Model(type: Booking::class, groups: ["bookings:read"]))
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Utilisateur non authentifié")]
    #[OA\Tag(name: "Bookings")]
    #[Security(name: "Bearer")]
    #[Route('', name: 'app_bookings_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['product_id']) || empty($data['product_id'])) {
            return new JsonResponse(['message' => 'Un produit doit être sélectionné'], Response::HTTP_BAD_REQUEST);
        }

        try {

            $product = $this->productRepository->find($data['product_id']);

            if (!$product) {
                return new JsonResponse(['message' => 'Produit introuvable'], Response::HTTP_BAD_REQUEST);
            }

            if ($product->getUser() === $user) {
                return new JsonResponse([
                    'message' => 'Vous ne pouvez pas réserver vos propres produits'
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($this->hasActiveBooking($product, $user)) {
                return new JsonResponse([
                    'message' => "Vous avez déjà une réservation active pour '{$product->getTitle()}'"
                ], Response::HTTP_BAD_REQUEST);
            }

            $totalPrice = $product->getPrice();

            $booking = new Booking();
            $booking->setUser($user)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setIsConfirmed(false)
                ->setIsOutdated(false)
                ->setTotalPrice($totalPrice)
                ->setProduct($product);

            $this->entityManager->persist($booking);
            $this->entityManager->flush();

            $this->notificationService->sendBookingOpenedMail($booking);
            $this->notificationService->sendBookingOpenedToSellerMail($booking);

            return new JsonResponse([
                'id' => $booking->getId(),
                'product' => [
                    'id' => $booking->getProduct()->getId(),
                    'title' => $booking->getProduct()->getTitle(),
                    'price' => $booking->getProduct()->getPrice()
                ],
                'total_price' => $booking->getTotalPrice(),
                'created_at' => $booking->getCreatedAt()->format('c'),
                'is_confirmed' => $booking->isConfirmed(),
                'is_outdated' => $booking->isOutdated(),
                'message' => 'Réservation créée avec succès. En attente de confirmation du vendeur.'
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Erreur lors de la création de la réservation: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Response(
        response: 200,
        description: "Liste des réservations de l'utilisateur"
    )]
    #[OA\Tag(name: "Bookings")]
    #[Security(name: "Bearer")]
    #[Route('/my', name: 'app_bookings_my', methods: ['GET'])]
    public function myBookings(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $myBookings = $this->bookingRepository->findBy(['user' => $user]);

        $sellerBookings = $this->bookingRepository->createQueryBuilder('b')
            ->join('b.product', 'p')
            ->where('p.user = :seller')
            ->setParameter('seller', $user)
            ->getQuery()
            ->getResult();

        $allBookings = array_merge($myBookings, $sellerBookings);

        $bookingsData = array_map(function($booking) use ($user) {
            $isSellerView = $this->isUserSeller($booking, $user);

            return [
                'id' => $booking->getId(),
                'product' => [
                    'id' => $booking->getProduct()->getId(),
                    'title' => $booking->getProduct()->getTitle(),
                    'price' => $booking->getProduct()->getPrice()
                ],
                'buyer' => [
                    'id' => $booking->getUser()->getId(),
                    'name' => $booking->getUser()->getFirstName() ?? $booking->getUser()->getLastName()
                ],
                'total_price' => $booking->getTotalPrice(),
                'created_at' => $booking->getCreatedAt()->format('c'),
                'is_confirmed' => $booking->isConfirmed(),
                'is_outdated' => $booking->isOutdated(),
                'confirmed_at' => $booking->getConfirmedAt()?->format('c'),
                'outdated_at' => $booking->getOutdatedAt()?->format('c'),
                'role' => $isSellerView ? 'seller' : 'buyer',
                'can_confirm' => $isSellerView && !$booking->isConfirmed() && !$booking->isOutdated(),
                'can_reject' => $isSellerView && !$booking->isConfirmed() && !$booking->isOutdated()
            ];
        }, $allBookings);

        return new JsonResponse($bookingsData);
    }

    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "action", type: "string", enum: ["confirm", "reject"]),
            ],
            type: "object"
        )
    )]
    #[OA\Response(response: 200, description: "Réservation mise à jour")]
    #[OA\Response(response: 400, description: "Action invalide")]
    #[OA\Response(response: 403, description: "Accès refusé")]
    #[OA\Response(response: 404, description: "Réservation non trouvée")]
    #[OA\Tag(name: "Bookings")]
    #[Security(name: "Bearer")]
    #[Route('/{id}/respond', name: 'app_bookings_respond', methods: ['PATCH'])]
    public function respond(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $booking = $this->bookingRepository->find($id);
        if (!$booking) {
            return new JsonResponse(['message' => 'Réservation non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isUserSeller($booking, $user)) {
            return new JsonResponse([
                'message' => 'Seul le vendeur peut répondre à cette réservation'
            ], Response::HTTP_FORBIDDEN);
        }

        if ($booking->isConfirmed() || $booking->isOutdated()) {
            return new JsonResponse([
                'message' => 'Cette réservation a déjà été traitée'
            ], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        $action = $data['action'] ?? '';

        try {
            if ($action === 'confirm') {
                $booking->setIsConfirmed(true)
                    ->setConfirmedAt(new \DateTimeImmutable());

                $this->entityManager->flush();
                $this->notificationService->sendBookingConfirmationNotification($booking);

                $message = 'Réservation confirmée avec succès';

            } elseif ($action === 'reject') {
                $booking->setIsOutdated(true)
                    ->setOutdatedAt(new \DateTimeImmutable());

                $this->entityManager->flush();
                $this->notificationService->sendBookingRejectionNotification($booking);

                $message = 'Réservation rejetée';

            } else {
                return new JsonResponse([
                    'message' => 'Action invalide. Utilisez "confirm" ou "reject"'
                ], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse([
                'id' => $booking->getId(),
                'is_confirmed' => $booking->isConfirmed(),
                'is_outdated' => $booking->isOutdated(),
                'confirmed_at' => $booking->getConfirmedAt()?->format('c'),
                'outdated_at' => $booking->getOutdatedAt()?->format('c'),
                'message' => $message
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Erreur lors du traitement: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Response(
        response: 200,
        description: "Réservations en attente pour le vendeur"
    )]
    #[OA\Tag(name: "Bookings")]
    #[Security(name: "Bearer")]
    #[Route('/pending', name: 'app_bookings_pending', methods: ['GET'])]
    public function pendingBookings(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $pendingBookings = $this->bookingRepository->createQueryBuilder('b')
            ->join('b.product', 'p')
            ->where('p.user = :seller')
            ->andWhere('b.isConfirmed = false')
            ->andWhere('b.isOutdated = false')
            ->setParameter('seller', $user)
            ->getQuery()
            ->getResult();

        $bookingsData = array_map(function($booking) {
            return [
                'id' => $booking->getId(),
                'product' => [
                    'id' => $booking->getProduct()->getId(),
                    'title' => $booking->getProduct()->getTitle(),
                    'price' => $booking->getProduct()->getPrice()
                ],
                'buyer' => [
                    'id' => $booking->getUser()->getId(),
                    'name' => $booking->getUser()->getFirstName() ?? $booking->getUser()->getLastName()
                ],
                'total_price' => $booking->getTotalPrice(),
                'created_at' => $booking->getCreatedAt()->format('c'),
                'hours_since_created' => $this->getHoursSinceCreation($booking)
            ];
        }, $pendingBookings);

        return new JsonResponse($bookingsData);
    }

    #[OA\Response(response: 200, description: "Réservation annulée")]
    #[OA\Response(response: 400, description: "Impossible d'annuler")]
    #[OA\Response(response: 403, description: "Accès refusé")]
    #[OA\Response(response: 404, description: "Réservation non trouvée")]
    #[OA\Tag(name: "Bookings")]
    #[Security(name: "Bearer")]
    #[Route('/{id}/cancel', name: 'app_bookings_cancel', methods: ['PATCH'])]
    public function cancel(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $booking = $this->bookingRepository->find($id);
        if (!$booking) {
            return new JsonResponse(['message' => 'Réservation non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur peut annuler (acheteur ou vendeur)
        if ($booking->getUser() !== $user && !$this->isUserSeller($booking, $user)) {
            return new JsonResponse([
                'message' => 'Vous ne pouvez pas annuler cette réservation'
            ], Response::HTTP_FORBIDDEN);
        }

        // Vérifier que la réservation peut être annulée
        if ($booking->isOutdated()) {
            return new JsonResponse([
                'message' => 'Cette réservation est déjà annulée ou expirée'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $booking->setIsOutdated(true)
                ->setOutdatedAt(new \DateTimeImmutable());

            $this->entityManager->flush();

            // Notifier l'autre partie
            $this->notificationService->sendBookingCancellationNotification($booking);

            return new JsonResponse([
                'id' => $booking->getId(),
                'is_outdated' => $booking->isOutdated(),
                'outdated_at' => $booking->getOutdatedAt()?->format('c'),
                'message' => 'Réservation annulée avec succès'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Erreur lors de l\'annulation: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Response(response: 200, description: "Détails de la réservation")]
    #[OA\Response(response: 404, description: "Réservation non trouvée")]
    #[OA\Tag(name: "Bookings")]
    #[Security(name: "Bearer")]
    #[Route('/{id}', name: 'app_bookings_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $booking = $this->bookingRepository->find($id);
        if (!$booking) {
            return new JsonResponse(['message' => 'Réservation non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur peut voir cette réservation
        if ($booking->getUser() !== $user && !$this->isUserSeller($booking, $user)) {
            return new JsonResponse([
                'message' => 'Accès non autorisé à cette réservation'
            ], Response::HTTP_FORBIDDEN);
        }

        $isSellerView = $this->isUserSeller($booking, $user);
        $seller = $booking->getProduct()->getUser();

        return new JsonResponse([
            'id' => $booking->getId(),
            'product' => [
                'id' => $booking->getProduct()->getId(),
                'title' => $booking->getProduct()->getTitle(),
                'type' => $booking->getProduct()->getType(),
                'price' => $booking->getProduct()->getPrice(),
                'expires_at' => $booking->getProduct()->getExpiresAt()?->format('c')
            ],
            'buyer' => [
                'id' => $booking->getUser()->getId(),
                'name' => $booking->getUser()->getFirstName() ?? $booking->getUser()->getLastName(),
                'email' => $isSellerView ? $booking->getUser()->getEmail() : null
            ],
            'seller' => [
                'id' => $seller->getId(),
                'name' => $seller->getFirstName() ?? $seller->getLastName(),
                'email' => !$isSellerView ? $seller->getEmail() : null
            ],
            'total_price' => $booking->getTotalPrice(),
            'created_at' => $booking->getCreatedAt()->format('c'),
            'is_confirmed' => $booking->isConfirmed(),
            'is_outdated' => $booking->isOutdated(),
            'confirmed_at' => $booking->getConfirmedAt()?->format('c'),
            'outdated_at' => $booking->getOutdatedAt()?->format('c'),
            'role' => $isSellerView ? 'seller' : 'buyer',
            'can_confirm' => $isSellerView && !$booking->isConfirmed() && !$booking->isOutdated(),
            'can_reject' => $isSellerView && !$booking->isConfirmed() && !$booking->isOutdated(),
            'can_cancel' => !$booking->isOutdated(),
            'hours_since_created' => $this->getHoursSinceCreation($booking)
        ]);
    }


    private function hasActiveBooking(Product $product, User $buyer): bool
    {
        foreach ($product->getBookings() as $booking) {
            if ($booking->getUser() === $buyer &&
                !$booking->isConfirmed() &&
                !$booking->isOutdated()) {
                return true;
            }
        }
        return false;
    }

    private function isUserSeller(Booking $booking, User $user): bool
    {
        return $booking->getProduct()->getUser() === $user;
    }

    private function getHoursSinceCreation(Booking $booking): int
    {
        $now = new \DateTimeImmutable();
        $interval = $now->diff($booking->getCreatedAt());
        return $interval->h + ($interval->days * 24);
    }
}
