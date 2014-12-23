<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 */
class AjaxController extends CommonAjaxController
{
    /**
     * Count how many visitors are currently viewing a page.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function viewingVisitorsAction(Request $request)
    {
        $dataArray  = array('success' => 0, 'ignore_wdt' => 1);

        /** @var \Mautic\PageBundle\Entity\PageRepository $pageRepository */
        $pageRepository = $this->factory->getEntityManager()->getRepository('MauticPageBundle:Hit');
        $dataArray['viewingVisitors'] = $pageRepository->countVisitors(60, true);

        $dataArray['success'] = 1;

        return $this->sendJsonResponse($dataArray);
    }
}
