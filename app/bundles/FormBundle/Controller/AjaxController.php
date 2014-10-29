<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 */
class AjaxController extends CommonAjaxController
{

    /**
     * @param Request $request
     * @param string  $name
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function reorderFieldsAction(Request $request, $name = 'fields')
    {
        $dataArray  = array('success' => 0);
        $session    = $this->factory->getSession();
        $order      = InputHelper::clean($request->request->get('mauticform'));
        $components = $session->get('mautic.form' . $name . '.add');
        if (!empty($order) && !empty($components)) {
            $components = array_replace(array_flip($order), $components);
            $session->set('mautic.form' . $name . '.add', $components);
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function reorderActionsAction(Request $request) {
        return $this->reorderFieldsAction($request, 'actions');
    }
}
