<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception as Exception;
/**
 * Class DefaultController
 *
 * @package Mautic\UserBundle\Controller
 */
class SecurityController extends CommonController
{

    /**
     * Generates login form and processes login
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction() {
        $session = $this->request->getSession();

        // get the login error if there is one
        if ($this->request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $this->request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
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

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get("translator")->trans($msg, array(), 'flashes')
            );
        }

        return $this->render(
            'MauticUserBundle:Security:login.html.php',
            array(
                'last_username' => $session->get(SecurityContext::LAST_USERNAME)
            )
        );
    }
}