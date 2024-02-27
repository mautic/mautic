<?php

namespace Mautic\ApiBundle\Controller\oAuth2;

use FOS\OAuthServerBundle\Event\PreAuthorizationEvent;
use FOS\OAuthServerBundle\Form\Handler\AuthorizeFormHandler;
use FOS\OAuthServerBundle\Model\ClientManagerInterface;
use OAuth2\OAuth2;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Environment;

class AuthorizeController extends \FOS\OAuthServerBundle\Controller\AuthorizeController
{
    /**
     * This constructor must be duplicated from the extended class so our custom code could access the properties.
     */
    public function __construct(
        RequestStack $requestStack,
        private Form $authorizeForm,
        private AuthorizeFormHandler $authorizeFormHandler,
        private OAuth2 $oAuth2Server,
        private TokenStorageInterface $tokenStorage,
        UrlGeneratorInterface $router,
        ClientManagerInterface $clientManager,
        private EventDispatcherInterface $eventDispatcher,
        private Environment $twig,
        SessionInterface $session = null
    ) {
        parent::__construct(
            $requestStack,
            $authorizeForm,
            $authorizeFormHandler,
            $oAuth2Server,
            $tokenStorage,
            $router,
            $clientManager,
            $eventDispatcher,
            $twig,
            $session
        );
    }

    /**
     * @return \FOS\OAuthServerBundle\Controller\Response|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \OAuth2\OAuth2RedirectException
     * @throws AccessDeniedException
     */
    public function authorizeAction(Request $request)
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if (!$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        if (true === $request->getSession()->get('_fos_oauth_server.ensure_logout')) {
            $request->getSession()->invalidate(600);
            $request->getSession()->set('_fos_oauth_server.ensure_logout', true);
        }

        $event = $this->eventDispatcher->dispatch(
            new PreAuthorizationEvent($user, $this->getClient())
        );

        if ($event->isAuthorizedClient()) {
            $scope = $request->get('scope', null);

            return $this->oAuth2Server->finishClientAuthorization(true, $user, $request, $scope);
        }

        if (true === $this->authorizeFormHandler->process()) {
            return $this->processSuccess($user, $this->authorizeFormHandler, $request);
        }

        $contents =  $this->twig->render(
            '@MauticApi/Authorize/oAuth2/authorize.html.twig',
            [
                'form'   => $this->authorizeForm->createView(),
                'client' => $this->getClient(),
            ]
        );

        return new Response($contents);
    }
}
