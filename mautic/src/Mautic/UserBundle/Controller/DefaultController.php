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
    public function indexAction(Request $request, $page = 1)
    {
        //set limits
        $limit = $this->container->getParameter('default_pagelimit');
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $orderBy    = $this->get('session')->get("user.orderby", "u.lastName, u.firstName, u.username");
        $orderByDir = $this->get("session")->get("user.orderbydir", "ASC");

        $users = $this->getDoctrine()
            ->getRepository('MauticUserBundle:User')
            ->getUsers($start, $limit, $orderBy, $orderByDir);

        if (count($users) < ($start + 1)) {
            //the number of users are now less then the current page so redirect to the last page
            $lastPage = ($pageCount = floor($limit / count($users))) ?: 1;
            $this->get('session')->set("mautic.user.page", $lastPage);
            $returnUrl   = $this->generateUrl('mautic_user_index', array("page" => $lastPage));
            return $this->postAction($request,
                $returnUrl,
                array("page" => $page),
                "Default:index",
                array(
                    'activeLink'    => '#mautic_user_index',
                    'route'         => $returnUrl
                ),
                true
            );
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->get('session')->set("mautic.user.page", $page);

        if ($request->isXmlHttpRequest() && !$request->get("ignoreAjax", false)) {
            return $this->ajaxAction($request, array(
                "users" => $users,
                "page"  => $page,
                "limit" => $limit
            ));
        } else {
            return $this->render('MauticUserBundle:Default:index.html.php', array(
                "users" => $users,
                "page"  => $page,
                "limit" => $limit
            ));
        }
    }
}
