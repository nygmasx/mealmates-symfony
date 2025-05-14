<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\User;
use App\Repository\AvailabilityRepository;
use App\Repository\DietaryPreferencesRepository;
use App\Repository\ProfileRepository;
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
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/profile')]
final class ProfileController extends AbstractController
{
    public function __construct(
        private readonly ProfileRepository            $profileRepository,
        private readonly ValidatorInterface           $validator,
        private readonly EntityManagerInterface       $entityManager,
        private readonly DietaryPreferencesRepository $dietaryPreferencesRepository,
        private readonly AvailabilityRepository       $availabilityRepository,
        private readonly SerializerInterface          $serializer
    )
    {
    }

    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "dietary_preferences", type: "array", items: new OA\Items(type: "integer")),
                new OA\Property(property: "address_line1", type: "string"),
                new OA\Property(property: "address_line2", type: "string", nullable: true),
                new OA\Property(property: "city", type: "string"),
                new OA\Property(property: "zip_code", type: "string"),
                new OA\Property(property: "latitude", type: "string", nullable: true),
                new OA\Property(property: "longitude", type: "string", nullable: true),
                new OA\Property(property: "availabilities", type: "array", items: new OA\Items(type: "integer"))
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Retourne le profil d'un utilisateur",
        content: new OA\JsonContent(
            ref: new Model(type: Profile::class, groups: ["profile:read"])
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Données invalides"
    )]
    #[OA\Tag(name: "Profiles")]
    #[Security(name: "Bearer")]
    #[Route('', name: 'app_profile_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $existingProfile = $this->profileRepository->findOneBy(['user' => $user]);
        if ($existingProfile) {
            return new JsonResponse(['message' => 'Ce profil existe déjà'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $profile = $this->serializer->deserialize(
                $request->getContent(),
                Profile::class,
                'json',
                ['groups' => 'profile:write']
            );

            $profile->setUser($user);

            $data = json_decode($request->getContent(), true);

            if (isset($data['dietary_preferences']) && is_array($data['dietary_preferences'])) {
                foreach ($data['dietary_preferences'] as $preferenceId) {
                    $preference = $this->dietaryPreferencesRepository->find($preferenceId);
                    if ($preference) {
                        $profile->addDietaryPreference($preference);
                    }
                }
            }

            if (isset($data['availabilities']) && is_array($data['availabilities'])) {
                foreach ($data['availabilities'] as $availabilityId) {
                    $availability = $this->availabilityRepository->find($availabilityId);
                    if ($availability) {
                        $profile->addAvailability($availability);
                    }
                }
            }

            $errors = $this->validator->validate($profile);
            if (count($errors) > 0) {
                $errorsMessages = [];
                foreach ($errors as $error) {
                    $errorsMessages[$error->getPropertyPath()] = $error->getMessage();
                }
                return new JsonResponse(['errors' => $errorsMessages], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($profile);
            $this->entityManager->flush();

            return $this->json(
                $profile,
                Response::HTTP_CREATED,
                [],
                ['groups' => ['profile:read', 'user:read', 'dietary:read', 'availability:read', 'review:read']]
            );
        } catch (NotEncodableValueException $e) {
            return new JsonResponse(['message' => 'Format JSON invalide'], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Une erreur est survenue lors de la création du profil: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "dietary_preferences", type: "array", items: new OA\Items(type: "integer")),
                new OA\Property(property: "address_line1", type: "string"),
                new OA\Property(property: "address_line2", type: "string", nullable: true),
                new OA\Property(property: "city", type: "string"),
                new OA\Property(property: "zip_code", type: "string"),
                new OA\Property(property: "latitude", type: "string", nullable: true),
                new OA\Property(property: "longitude", type: "string", nullable: true),
                new OA\Property(property: "availabilities", type: "array", items: new OA\Items(type: "integer"))
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Retourne le profil mis à jour",
        content: new OA\JsonContent(
            ref: new Model(type: Profile::class, groups: ["profile:read"])
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Données invalides"
    )]
    #[OA\Tag(name: "Profiles")]
    #[Security(name: "Bearer")]
    #[Route('/{id}', name: 'app_profile_edit', methods: ['PUT'])]
    public function edit(Request $request, Profile $profile): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if ($profile->getUser() !== $user) {
            return new JsonResponse(['message' => 'Vous n\'êtes pas autorisé à modifier ce profil'], Response::HTTP_FORBIDDEN);
        }

        try {
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return new JsonResponse(['message' => 'Données JSON invalides ou vides'], Response::HTTP_BAD_REQUEST);
            }

            $this->serializer->deserialize(
                $request->getContent(),
                Profile::class,
                'json',
                [
                    AbstractNormalizer::OBJECT_TO_POPULATE => $profile,
                    'groups' => 'profile:write'
                ]
            );

            if (isset($data['dietary_preferences']) && is_array($data['dietary_preferences'])) {
                foreach ($profile->getDietaryPreferences()->toArray() as $preference) {
                    $profile->removeDietaryPreference($preference);
                }

                foreach ($data['dietary_preferences'] as $preferenceId) {
                    $preference = $this->dietaryPreferencesRepository->find($preferenceId);
                    if ($preference) {
                        $profile->addDietaryPreference($preference);
                    }
                }
            }

            if (isset($data['availabilities']) && is_array($data['availabilities'])) {
                foreach ($profile->getAvailabilities()->toArray() as $availability) {
                    $profile->removeAvailability($availability);
                }

                foreach ($data['availabilities'] as $availabilityId) {
                    $availability = $this->availabilityRepository->find($availabilityId);
                    if ($availability) {
                        $profile->addAvailability($availability);
                    }
                }
            }

            $errors = $this->validator->validate($profile);
            if (count($errors) > 0) {
                $errorsMessages = [];
                foreach ($errors as $error) {
                    $errorsMessages[$error->getPropertyPath()] = $error->getMessage();
                }
                return new JsonResponse(['errors' => $errorsMessages], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($profile);
            $this->entityManager->flush();

            $this->entityManager->refresh($profile);

            return $this->json(
                $profile,
                Response::HTTP_OK,
                [],
                ['groups' => ['profile:read', 'user:read', 'dietary:read', 'availability:read', 'review:read']]
            );
        } catch (NotEncodableValueException $e) {
            return new JsonResponse(['message' => 'Format JSON invalide: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Une erreur est survenue lors de la mise à jour du profil',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Response(
        response: 200,
        description: "Retourne le profil d'un utilisateur",
        content: new OA\JsonContent(
            ref: new Model(type: Profile::class, groups: ["profile:read"])
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
    #[OA\Tag(name: "Profiles")]
    #[Security(name: "Bearer")]
    #[Route('/{user}', name: 'app_profile_show', methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $profile = $this->profileRepository->findBy(['user' => $user]);

        return $this->json(
            $profile,
            Response::HTTP_OK,
            [],
            ['groups' => ['profile:read', 'user:read']]
        );
    }

    #[OA\Response(
        response: 200,
        description: "Retourne le profil de l'utilisateur connecté",
        content: new OA\JsonContent(
            ref: new Model(type: Profile::class, groups: ["profile:read"])
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Profil non trouvé"
    )]
    #[OA\Tag(name: "Profiles")]
    #[Security(name: "Bearer")]
    #[Route('/me', name: 'app_profile_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $profile = $this->profileRepository->findBy(['user' => $user]);

        if (!$profile) {
            return new JsonResponse(['message' => 'Profil non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(
            $user,
            Response::HTTP_OK,
            [],
            ['groups' => ['profile:read']]
        );
    }

    #[OA\Response(
        response: 204,
        description: "Profil supprimé"
    )]
    #[OA\Response(
        response: 404,
        description: "Profil non trouvé"
    )]
    #[OA\Tag(name: "Profiles")]
    #[Security(name: "Bearer")]
    #[Route('/{id}', name: 'app_profile_delete', methods: ['DELETE'])]
    public function delete(Profile $profile): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if ($profile->getUser() !== $user) {
            return new JsonResponse(['message' => 'Vous n\'êtes pas autorisé à supprimer ce profil'], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($profile);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
