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
     * Get the HTML for list of fields.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function getIntegrationLeadFieldsAction(Request $request)
    {
        $integration = $request->request->get('integration');
        $settings    = $request->request->get('settings');

        $dataArray = ['success' => 0];

        if (!empty($integration) && !empty($settings)) {
            /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $helper */
            $helper = $this->factory->getHelper('integration');
            /** @var \Mautic\PluginBundle\Integration\AbstractIntegration $object */
            $object = $helper->getIntegrationObject($integration);

            if ($object) {
                $integrationFields = $object->getFormLeadFields($settings);

                if (!empty($integrationFields)) {
                    // Get a list of custom form fields
                    $leadFields                            = $this->getModel('plugin')->getLeadFields();
                    list($specialInstructions, $alertType) = $object->getFormNotes('leadfield_match');
                    $defaults                              = $object->getIntegrationSettings()->getFeatureSettings();
                    $data                                  = isset($defaults['leadFields']) ? $defaults['leadFields'] : [];

                    $form = $this->createForm('integration_fields', $data, [
                        'lead_fields'          => $leadFields,
                        'integration_fields'   => $integrationFields,
                        'csrf_protection'      => false,
                        'special_instructions' => $specialInstructions,
                        'alert_type'           => $alertType,
                    ]);

                    $form = $this->setFormTheme($form, 'MauticCoreBundle:Helper:blank_form.html.php', 'MauticPluginBundle:FormTheme\Integration');

                    $html = $this->render('MauticCoreBundle:Helper:blank_form.html.php', [
                        'form'      => $form,
                        'function'  => 'row',
                        'variables' => [
                            'integration' => $object,
                        ],
                    ])->getContent();

                    if (!isset($settings['prefix'])) {
                        $prefix = 'integration_details[featureSettings][leadFields]';
                    } else {
                        $prefix = $settings['prefix'];
                    }

                    $idPrefix = str_replace(['][', '[', ']'], '_', $prefix);
                    if (substr($idPrefix, -1) == '_') {
                        $idPrefix = substr($idPrefix, 0, -1);
                    }

                    $html = preg_replace('/integration_fields\[(.*?)\]/', $prefix.'[$1]', $html);
                    $html = str_replace('integration_fields', $idPrefix, $html);

                    $dataArray['success'] = 1;
                    $dataArray['html']    = $html;
                }
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Get the HTML for list of fields.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function getIntegrationCompanyFieldsAction(Request $request)
    {
        $integration = $request->request->get('integration');
        $settings    = $request->request->get('settings');

        $dataArray = ['success' => 0];

        if (!empty($integration) && !empty($settings)) {
            /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $helper */
            $helper = $this->get('mautic.helper.integration');
            /** @var \Mautic\PluginBundle\Integration\AbstractIntegration $object */
            $object = $helper->getIntegrationObject($integration);

            if ($object) {
                $integrationFields = $object->getCompanyLeadFields($settings);

                if (!empty($integrationFields)) {
                    // Get a list of custom form fields
                    $defaults                              = $object->getIntegrationSettings()->getFeatureSettings();
                    $companyFields                         = $this->getModel('plugin')->getCompanyFields($defaults);
                    list($specialInstructions, $alertType) = $object->getFormNotes('companyfield_match');
                    $data                                  = isset($defaults['companyFields']) ? $defaults['companyFields'] : [];

                    $form = $this->createForm('integration_company_fields', $data, [
                        'company_fields'             => $companyFields,
                        'integration_company_fields' => $integrationFields,
                        'csrf_protection'            => false,
                        'special_instructions'       => $specialInstructions,
                        'alert_type'                 => $alertType,
                    ]);

                    $form = $this->setFormTheme($form, 'MauticCoreBundle:Helper:blank_form.html.php', 'MauticPluginBundle:FormTheme\Integration');

                    $html = $this->render('MauticCoreBundle:Helper:blank_form.html.php', [
                        'form'      => $form,
                        'function'  => 'row',
                        'variables' => [
                            'integration' => $object,
                        ],
                    ])->getContent();

                    if (!isset($settings['prefix'])) {
                        $prefix = 'integration_details[featureSettings][companyFields]';
                    } else {
                        $prefix = $settings['prefix'];
                    }

                    $idPrefix = str_replace(['][', '[', ']'], '_', $prefix);
                    if (substr($idPrefix, -1) == '_') {
                        $idPrefix = substr($idPrefix, 0, -1);
                    }

                    $html = preg_replace('/integration_fields\[(.*?)\]/', $prefix.'[$1]', $html);
                    $html = str_replace('integration_fields', $idPrefix, $html);

                    $dataArray['success'] = 1;
                    $dataArray['html']    = $html;
                }
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Get the HTML for integration properties.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function getIntegrationConfigAction(Request $request)
    {
        $integration = $request->request->get('integration');
        $settings    = $request->request->get('settings');
        $dataArray   = ['success' => 0];

        if (!empty($integration) && !empty($settings)) {
            /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $helper */
            $helper = $this->factory->getHelper('integration');
            /** @var \Mautic\PluginBundle\Integration\AbstractIntegration $object */
            $object = $helper->getIntegrationObject($integration);

            if ($object) {
                $objectSettings = $object->getIntegrationSettings();
                $defaults       = $objectSettings->getFeatureSettings();

                $form = $this->createForm('integration_config', $defaults, [
                    'integration'     => $object,
                    'csrf_protection' => false,
                ]);

                $form = $this->setFormTheme($form, 'MauticCoreBundle:Helper:blank_form.html.php', 'MauticPluginBundle:FormTheme\Integration');

                $html = $this->render('MauticCoreBundle:Helper:blank_form.html.php', [
                    'form'      => $form,
                    'function'  => 'widget',
                    'variables' => [
                        'integration' => $object,
                    ],
                ])->getContent();

                $prefix   = str_replace('[integration]', '[config]', $settings['name']);
                $idPrefix = str_replace(['][', '[', ']'], '_', $prefix);
                if (substr($idPrefix, -1) == '_') {
                    $idPrefix = substr($idPrefix, 0, -1);
                }

                $html = preg_replace('/integration_config\[(.*?)\]/', $prefix.'[$1]', $html);
                $html = str_replace('integration_config', $idPrefix, $html);

                $dataArray['success'] = 1;
                $dataArray['html']    = $html;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Get the HTML for campaigns list dropdown.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
                $this->factory->getLogger()->addError(print_r($data, true));
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

        $doNotMatchField = ($mautic_field === '-1');
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
            }
            $newFeatureSettings[$integration_field] = $mautic_field;
            if (isset($featureSettings[$fields])) {
                $featureSettings[$fields] = array_merge($featureSettings[$fields], $newFeatureSettings);
            }

            $dataArray = ['success' => 1];
        }

        $entity->setFeatureSettings($featureSettings);

        $this->getModel('plugin')->saveFeatureSettings($entity);

        return $this->sendJsonResponse($dataArray);
    }
}
