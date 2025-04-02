<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class OAuthSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private string $frontendUrl;

    public function __construct(
        private readonly JWTTokenManagerInterface $JWTTokenManager,
        private readonly ParameterBagInterface    $params
    )
    {
        $this->frontendUrl = $this->params->get('app.frontend_url');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse|JsonResponse
    {
        $user = $token->getUser();

        $jwtToken = $this->JWTTokenManager->create($user);

        if ($request->isXmlHttpRequest() || $request->headers->get('Accept') === 'application/json') {
            return new JsonResponse([
                'token' => $jwtToken
            ]);
        }

        return new RedirectResponse($this->frontendUrl . '/oauth-callback?token=' . $jwtToken);
    }
}
