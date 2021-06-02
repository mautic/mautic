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

use Bazinga\OAuthServerBundle\Doctrine\Provider\TokenProvider;
use Bazinga\OAuthServerBundle\Model\RequestTokenInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthorizeController extends AbstractController
{
    /**
     * @var TokenProvider
     */
    private $tokenProvider;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(TokenProvider $tokenProvider, TokenStorageInterface $tokenStorage)
    {
        $this->tokenProvider = $tokenProvider;
        $this->tokenStorage  = $tokenStorage;
    }

    /**
     * @return Response
     *
     * @throws AccessDeniedException
     * @throws HttpException
     */
    public function allowAction(Request $request)
    {
        $oauth_token    = $request->get('oauth_token', null);
        $oauth_callback = $request->get('oauth_callback', null);
        $user           = $this->tokenStorage->getToken()->getUser();

        if (!$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $token = $this->tokenProvider->loadRequestTokenByToken($oauth_token);
        if (null === $token) {
            throw new AccessDeniedException('oauth_token is invalid.');
        }

        $consumer                  = $token->getConsumer();
        $restricted_oauth_callback = $consumer->getCallback();
        if (!empty($restricted_oauth_callback) && 0 !== strpos($oauth_callback, $restricted_oauth_callback)) {
            throw new AccessDeniedException('Callback is invalid.');
        }

        if ($token instanceof RequestTokenInterface) {
            $this->tokenProvider->setUserForRequestToken($token, $this->tokenStorage->getToken()->getUser());

            return new Response(
                $this->container->get('templating')->render(
                    'MauticApiBundle:Authorize:oAuth1/authorize.html.php',
                    [
                        'consumer'       => $consumer,
                        'oauth_token'    => $oauth_token,
                        'oauth_callback' => $oauth_callback,
                    ]
                )
            );
        }

        throw new HttpException(404);
    }
}
