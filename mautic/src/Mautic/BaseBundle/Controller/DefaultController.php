<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\BaseBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class DefaultController
 * Almost all other Mautic Bundle controllers extend this default controller
 *
 * @package Mautic\BaseBundle\Controller
 */
class DefaultController extends CommonController
{

    /**
     * @return \Symfony\Component\HttpFoundation\Response|void
     */
    public function indexAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            return $this->ajaxAction($request);
        } else {
            return $this->render('MauticBaseBundle:Default:index.html.php');
        }
    }
}