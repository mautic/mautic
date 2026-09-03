<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\StageBundle\Form\Type\StageActionType;
use Mautic\StageBundle\Model\StageModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Environment;

final class AjaxController extends CommonAjaxController
{
    private StageModel $stageModel;

    #[Required]
    public function autowireStageAjaxController(
        StageModel $stageModel,
    ): void {
        $this->stageModel = $stageModel;
    }

    public function getActionFormAction(Request $request, Environment $twig): JsonResponse
    {
        $dataArray = [
            'success' => 0,
            'html'    => '',
        ];
        $type = InputHelper::clean($request->request->get('actionType'));

        if (!empty($type)) {
            $actions = $this->stageModel->getStageActions();

            if (isset($actions['actions'][$type])) {
                $themes = ['MauticStageBundle:FormTheme\Action'];
                if (!empty($actions['actions'][$type]['formTheme'])) {
                    $themes[] = $actions['actions'][$type]['formTheme'];
                }
                $formType        = (!empty($actions['actions'][$type]['formType'])) ? $actions['actions'][$type]['formType'] : 'genericstage_settings';
                $formTypeOptions = (!empty($actions['actions'][$type]['formTypeOptions'])) ? $actions['actions'][$type]['formTypeOptions'] : [];

                $form = $this->createForm(StageActionType::class, [], ['formType' => $formType, 'formTypeOptions' => $formTypeOptions]);
                $html = $this->renderView('@MauticStage/Stage/actionform.html.twig', [
                    'form' => $this->setFormTheme($form, $twig, $themes),
                ]);

                $html                 = str_replace('stageaction', 'stage', $html);
                $dataArray['html']    = $html;
                $dataArray['success'] = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }
}
