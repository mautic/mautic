<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Security\Authentication;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationHandler implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface
{
    private $router;
    private $session;

    /**
     * Constructor.
     *
     * @author    Joe Sexton <joe@webtipblog.com>
     *
     * @param RouterInterface $router
     * @param Session         $session
     */
    public function __construct(RouterInterface $router, Session $session)
    {
        $this->router  = $router;
        $this->session = $session;
    }

    /**
     * onAuthenticationSuccess.
     *
     * @author    Joe Sexton <joe@webtipblog.com>
     *
     * @param Request        $request
     * @param TokenInterface $token
     *
     * @return Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        // Remove post_logout if set
        $request->getSession()->remove('post_logout');

        $format = $request->request->get('format');

        if ($format == 'json') {
            $array    = ['success' => true];
            $response = new Response(json_encode($array));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        } else {
            $redirectUrl = $request->getSession()->get('_security.main.target_path', $this->router->generate('mautic_dashboard_index'));

            return new RedirectResponse($redirectUrl);
        }
    }

    /**
     * onAuthenticationFailure.
     *
     * @author    Joe Sexton <joe@webtipblog.com>
     *
     * @param Request                 $request
     * @param AuthenticationException $exception
     *
     * @return Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        // Remove post_logout if set
        $request->getSession()->remove('post_logout');

        $format = $request->request->get('format');

        if ($format == 'json') {
            $array    = ['success' => false, 'message' => $exception->getMessage()];
            $response = new Response(json_encode($array));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        } else {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

            return new RedirectResponse($this->router->generate('login'));
        }
    }
}
