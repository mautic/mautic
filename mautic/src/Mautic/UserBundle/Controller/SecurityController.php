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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Class DefaultController
 *
 * @package Mautic\UserBundle\Controller
 */
class SecurityController extends CommonController
{
    public function loginAction(Request $request) {
        $session = $request->getSession();

        // get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR)
            );
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }

        return $this->render(
            'MauticUserBundle:Security:login.html.php',
            array(
                'last_username' => $session->get(SecurityContext::LAST_USERNAME)
            )
        );
    }
}