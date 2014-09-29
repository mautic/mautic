<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Controller\oAuth1;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception as Exception;

class SecurityController extends Controller
{

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function loginAction(Request $request)
    {
        $session = $request->getSession();

        //get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }
        if (!empty($error)) {
            if (($error instanceof Exception\BadCredentialsException)) {
                $msg = "mautic.user.auth.error.invalidlogin";
            } else {
                $msg = $error->getMessage();
            }

            $session->getFlashBag()->add(
                'error',
                $this->get("translator")->trans($msg, array(), 'flashes')
            );
        }

        return $this->render(
            'MauticApiBundle:Security:login.html.php',
            array(
                'last_username' => $session->get(SecurityContext::LAST_USERNAME),
                'route'         => 'mautic_oauth1_server_auth_login_check'
            )
        );
    }

    /**
     * @return Response
     */
    public function loginCheckAction()
    {
        return new Response('', 400);
    }
}