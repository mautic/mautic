<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Mautic\BaseBundle\Controller\EventsController;

/**
 * Class DefaultController
 * Almost all other Mautic Bundle controllers extend this default controller
 *
 * @package Mautic\BaseBundle\Controller
 */
class DefaultController extends Controller implements EventsController
{

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response|void
     */
    public function indexAction(Request $request)
    {
        return $this->render('MauticDashboardBundle:Default:index.html.php');
    }
}