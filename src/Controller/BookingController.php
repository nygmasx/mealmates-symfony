<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Chat;
use App\Entity\Product;
use App\Entity\QrValidationToken;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\ProductRepository;
use App\Repository\QrValidationTokenRepository;
use App\Security\Voter\BookingVoter;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[Route('/bookings')]
final class BookingController extends AbstractController
{
    public function __construct(
        private readonly BookingRepository      $bookingRepository,
        private readonly ProductRepository      $productRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationService    $notificationService,
        private readonly QrValidationTokenRepository $qrValidationTokenRepository,
    )
    {
    }

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
                ->setIsPaid(false)
                ->setTotalPrice($totalPrice)
                ->setProduct($product);

            $chat = new Chat();
            $chat->setCreatedAt(new \DateTimeImmutable())
                ->setRelatedProduct($product)
                ->setUserOne($user)
                ->setUserTwo($product->getUser())
                ->setBooking($booking);

            $booking->setChat($chat);

            $this->entityManager->persist($booking);
            $this->entityManager->persist($chat);
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
                'is_delivered' => $booking->isDelivered(),
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

        $allBookings = $this->bookingRepository->findAllBookingsForUser($user);

        $bookingsData = array_map(function ($booking) use ($user) {
            $isSellerView = $booking->getProduct()->getUser() === $user;

            return [
                'id' => $booking->getId(),
                'product' => [
                    'id' => $booking->getProduct()->getId(),
                    'title' => $booking->getProduct()->getTitle(),
                    'price' => $booking->getProduct()->getPrice()
                ],
                'buyer' => [
                    'id' => $booking->getUser()->getId(),
                    'name' => $booking->getUser()->getFirstName() . ' ' . $booking->getUser()->getLastName(),
                    'email' => $booking->getUser()->getEmail(),
                ],
                'total_price' => $booking->getTotalPrice(),
                'created_at' => $booking->getCreatedAt()->format('c'),
                'is_confirmed' => $booking->isConfirmed(),
                'is_delivered' => $booking->isDelivered(),
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
    public function respond(Booking $booking, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $this->denyAccessUnlessGranted(BookingVoter::RESPOND, $booking);

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
                'is_delivered' => $booking->isDelivered(),
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
        description: "Réservations où l'utilisateur est le vendeur"
    )]
    #[OA\Tag(name: "Bookings")]
    #[Security(name: "Bearer")]
    #[Route('/seller', name: 'app_bookings_seller', methods: ['GET'])]
    public function sellerBookings(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $sellerBookings = $this->bookingRepository->findBookingsForSeller($user);

        $bookingsData = array_map(function ($booking) {
            return [
                'id' => $booking->getId(),
                'product' => [
                    'id' => $booking->getProduct()->getId(),
                    'title' => $booking->getProduct()->getTitle(),
                    'price' => $booking->getProduct()->getPrice()
                ],
                'buyer' => [
                    'id' => $booking->getUser()->getId(),
                    'name' => $booking->getUser()->getFirstName() . ' ' . $booking->getUser()->getLastName(),
                    'email' => $booking->getUser()->getEmail(),
                ],
                'chat' => $booking->getChat()->getId(),
                'total_price' => $booking->getTotalPrice(),
                'created_at' => $booking->getCreatedAt()->format('c'),
                'is_confirmed' => $booking->isConfirmed(),
                'is_delivered' => $booking->isDelivered(),
                'is_outdated' => $booking->isOutdated(),
                'confirmed_at' => $booking->getConfirmedAt()?->format('c'),
                'outdated_at' => $booking->getOutdatedAt()?->format('c'),
                'can_confirm' => !$booking->isConfirmed() && !$booking->isOutdated(),
                'can_reject' => !$booking->isConfirmed() && !$booking->isOutdated()
            ];
        }, $sellerBookings);

        return new JsonResponse($bookingsData);
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

        $bookingsData = array_map(function ($booking) {
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
    public function cancel(Booking $booking): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$booking) {
            return new JsonResponse(['message' => 'Réservation non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(BookingVoter::CANCEL, $booking);

        if ($booking->isOutdated()) {
            return new JsonResponse([
                'message' => 'Cette réservation est déjà annulée ou expirée'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $booking->setIsOutdated(true)
                ->setOutdatedAt(new \DateTimeImmutable());

            $this->entityManager->flush();

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
    public function show(Booking $booking): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $this->denyAccessUnlessGranted(BookingVoter::VIEW, $booking);

        $isSellerView = $booking->getProduct()->getUser() === $user;
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
            'is_delivered' => $booking->isDelivered(),
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

    #[Route('/{id}/create-payment-intent', name: 'app_bookings_create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Booking $booking, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $this->denyAccessUnlessGranted(BookingVoter::PAY, $booking);

        if (!$booking->isConfirmed()) {
            return new JsonResponse([
                'message' => 'La réservation doit être confirmée avant le paiement'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($booking->isPaid()) {
            return new JsonResponse([
                'message' => 'Cette réservation est déjà payée'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

            $paymentIntent = PaymentIntent::create([
                'amount' => $booking->getTotalPrice() * 100,
                'currency' => 'eur',
                'payment_method_types' => ['card'],
                'metadata' => [
                    'booking_id' => $booking->getId(),
                    'user_id' => $user->getId(),
                    'product_title' => $booking->getProduct()->getTitle(),
                ],
            ]);

            $booking->setPaymentIntentId($paymentIntent->id);
            $this->entityManager->flush();

            return new JsonResponse([
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Erreur lors de la création du paiement: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/confirm-payment', name: 'app_bookings_confirm_payment', methods: ['POST'])]
    public function confirmPayment(Booking $booking, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$booking) {
            return new JsonResponse(['message' => 'Réservation non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(BookingVoter::PAY, $booking);

        $data = json_decode($request->getContent(), true);
        $paymentIntentId = $data['payment_intent_id'] ?? '';

        if (empty($paymentIntentId)) {
            return new JsonResponse([
                'message' => 'Payment Intent ID requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            if ($paymentIntent->status === 'succeeded') {
                $booking->setIsPaid(true)
                    ->setIsPaidAt(new \DateTimeImmutable())
                    ->setPaymentIntentId($paymentIntentId);

                $this->entityManager->flush();

                $this->notificationService->sendPaymentConfirmationNotification($booking);

                return new JsonResponse([
                    'id' => $booking->getId(),
                    'is_paid' => $booking->isPaid(),
                    'paid_at' => $booking->getIsPaidAt()?->format('c'),
                    'payment_intent_id' => $paymentIntentId,
                    'message' => 'Paiement confirmé avec succès'
                ]);

            } else {
                return new JsonResponse([
                    'message' => 'Le paiement n\'a pas été validé par Stripe'
                ], Response::HTTP_BAD_REQUEST);
            }

        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Erreur lors de la confirmation du paiement: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/generate-qr-code', name: 'app_bookings_generate_qr_code', methods: ['POST'])]
    public function generateQRCode(Booking $booking, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $this->denyAccessUnlessGranted(BookingVoter::GENERATE_QR, $booking);

        if (!$booking->isConfirmed()) {
            return new JsonResponse([
                'message' => 'La réservation doit être confirmée'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$booking->isPaid() && $booking->getTotalPrice() > 0) {
            return new JsonResponse([
                'message' => 'La réservation doit être payée'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($booking->isDelivered()) {
            return new JsonResponse([
                'message' => 'Cette transaction est déjà finalisée'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $token = bin2hex(random_bytes(32));

            $expiresAt = new \DateTimeImmutable('+5 minutes');

            $qrToken = new QrValidationToken();
            $qrToken->setBooking($booking)
                ->setToken($token)
                ->setExpiresAt($expiresAt)
                ->setIsUsed(false);

            $this->entityManager->persist($qrToken);
            $this->entityManager->flush();

            return new JsonResponse([
                'token' => $token,
                'expires_at' => $expiresAt->format('c'),
                'booking_id' => $booking->getId(),
                'valid_duration' => 300,
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Erreur lors de la génération du QR code: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/validate-transaction', name: 'app_bookings_validate_transaction', methods: ['POST'])]
    public function validateTransaction(Booking $booking, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $this->denyAccessUnlessGranted(BookingVoter::VALIDATE_TRANSACTION, $booking);

        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? '';
        $validationData = $data['validation_data'] ?? [];

        if (empty($token)) {
            return new JsonResponse([
                'message' => 'Token requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $qrToken = $this->qrValidationTokenRepository->findOneBy([
                'token' => $token,
                'booking' => $booking,
                'isUsed' => false
            ]);

            if (!$qrToken) {
                return new JsonResponse([
                    'message' => 'Token invalide ou déjà utilisé'
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($qrToken->getExpiresAt() < new \DateTimeImmutable()) {
                return new JsonResponse([
                    'message' => 'Token expiré'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (isset($validationData['amount']) && $validationData['amount'] != $booking->getTotalPrice()) {
                return new JsonResponse([
                    'message' => 'Montant de validation incorrect'
                ], Response::HTTP_BAD_REQUEST);
            }

            $qrToken->setIsUsed(true)
                ->setUsedAt(new \DateTimeImmutable());

            $booking->setIsDelivered(true)
                ->setIsDeliveredAt(new \DateTimeImmutable())
                ->setValidationMethod('qr_code');

            $this->entityManager->flush();

            $this->notificationService->sendTransactionCompletedNotification($booking);

            return new JsonResponse([
                'message' => 'Transaction validée avec succès',
                'booking' => [
                    'id' => $booking->getId(),
                    'is_completed' => true,
                    'completed_at' => $booking->getIsDeliveredAt()?->format('c'),
                    'validation_method' => 'qr_code',
                    'product' => [
                        'title' => $booking->getProduct()->getTitle()
                    ],
                    'total_price' => $booking->getTotalPrice()
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Erreur lors de la validation: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/transaction-status', name: 'app_bookings_transaction_status', methods: ['GET'])]
    public function getTransactionStatus(Booking $booking, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $this->denyAccessUnlessGranted(BookingVoter::VIEW, $booking);

        return new JsonResponse([
            'booking_id' => $booking->getId(),
            'is_confirmed' => $booking->isConfirmed(),
            'is_delivered' => $booking->isDelivered(),
            'is_paid' => $booking->isPaid(),
            'is_completed' => $booking->isDelivered(),
            'completed_at' => $booking->getIsDeliveredAt()?->format('c'),
            'validation_method' => $booking->getValidationMethod(),
            'can_generate_qr' => $booking->isConfirmed() &&
                ($booking->isPaid() || $booking->getTotalPrice() == 0) &&
                !$booking->isDelivered(),
            'can_validate' => $booking->isConfirmed() &&
                ($booking->isPaid() || $booking->getTotalPrice() == 0) &&
                !$booking->isDelivered() &&
                ($booking->getProduct()->getUser() === $user)
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


    private function getHoursSinceCreation(Booking $booking): int
    {
        $now = new \DateTimeImmutable();
        $interval = $now->diff($booking->getCreatedAt());
        return $interval->h + ($interval->days * 24);
    }
}
