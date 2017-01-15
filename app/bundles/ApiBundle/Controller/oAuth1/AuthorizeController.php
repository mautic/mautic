<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Controller\oAuth1;

use Bazinga\OAuthServerBundle\Model\RequestTokenInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class AuthorizeController.
 */
class AuthorizeController extends Controller
{
    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws AccessDeniedException
     * @throws HttpException
     */
    public function allowAction(Request $request)
    {
        $oauth_token    = $request->get('oauth_token', null);
        $oauth_callback = $request->get('oauth_callback', null);

        $securityContext = $this->container->get('security.context');
        $tokenProvider   = $this->container->get('bazinga.oauth.provider.token_provider');

        $user = $securityContext->getToken()->getUser();

        if (!$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $token    = $tokenProvider->loadRequestTokenByToken($oauth_token);
        $consumer = $token->getConsumer();

        $restricted_oauth_callback = $consumer->getCallback();
        if (!empty($restricted_oauth_callback) && strpos($oauth_callback, $restricted_oauth_callback) !== 0) {
            throw new AccessDeniedException('Callback is not valid.');
        }

        if ($token instanceof RequestTokenInterface) {
            $tokenProvider->setUserForRequestToken($token, $securityContext->getToken()->getUser());

            return new Response($this->container->get('templating')->render('MauticApiBundle:Authorize:oAuth1/authorize.html.php', [
                'consumer'       => $token->getConsumer(),
                'oauth_token'    => $oauth_token,
                'oauth_callback' => $oauth_callback,
            ]));
        }

        throw new HttpException(404);
    }
}
