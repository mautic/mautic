<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 */
class AjaxController extends CommonAjaxController
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function getActionFormAction(Request $request)
    {
        $dataArray = array(
            'success' => 0,
            'html'    => ''
        );
        $type      = InputHelper::clean($request->request->get('actionType'));

        if (!empty($type)) {
            //get the HTML for the form
            /** @var \Mautic\StageBundle\Model\StageModel $model */
            $model   = $this->getModel('stage');
            $actions = $model->getStageActions();

            if (isset($actions['actions'][$type])) {
                $themes = array('MauticStageBundle:FormTheme\Action');
                if (!empty($actions['actions'][$type]['formTheme'])) {
                    $themes[] = $actions['actions'][$type]['formTheme'];
                }
                $formType        = (!empty($actions['actions'][$type]['formType'])) ? $actions['actions'][$type]['formType'] : 'genericstage_settings';
                $formTypeOptions = (!empty($actions['actions'][$type]['formTypeOptions'])) ? $actions['actions'][$type]['formTypeOptions'] : array();

                $form            = $this->get('form.factory')->create('stageaction', array(), array('formType' => $formType, 'formTypeOptions' => $formTypeOptions));
                $html            = $this->renderView('MauticStageBundle:Stage:actionform.html.php', array(
                    'form' => $this->setFormTheme($form, 'MauticStageBundle:Stage:actionform.html.php', $themes)
                ));

                $html                 = str_replace('stageaction', 'stage', $html);
                $dataArray['html']    = $html;
                $dataArray['success'] = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }
}
