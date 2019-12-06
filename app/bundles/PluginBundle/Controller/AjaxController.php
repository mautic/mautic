<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController.
 */
class AjaxController extends CommonAjaxController
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function setIntegrationFilterAction(Request $request)
    {
        $session      = $this->get('session');
        $pluginFilter = InputHelper::int($this->request->get('plugin'));
        $session->set('mautic.integrations.filter', $pluginFilter);

        return $this->sendJsonResponse(['success' => 1]);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *
     * @throws \Exception
     */
    protected function getIntegrationCampaignsAction(Request $request)
    {
        $integration = $request->request->get('integration');
        $dataArray   = ['success' => 0];

        if (!empty($integration)) {
            /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $helper */
            $helper = $this->factory->getHelper('integration');
            /** @var \Mautic\PluginBundle\Integration\AbstractIntegration $object */
            $object = $helper->getIntegrationObject($integration);
            $data   = [];
            if ($object) {
                $campaigns = $object->getCampaigns();
                if (isset($campaigns['records']) && !empty($campaigns['records'])) {
                    foreach ($campaigns['records'] as $campaign) {
                        $data[$campaign['Id']] = $campaign['Name'];
                    }
                }
                $form = $this->createForm('integration_campaigns', $data, [
                    'integration'     => $integration,
                    'campaigns'       => $data,
                    'csrf_protection' => false,
                ]);

                $form = $this->setFormTheme($form, 'MauticCoreBundle:Helper:blank_form.html.php', 'MauticPluginBundle:FormTheme\Integration');

                $html = $this->render('MauticCoreBundle:Helper:blank_form.html.php', [
                    'form'      => $form,
                    'function'  => 'row',
                    'variables' => [
                        'campaigns'   => $data,
                        'integration' => $object,
                    ],
                ])->getContent();

                $dataArray['success'] = 1;
                $dataArray['html']    = $html;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    protected function matchFieldsAction(Request $request)
    {
        $integration       = $request->request->get('integration');
        $integration_field = $request->request->get('integrationField');
        $mautic_field      = $request->request->get('mauticField');
        $update_mautic     = $request->request->get('updateMautic');
        $object            = $request->request->get('object');

        $helper             = $this->factory->getHelper('integration');
        $integration_object = $helper->getIntegrationObject($integration);
        $entity             = $integration_object->getIntegrationSettings();
        $featureSettings    = $entity->getFeatureSettings();
        $doNotMatchField    = ($mautic_field === '-1' || $mautic_field === '');
        if ($object == 'lead') {
            $fields       = 'leadFields';
            $updateFields = 'update_mautic';
        } else {
            $fields       = 'companyFields';
            $updateFields = 'update_mautic_company';
        }
        $newFeatureSettings = [];
        if ($doNotMatchField) {
            if (isset($featureSettings[$updateFields]) && array_key_exists($integration_field, $featureSettings[$updateFields])) {
                unset($featureSettings[$updateFields][$integration_field]);
            }
            if (isset($featureSettings[$fields]) && array_key_exists($integration_field, $featureSettings[$fields])) {
                unset($featureSettings[$fields][$integration_field]);
            }
            $dataArray = ['success' => 0];
        } else {
            $newFeatureSettings[$integration_field] = $update_mautic;
            if (isset($featureSettings[$updateFields])) {
                $featureSettings[$updateFields] = array_merge($featureSettings[$updateFields], $newFeatureSettings);
            } else {
                $featureSettings[$updateFields] = $newFeatureSettings;
            }
            $newFeatureSettings[$integration_field] = $mautic_field;
            if (isset($featureSettings[$fields])) {
                $featureSettings[$fields] = array_merge($featureSettings[$fields], $newFeatureSettings);
            } else {
                $featureSettings[$fields] = $newFeatureSettings;
            }

            $dataArray = ['success' => 1];
        }
        $entity->setFeatureSettings($featureSettings);

        $this->getModel('plugin')->saveFeatureSettings($entity);

        return $this->sendJsonResponse($dataArray);
    }
}
