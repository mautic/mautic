<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 *
 * @package Mautic\CampaignBundle\Controller
 */
class AjaxController extends CommonAjaxController
{

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function reorderCampaignEventsAction (Request $request)
    {
        $dataArray  = array('success' => 0);
        $session    = $this->factory->getSession();
        $order      = InputHelper::clean($request->request->get('campaign'));
        $components = $session->get('mautic.campaigns.add');
        if (!empty($order) && !empty($components)) {
            $components = array_replace(array_flip($order), $components);
            $session->set('mautic.campaigns.add', $components);
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }

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
            /** @var \Mautic\CampaignBundle\Model\CampaignModel $model */
            $model   = $this->factory->getModel('campaign');
            $actions = $model->getCampaignActions();

            if (isset($actions['actions'][$type])) {
                $formType = (!empty($actions['actions'][$type]['formType'])) ? $actions['actions'][$type]['formType'] :
                    'genericcampaign_settings';

                $form = $this->get('form.factory')->create('campaignaction', array(), array('formType' => $formType));
                $formView = $form->createView();
                $this->factory->getTemplating()->getEngine('MauticCampaignBundle:Campaign:actionform.html.php')->get('form')
                    ->setTheme($formView, 'MauticCampaignBundle:CampaignForm');
                $html = $this->renderView('MauticCampaignBundle:Campaign:actionform.html.php', array(
                    'form' => $formView
                ));
                //replace campaignaction with campaign
                $html = str_replace('campaignaction', 'campaign', $html);
                $dataArray['html']    = $html;
                $dataArray['success'] = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }
}