<?php

namespace Mautic\StageBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\StageBundle\Cache\StageCountCache;
use Mautic\StageBundle\Form\Type\StageActionType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController.
 */
class AjaxController extends CommonAjaxController
{
    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getActionFormAction(Request $request, FormFactoryInterface $formFactory)
    {
        $dataArray = [
            'success' => 0,
            'html'    => '',
        ];
        $type = InputHelper::clean($request->request->get('actionType'));

        if (!empty($type)) {
            // get the HTML for the form
            /** @var \Mautic\StageBundle\Model\StageModel $model */
            $model   = $this->getModel('stage');
            $actions = $model->getStageActions();

            if (isset($actions['actions'][$type])) {
                $themes = ['MauticStageBundle:FormTheme\Action'];
                if (!empty($actions['actions'][$type]['formTheme'])) {
                    $themes[] = $actions['actions'][$type]['formTheme'];
                }
                $formType        = (!empty($actions['actions'][$type]['formType'])) ? $actions['actions'][$type]['formType'] : 'genericstage_settings';
                $formTypeOptions = (!empty($actions['actions'][$type]['formTypeOptions'])) ? $actions['actions'][$type]['formTypeOptions'] : [];

                $form = $formFactory->create(StageActionType::class, [], ['formType' => $formType, 'formTypeOptions' => $formTypeOptions]);
                $html = $this->renderView('@MauticStage/Stage/actionform.html.twig', [
                    'form' => $this->setFormTheme($form, '@MauticStage/Stage/actionform.html.twig', $themes),
                ]);

                $html                 = str_replace('stageaction', 'stage', $html);
                $dataArray['html']    = $html;
                $dataArray['success'] = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @throws Exception
     */
    protected function getLeadCountAction(Request $request): JsonResponse
    {
        $id = (int) InputHelper::clean($request->request->get('id'));

        /** @var StageCountCache $councCache */
        $countCache   = $this->get('mautic.stage.cache.stage_count');

        $leadCount = $countCache->getStageContactCount($id);

        return new JsonResponse($this->prepareJsonResponse($leadCount));
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareJsonResponse(int $leadCount): array
    {
        return [
            'html' => $this->translator->transChoice(
                'mautic.lead.list.viewleads_count',
                $leadCount,
                ['%count%' => $leadCount]
            ),
            'leadCount' => $leadCount,
        ];
    }
}
