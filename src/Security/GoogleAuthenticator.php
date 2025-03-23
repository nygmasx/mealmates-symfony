<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

// Nouveaux imports nécessaires
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use App\Repository\UserRepository;
use App\Entity\User;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleUser;

class GoogleAuthenticator extends AbstractOAuthAuthenticator
{
    protected string $serviceName = 'google';  // J'ai ajouté 'google' comme valeur

    protected function getUserFromResourceOwner(ResourceOwnerInterface $resourceOwner, UserRepository $repository): User
    {
        // Correction de "resourceOwner" à "$resourceOwner" (variable)
        if (!($resourceOwner instanceof GoogleUser)) {
            throw new \LogicException('This method can only be called with a GoogleUser instance');
        }

        if (true !== $resourceOwner->get('verified_email')) {
            throw new \LogicException('The email is not verified');
        }

        $googleId = $resourceOwner->getId();
        $user = $repository->findOneBy(['googleId' => $googleId]);
        if ($user) {
            return $user;
        }

        $email = $resourceOwner->getEmail();
        $user = $repository->findOneBy(['email' => $email]);
        if ($user) {
            $user->setGoogleId($googleId);
            return $user;
        }

        $user = new User();
        $user->setGoogleId($googleId);
        $user->setEmail($email);
        $user->setFirstName($resourceOwner->getFirstName());
        $user->setLastName($resourceOwner->getLastName());
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);

        return $user;
    }
}
