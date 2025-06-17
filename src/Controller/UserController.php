<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Random\RandomException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

#[Route('/user')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface      $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface          $validator,
        private readonly UserRepository              $userRepository,
        private readonly NotificationService         $notificationService,
    )
    {
    }

    #[Route('', name: 'app_user_create', methods: ['POST'])]
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
        description: "Retourne l'utilisateur créé",
        content: new OA\JsonContent(
            ref: new Model(type: User::class, groups: ["user:read"])
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Données invalides"
    )]
    #[OA\Tag(name: "Users")]
    #[Security(name: "Bearer")]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['password']) || empty($data['password'])) {
            return $this->json(['message' => 'Le mot de passe est requis'], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setEmail($data['email'] ?? '');
        $user->setRoles(['ROLE_USER']);

        try {
            $verificationToken = bin2hex(random_bytes(32));
            $user->setVerificationToken($verificationToken);
        } catch (RandomException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $data['password'])
        );

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        try {
            $this->notificationService->sendVerificationEmail($user);
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        return $this->json(
            $user,
            Response::HTTP_CREATED,
            [],
            ['groups' => ['user:read', 'profile:read']]
        );
    }

    #[OA\Response(
        response: 201,
        description: "Retourne les utilisateurs",
        content: new OA\JsonContent(
            ref: new Model(type: User::class, groups: ["user:read"])
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Données invalides"
    )]
    #[OA\Tag(name: "Users")]
    #[Security(name: "Bearer")]
    #[Route('', name: 'app_users', methods: ['GET'])]
    public function get(Request $request): JsonResponse
    {
        $users = $this->userRepository->findAll();

        return $this->json(
            $users,
            Response::HTTP_CREATED,
            [],
            ['groups' => 'user:read']
        );
    }

    #[Route('/profile', name: 'app_user_profile', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: "Retourne le profil de l'utilisateur connecté",
        content: new OA\JsonContent(
            ref: new Model(type: User::class, groups: ["user:read"])
        )
    )]
    #[OA\Tag(name: "Users")]
    #[Security(name: "Bearer")]
    public function profile(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json(
            $user,
            Response::HTTP_OK,
            [],
            ['groups' => 'user:read']
        );
    }

    #[Route('/verify/{token}', name: 'app_user_verify', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: "Compte vérifié avec succès"
    )]
    #[OA\Response(
        response: 404,
        description: "Token invalide"
    )]
    #[OA\Tag(name: "Users")]
    public function verifyUser(string $token): Response
    {
        try {
            $user = $this->userRepository->findOneBy(['verificationToken' => $token]);

            if (!$user) {
                return $this->json(['message' => 'Token de vérification invalide'], Response::HTTP_NOT_FOUND);
            }

            $user->setIsVerified(true);
            $user->setVerificationToken(null);

            $this->entityManager->flush();

            return $this->redirect('https://mealmates.testingtest.fr/login');
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }


}
