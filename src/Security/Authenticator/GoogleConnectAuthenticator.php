<?php

/*
 * This file is part of the hydrometer public server project.
 *
 * @author Clemens Krack <info@clemenskrack.com>
 */

namespace App\Security\Authenticator;

use App\Security\Provider\GoogleConnectProvider;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class GoogleConnectAuthenticator extends SocialAuthenticator
{
    private $clientRegistry;
    private $router;
    private $userProvider;

    public function __construct(
        ClientRegistry $clientRegistry,
        GoogleConnectProvider $userProvider,
        RouterInterface $router
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->userProvider = $userProvider;
        $this->router = $router;
    }

    public function supports(Request $request)
    {
        // continue ONLY if the current ROUTE matches the check ROUTE
        return 'connect_google_check' === $request->attributes->get('_route');
    }

    public function getCredentials(Request $request)
    {
        // this method is only called if supports() returns true

        return $this->fetchAccessToken($this->getClient());
    }

    public function getUser($credentials, UserProviderInterface $ignoredProvider)
    {
        /** @var GoogleUser $googleUser */
        $googleUser = $this->getClient()
            ->fetchUserFromToken($credentials);

        // use the injected userprovider to find or create our oauth user
        // note: we ignore the userprovider in the method argument
        return $this->userProvider->loadUserByOAuthUserResponse($googleUser);
    }

    /**
     * @return GoogleClient
     */
    private function getClient()
    {
        return $this->clientRegistry
            // "google" is the key used in config.yml
            ->getClient('google');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return new RedirectResponse($this->router->generate('ui_hydrometers_list'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        // or to translate this message
        // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse(
            $this->router->generate(
                'static-page',
                ['static' => 'auth']
            ),
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}
