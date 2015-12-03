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
use Mautic\DashboardBundle\Entity\Module;
use Mautic\DashboardBundle\DashboardEvents;
use Mautic\DashboardBundle\Event\ModuleFormEvent;

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
        $dataArray  = array('success' => 0);

        /** @var \Mautic\PageBundle\Entity\PageRepository $pageRepository */
        $pageRepository = $this->factory->getEntityManager()->getRepository('MauticPageBundle:Hit');
        $dataArray['viewingVisitors'] = $pageRepository->countVisitors(60, true);

        $dataArray['success'] = 1;

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Returns HTML of a new module based on its values.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function updateModuleFormAction(Request $request)
    {
        $data = $request->request->get('module');
        $dataArray = array('success' => 0);

        // Clear params if type is not selected
        if (empty($data['type'])) {
            unset($data['params']);
        }

        $module = new Module();
        $form = $this->get('form.factory')->create('module', $module);
        $formHtml = $this->render("MauticDashboardBundle::Module\\form.html.php",
            array('form' => $form->bind($data)->createView())
        )->getContent();

        $dataArray['formHtml'] = $formHtml;
        $dataArray['success']  = 1;

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Saves the new ordering of dashboard modules.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function updateModuleOrderingAction(Request $request)
    {
        $data = $request->request->get('ordering');
        $repo = $this->factory->getModel('dashboard')->getRepository();
        $repo->updateOrdering(array_flip($data), $this->factory->getUser()->getId());
        $dataArray = array('success' => 1);

        return $this->sendJsonResponse($dataArray);
    }
}
