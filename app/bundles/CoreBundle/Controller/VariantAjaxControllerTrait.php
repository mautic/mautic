<?php

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\HttpFoundation\Request;

trait VariantAjaxControllerTrait
{
    /**
     * @return mixed[]
     */
    private function getAbTestForm(Request $request, EmailModel|PageModel $model, callable $buildForm, callable $renderView, string $abSettingsFormBlockPrefix, string $parentFormName): array
    {
        $dataArray = ['success' => 0, 'html' => ''];
        $type      = InputHelper::clean($request->request->get('abKey'));
        $id        = (int) $request->request->get('id');

        if (empty($type)) {
            return $dataArray;
        }

        // get the HTML for the form
        $entity           = $model->getEntity($id);
        $abTestComponents = $model->getBuilderComponents($entity, 'abTestWinnerCriteria');
        $abTestSettings   = $abTestComponents['criteria'];

        if (!isset($abTestSettings[$type])) {
            return $dataArray;
        }

        $html     = '';
        $formType = $abTestSettings[$type]['formType'] ?? '';
        if (!empty($formType)) {
            $formOptions = $abTestSettings[$type]['formTypeOptions'] ?? [];
            $html        = $renderView($buildForm($formType, $formOptions));
        }

        $html = str_replace(
            [
                "{$abSettingsFormBlockPrefix}[",
                "{$abSettingsFormBlockPrefix}_",
                $abSettingsFormBlockPrefix,
            ],
            [
                "{$parentFormName}[variantSettings][",
                "{$parentFormName}_variantSettings_",
                $parentFormName,
            ],
            $html
        );
        $dataArray['html']    = $html;
        $dataArray['success'] = 1;

        return $dataArray;
    }
}
