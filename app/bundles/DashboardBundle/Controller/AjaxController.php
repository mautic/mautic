<?php

namespace Mautic\DashboardBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Form\Type\WidgetType;
use Mautic\DashboardBundle\Model\DashboardModel;
use Mautic\PageBundle\Entity\Hit;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    /**
     * Count how many visitors are currently viewing a page.
     */
    public function viewingVisitorsAction(EntityManagerInterface $entityManager): JsonResponse
    {
        $dataArray = ['success' => 0];

        /** @var \Mautic\PageBundle\Entity\PageRepository $pageRepository */
        $pageRepository               = $entityManager->getRepository(Hit::class);
        $dataArray['viewingVisitors'] = $pageRepository->countVisitors(60, true);

        $dataArray['success'] = 1;

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Returns HTML of a new widget based on its values.
     */
    public function updateWidgetFormAction(Request $request, FormFactoryInterface $formFactory): JsonResponse
    {
        $data      = $request->request->all()['widget'] ?? [];
        $dataArray = ['success' => 0];

        // Clear params if type is not selected
        if (empty($data['type'])) {
            unset($data['params']);
        }

        $widget   = new Widget();
        $form     = $formFactory->create(WidgetType::class, $widget);
        $formHtml = $this->render('@MauticDashboard/Widget/form.html.twig',
            ['form' => $form->submit($data)->createView()]
        )->getContent();

        $dataArray['formHtml'] = $formHtml;
        $dataArray['success']  = 1;

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Saves the new ordering of dashboard widgets.
     */
    public function updateWidgetOrderingAction(Request $request): JsonResponse
    {
        $data           = $request->request->all()['ordering'] ?? [];
        $dashboardModel = $this->getModel('dashboard');
        \assert($dashboardModel instanceof DashboardModel);
        $repo = $dashboardModel->getRepository();
        $repo->updateOrdering(array_flip($data), $this->user->getId());
        $dataArray = ['success' => 1];

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Deletes the entity.
     */
    public function deleteAction(Request $request): JsonResponse
    {
        $objectId  = $request->request->get('widget');
        $dataArray = ['success' => 0];

        // @todo: build permissions
        // if (!$this->security->isGranted('dashobard:widgets:delete')) {
        //     return $this->accessDenied();
        // }

        /** @var DashboardModel $model */
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
