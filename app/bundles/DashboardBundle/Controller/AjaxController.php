<?php

namespace Mautic\DashboardBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Entity\WidgetRepository;
use Mautic\DashboardBundle\Form\Type\WidgetType;
use Mautic\DashboardBundle\Model\DashboardModel;
use Mautic\PageBundle\Entity\HitRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\Attribute\Required;

final class AjaxController extends CommonAjaxController
{
    private WidgetRepository $widgetRepository;

    private DashboardModel $dashboardModel;

    private HitRepository $hitRepository;

    #[Required]
    public function autowireDashboardAjaxController(
        WidgetRepository $widgetRepository,
        DashboardModel $dashboardModel,
        HitRepository $hitRepository,
    ): void {
        $this->dashboardModel = $dashboardModel;
        $this->widgetRepository = $widgetRepository;
        $this->hitRepository = $hitRepository;
    }

    /**
     * Count how many visitors are currently viewing a page.
     */
    public function viewingVisitorsAction(): JsonResponse
    {
        $dataArray = ['success' => 0];

        $dataArray['viewingVisitors'] = $this->hitRepository->countVisitors(60, true);
        $dataArray['success'] = 1;

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Returns HTML of a new widget based on its values.
     */
    public function updateWidgetFormAction(Request $request): JsonResponse
    {
        $data      = $request->request->all()['widget'] ?? [];
        $dataArray = ['success' => 0];

        // Clear params if type is not selected
        if (empty($data['type'])) {
            unset($data['params']);
        }

        $widget   = new Widget();

        $form     = $this->createForm(WidgetType::class, $widget);
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
        $data = $request->request->all()['ordering'] ?? [];

        $this->widgetRepository->updateOrdering(array_flip($data), $this->user->getId());
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
        $entity = $this->dashboardModel->getEntity($objectId);
        if ($entity) {
            $this->dashboardModel->deleteEntity($entity);
            $name                 = $entity->getName();
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }
}
