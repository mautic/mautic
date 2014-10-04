<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Security\OAuth1\Firewall;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Mautic\ApiBundle\Security\OAuth1\Authentication\Token\OAuthToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class OAuthListener
 *
 * @package Mautic\ApiBundle\Security\Firewall
 */
class OAuthListener extends \Bazinga\OAuthServerBundle\Security\Firewall\OAuthListener
{

    /**
     * @author William DURAND <william.durand1@gmail.com>
     *
     * @param GetResponseEvent $event The event.
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
