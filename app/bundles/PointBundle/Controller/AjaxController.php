<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 *
 * @package Mautic\PointBundle\Controller
 */
class AjaxController extends CommonAjaxController
{

    /**
     * @param Request $request
     * @param string  $name
     */
    protected function reorderActionsAction (Request $request)
    {
        $dataArray  = array('success' => 0);
        $session    = $this->factory->getSession();
        $order      = InputHelper::clean($request->request->get('point'));
        $components = $session->get('mautic.pointactions.add');
        if (!empty($order) && !empty($components)) {
            $components = array_replace(array_flip($order), $components);
            $session->set('mautic.pointactions.add', $components);
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }
}