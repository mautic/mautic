<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AddonBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 *
 * @package Mautic\AddonBundle\Controller
 */
class AjaxController extends CommonAjaxController
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function setIntegrationFilterAction(Request $request)
    {
        $session     = $this->factory->getSession();
        $addonFilter = InputHelper::int($this->request->get('addon'));
        $session->set('mautic.integrations.filter', $addonFilter);
        return $this->sendJsonResponse(array('success' => 1));
    }
}