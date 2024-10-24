<?php

namespace Mautic\PointBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\PointBundle\Form\Type\PointActionType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    public function reorderTriggerEventsAction(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $dataArray   = ['success' => 0];
        $session     = $request->getSession();
        $triggerId   = InputHelper::clean($request->request->get('triggerId'));
        $sessionName = 'mautic.point.'.$triggerId.'.triggerevents.modified';
        $order       = InputHelper::clean($request->request->get('triggerEvent'));
        $components  = $session->get($sessionName);

        if (!empty($order) && !empty($components)) {
            $components = array_replace(array_flip($order), $components);
            $session->set($sessionName, $components);
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }

    public function getActionFormAction(Request $request, FormFactoryInterface $formFactory): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $type      = InputHelper::clean($request->request->get('actionType'));
        $dataArray = [
            'success' => 0,
            'html'    => '',
        ];

        if (!empty($type)) {
            // get the HTML for the form
            /** @var \Mautic\PointBundle\Model\PointModel $model */
            $model   = $this->getModel('point');
            $actions = $model->getPointActions();

            if (isset($actions['actions'][$type])) {
                $themes = ['@MauticPoint/FormTheme/Action/_pointaction_properties_row.html.twig'];

                if (!empty($actions['actions'][$type]['formTheme'])) {
                    $themes[] = $actions['actions'][$type]['formTheme'];
                }

                $formType        = (!empty($actions['actions'][$type]['formType'])) ? $actions['actions'][$type]['formType'] : null;
                $formTypeOptions = (!empty($actions['actions'][$type]['formTypeOptions'])) ? $actions['actions'][$type]['formTypeOptions'] : [];
                $form            = $formFactory->create(PointActionType::class, [], ['formType' => $formType, 'formTypeOptions' => $formTypeOptions]);
                $html            = $this->renderView('@MauticPoint/Point/actionform.html.twig', [
                    'form'       => $form->createView(),
                    'formThemes' => $themes,
                ]);

                // replace pointaction with point
                $html                 = str_replace('pointaction', 'point', $html);
                $dataArray['html']    = $html;
                $dataArray['success'] = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }
}
