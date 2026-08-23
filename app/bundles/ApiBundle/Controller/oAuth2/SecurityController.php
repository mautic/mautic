<?php

namespace Mautic\ApiBundle\Controller\oAuth2;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

final class SecurityController extends CommonController
{
    #[Route(
        '/oauth/v2/authorize_login',
        name: 'mautic_oauth2_server_auth_login',
        methods: ['GET|POST'],
        priority: -215
    )]
    public function loginAction(Request $request): Response
    {
        $session = $request->getSession();

        // get the login error if there is one
        if ($request->attributes->has(SecurityRequestAttributes::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityRequestAttributes::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityRequestAttributes::AUTHENTICATION_ERROR);
            $session->remove(SecurityRequestAttributes::AUTHENTICATION_ERROR);
        }
        if (!empty($error)) {
            if ($error instanceof Exception\BadCredentialsException) {
                $msg = 'mautic.user.auth.error.invalidlogin';
            } else {
                $msg = $error->getMessage();
            }
            $this->addFlashMessage($msg, [], 'error', null, false);
        }

        if ($session->has('_security.target_path')) {
            if (str_contains($session->get('_security.target_path'), $this->generateUrl('fos_oauth_server_authorize'))) {
                $session->set('_fos_oauth_server.ensure_logout', true);
            }
        }

        return $this->render(
            '@MauticApi/Security/login.html.twig',
            [
                'last_username' => $session->get(SecurityRequestAttributes::LAST_USERNAME),
                'route'         => 'mautic_oauth2_server_auth_login_check',
            ]
        );
    }

    #[Route(
        '/oauth/v2/authorize_login_check',
        name: 'mautic_oauth2_server_auth_login_check',
        methods: ['GET|POST'],
        priority: -216
    )]
    public function loginCheckAction(): Response
    {
        return new Response('', Response::HTTP_BAD_REQUEST);
    }
}
