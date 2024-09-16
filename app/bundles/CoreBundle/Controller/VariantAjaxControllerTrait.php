<?php

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

trait VariantAjaxControllerTrait
{
    /**
     * @param string $modelName
     * @param string $abSettingsFormName
     * @param string $abSettingsFormBlockPrefix
     * @param string $parentFormName
     * @param string $abFormTemplate
     * @param array  $formThemes
     *
     * @return mixed
     */
    private function getAbTestForm(Request $request, $modelName, $abSettingsFormName, $abSettingsFormBlockPrefix, $parentFormName, $abFormTemplate, $formThemes = [])
    {
        $dataArray = [
            'success' => 0,
            'html'    => '',
        ];
        $type = InputHelper::clean($request->request->get('abKey'));
        $id   = (int) $request->request->get('id');

        if (!empty($type)) {
            //get the HTML for the form
            $model  = $this->getModel($modelName);
            $entity = $model->getEntity($id);

            $abTestComponents = $model->getBuilderComponents($entity, 'abTestWinnerCriteria');
            $abTestSettings   = $abTestComponents['criteria'];

            if (isset($abTestSettings[$type])) {
                $html     = '';
                $formType = (!empty($abTestSettings[$type]['formType'])) ? $abTestSettings[$type]['formType'] : '';
                if (!empty($formType)) {
                    $formOptions = (!empty($abTestSettings[$type]['formTypeOptions'])) ? $abTestSettings[$type]['formTypeOptions'] : [];
                    $form        = $this->get('form.factory')->create(
                        $abSettingsFormName,
                        [],
                        ['formType' => $formType, 'formTypeOptions' => $formOptions]
                    );
                    $html = $this->renderView(
                        $abFormTemplate,
                        [
                            'form' => $this->setFormTheme($form, $formThemes),
                        ]
                    );
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
            }
        }

        return $this->sendJsonResponse($dataArray);
    }
}
