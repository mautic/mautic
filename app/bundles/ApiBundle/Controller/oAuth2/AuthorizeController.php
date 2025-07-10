<?php

namespace Mautic\ApiBundle\Controller\oAuth2;

use FOS\OAuthServerBundle\Form\Handler\AuthorizeFormHandler;
use FOS\OAuthServerBundle\Model\ClientManagerInterface;
use OAuth2\OAuth2;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class AuthorizeController extends \FOS\OAuthServerBundle\Controller\AuthorizeController
{
    private TokenStorageInterface $tokenStorage;

    /**
     * This constructor must be duplicated from the extended class so our custom code could access the properties.
     */
    public function __construct(
        RequestStack $requestStack,
        Form $authorizeForm,
        AuthorizeFormHandler $authorizeFormHandler,
        OAuth2 $oAuth2Server,
        TokenStorageInterface $tokenStorage,
        UrlGeneratorInterface $router,
        ClientManagerInterface $clientManager,
        EventDispatcherInterface $eventDispatcher,
        private Environment $twig,
    ) {
        parent::__construct(
            $requestStack,
            $authorizeForm,
            $authorizeFormHandler,
            $oAuth2Server,
            $twig,
            $tokenStorage,
            $router,
            $clientManager,
            $eventDispatcher
        );

        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param array<string , mixed> $data Various data to be passed to the twig template
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    protected function renderAuthorize(array $data): Response
    {
        $response = $this->twig->render(
            '@MauticApi/Authorize/oAuth2/authorize.html.twig',
            $data
        );

        return new Response($response);
    }

    public function authorizeAction(Request $request): Response
    {
        // The parent bundle does not care about token being empty.
        if (null === $this->tokenStorage->getToken()) {
            throw new AccessDeniedException('This user does not have access to this section. No token.');
        }

        return parent::authorizeAction($request);
    }
}
