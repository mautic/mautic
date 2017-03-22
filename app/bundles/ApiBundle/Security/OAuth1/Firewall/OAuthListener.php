<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Security\OAuth1\Firewall;

use Bazinga\OAuthServerBundle\Security\Authentification\Token\OAuthToken;
use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class OAuthListener.
 */
class OAuthListener extends \Bazinga\OAuthServerBundle\Security\Firewall\OAuthListener
{
    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function setFactory(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @author William DURAND <william.durand1@gmail.com>
     *
     * @param GetResponseEvent $event
     *
     * @throws AuthenticationException
     * @throws HttpException
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (false === $request->attributes->get('oauth_request_parameters', false)) {
            return;
        }

        $token = new OAuthToken();
        $token->setRequestParameters($request->attributes->get('oauth_request_parameters'));
        $token->setRequestMethod($request->attributes->get('oauth_request_method'));
        $token->setRequestUrl($request->attributes->get('oauth_request_url'));

        try {
            $returnValue = $this->authenticationManager->authenticate($token);

            if ($returnValue instanceof TokenInterface) {
                return $this->securityContext->setToken($returnValue);
            } elseif ($returnValue instanceof Response) {
                return $event->setResponse($returnValue);
            }
        } catch (AuthenticationException $e) {
            throw $e;
        }

        throw new HttpException(401);
    }
}
