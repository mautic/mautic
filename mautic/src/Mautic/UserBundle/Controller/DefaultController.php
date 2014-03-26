<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\Userbundle\Entity as Entity;
use Mautic\UserBundle\Form\Type as FormType;

/**
 * Class DefaultController
 *
 * @package Mautic\UserBundle\Controller
 */
class DefaultController extends CommonController
{

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, $page)
    {
        $start = ($page === 1) ? 0 : (($page-1) * 30);

        $users = $this->getDoctrine()
            ->getRepository('MauticUserBundle:User')
            ->getAllUsers($start);

        if ($request->isXmlHttpRequest()) {
            return $this->ajaxAction($request, array("users" => $users));
        } else {
            return $this->render('MauticUserBundle:Default:index.html.php', array("users" => $users));
        }
    }
}
