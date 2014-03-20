<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Mautic\BaseBundle\Controller\CommonController;


/**
 * Class DefaultController
 *
 * @package Mautic\DashboardBundle\Controller
 */
class DefaultController extends CommonController
{

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            return $this->ajaxAction($request);
        } else {
            return $this->render('MauticDashboardBundle:Default:index.html.php');
        }
    }
}
