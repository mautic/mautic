<?php

namespace Mautic\DashboardBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Form\Type\WidgetType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController.
 */
class AjaxController extends CommonAjaxController
{
    /**
     * Count how many visitors are currently viewing a page.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function viewingVisitorsAction(Request $request)
    {
        $dataArray = ['success' => 0];

        /** @var \Mautic\PageBundle\Entity\PageRepository $pageRepository */
        $pageRepository               = $this->get('doctrine.orm.entity_manager')->getRepository('MauticPageBundle:Hit');
        $dataArray['viewingVisitors'] = $pageRepository->countVisitors(60, true);

        $dataArray['success'] = 1;

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Returns HTML of a new widget based on its values.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function updateWidgetFormAction(Request $request)
    {
        $data      = $request->request->get('widget');
        $dataArray = ['success' => 0];

        // Clear params if type is not selected
        if (empty($data['type'])) {
            unset($data['params']);
        }

        $widget   = new Widget();
        $form     = $this->get('form.factory')->create(WidgetType::class, $widget);
        $formHtml = $this->render('MauticDashboardBundle::Widget\\form.html.php',
            ['form' => $form->submit($data)->createView()]
        )->getContent();

        $dataArray['formHtml'] = $formHtml;
        $dataArray['success']  = 1;

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Saves the new ordering of dashboard widgets.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function updateWidgetOrderingAction(Request $request)
    {
        $data = $request->request->get('ordering');
        $repo = $this->getModel('dashboard')->getRepository();
        $repo->updateOrdering(array_flip($data), $this->user->getId());
        $dataArray = ['success' => 1];

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Deletes the entity.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request)
    {
        $objectId  = $request->request->get('widget');
        $dataArray = ['success' => 0];

        // @todo: build permissions
        // if (!$this->get('mautic.security')->isGranted('dashobard:widgets:delete')) {
        //     return $this->accessDenied();
        // }

        /** @var \Mautic\DashboardBundle\Model\DashboardModel $model */
        $model  = $this->getModel('dashboard');
        $entity = $model->getEntity($objectId);
        if ($entity) {
            $model->deleteEntity($entity);
            $name                 = $entity->getName();
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }
}
