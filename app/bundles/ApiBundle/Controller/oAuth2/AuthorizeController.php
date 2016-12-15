<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Controller\oAuth2;

use FOS\OAuthServerBundle\Event\OAuthEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class AuthorizeController.
 */
class AuthorizeController extends \FOS\OAuthServerBundle\Controller\AuthorizeController
{
    /**
     * Authorize.
     *
     * @param Request $request
     *
     * @return \FOS\OAuthServerBundle\Controller\Response|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \OAuth2\OAuth2RedirectException
     * @throws AccessDeniedException
     *
     * @author Chris Jones <leeked@gmail.com>
     */
    public function authorizeAction(Request $request)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();

        if (!$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        if (true === $this->container->get('session')->get('_fos_oauth_server.ensure_logout')) {
            $this->container->get('session')->invalidate(600);
            $this->container->get('session')->set('_fos_oauth_server.ensure_logout', true);
        }

        $form        = $this->container->get('fos_oauth_server.authorize.form');
        $formHandler = $this->container->get('fos_oauth_server.authorize.form.handler');

        $event = $this->container->get('event_dispatcher')->dispatch(
            OAuthEvent::PRE_AUTHORIZATION_PROCESS,
            new OAuthEvent($user, $this->getClient())
        );

        if ($event->isAuthorizedClient()) {
            $scope = $request->get('scope', null);

            return $this->container
                ->get('fos_oauth_server.server')
                ->finishClientAuthorization(true, $user, $request, $scope);
        }

        if (true === $formHandler->process()) {
            return $this->processSuccess($user, $formHandler, $request);
        }

        return $this->container->get('templating')->renderResponse(
            'MauticApiBundle:Authorize:oAuth2/authorize.html.php',
            [
                'form'   => $form->createView(),
                'client' => $this->getClient(),
            ]
        );
    }
}
