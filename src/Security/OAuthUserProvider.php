<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class OAuthUserProvider implements OAuthAwareUserProviderInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function loadUserByOAuthUserResponse(UserResponseInterface $response): UserInterface
    {
        $email = $response->getEmail();

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($email);

            $resourceOwnerName = $response->getResourceOwner()->getName();
            $user->setOauthProvider($resourceOwnerName);
            $user->setOauthId($response->getUsername());

            if ($response->getRealName()) {
                $user->setOauthName($response->getRealName());
            }

            $user->setPassword(bin2hex(random_bytes(16)));

            $user->setRoles(['ROLE_USER']);

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } else {

            if (!$user->getOauthProvider()) {
                $resourceOwnerName = $response->getResourceOwner()->getName();
                $user->setOauthProvider($resourceOwnerName);
                $user->setOauthId($response->getUsername());
                $this->entityManager->flush();
            }
        }

        return $user;
    }
}
