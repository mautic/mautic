<?php

namespace Mautic\PluginBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\PluginBundle\Form\Type\CompanyFieldsType;
use Mautic\PluginBundle\Form\Type\FieldsType;
use Mautic\PluginBundle\Form\Type\IntegrationCampaignsType;
use Mautic\PluginBundle\Form\Type\IntegrationConfigType;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Model\PluginModel;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    public function setIntegrationFilterAction(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $session      = $request->getSession();
        $pluginFilter = (int) $request->get('plugin');
        $session->set('mautic.integrations.filter', $pluginFilter);

        return $this->sendJsonResponse(['success' => 1]);
    }

    /**
     * Get the HTML for list of fields.
     */
    public function getIntegrationFieldsAction(Request $request, IntegrationHelper $helper): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $integration = $request->query->get('integration');
        $settings    = $request->query->all()['settings'] ?? [];
        $page        = $request->query->get('page');

        $dataArray = ['success' => 0];

        if (!empty($integration) && !empty($settings)) {
            /** @var \Mautic\PluginBundle\Integration\AbstractIntegration $integrationObject */
            $integrationObject = $helper->getIntegrationObject($integration);

            if ($integrationObject) {
                if (!$object = $request->attributes->get('object')) {
                    $object = $settings['object'] ?? 'lead';
                }

                $isLead            = ('lead' === $object);
                $integrationFields = ($isLead)
                    ? $integrationObject->getFormLeadFields($settings)
                    : $integrationObject->getFormCompanyFields(
                        $settings
                    );

                if (!empty($integrationFields)) {
                    $session = $request->getSession();
                    $session->set('mautic.plugin.'.$integration.'.'.$object.'.page', $page);

                    /** @var PluginModel $pluginModel */
                    $pluginModel = $this->getModel('plugin');

                    // Get a list of custom form fields
                    $mauticFields       = ($isLead) ? $pluginModel->getLeadFields() : $pluginModel->getCompanyFields();
                    $featureSettings    = $integrationObject->getIntegrationSettings()->getFeatureSettings();
                    $enableDataPriority = $integrationObject->getDataPriority();
                    $formType           = $isLead ? FieldsType::class : CompanyFieldsType::class;
                    $form               = $this->createForm(
                        $formType,
                        $featureSettings[$object.'Fields'] ?? [],
                        [
                            'mautic_fields'        => $mauticFields,
                            'data'                 => $featureSettings,
                            'integration_fields'   => $integrationFields,
                            'csrf_protection'      => false,
                            'integration_object'   => $integrationObject,
                            'enable_data_priority' => $enableDataPriority,
                            'integration'          => $integration,
                            'page'                 => $page,
                            'limit'                => $this->coreParametersHelper->get('default_pagelimit'),
                        ]
                    );

                    $html = $this->render('@MauticCore/Helper/blank_form.html.twig', [
                            'form'      => $form->createView(),
                            'formTheme' => '@MauticPlugin/FormTheme/Integration/layout.html.twig',
                            'function'  => 'row',
                        ]
                    )->getContent();

                    if (!isset($settings['prefix'])) {
                        $prefix = 'integration_details[featureSettings]['.$object.'Fields]';
                    } else {
                        $prefix = $settings['prefix'];
                    }

                    $idPrefix = str_replace(['][', '[', ']'], '_', $prefix);
                    if (str_ends_with($idPrefix, '_')) {
                        $idPrefix = substr($idPrefix, 0, -1);
                    }
                    $html                 = preg_replace('/'.$form->getName().'\[(.*?)\]/', $prefix.'[$1]', $html);
                    $html                 = str_replace($form->getName(), $idPrefix, $html);
                    $dataArray['success'] = 1;
                    $dataArray['html']    = $html;
                }
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Get the HTML for integration properties.
     */
    public function getIntegrationConfigAction(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $integration = $request->query->get('integration');
        $settings    = $request->query->all()['settings'] ?? [];
        $dataArray   = ['success' => 0];

        if (!empty($integration) && !empty($settings)) {
            /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $helper */
            $helper = $this->factory->getHelper('integration');
            /** @var \Mautic\PluginBundle\Integration\AbstractIntegration $object */
            $object = $helper->getIntegrationObject($integration);

            if ($object) {
                $data           = $statusData           = [];
                $objectSettings = $object->getIntegrationSettings();
                $defaults       = $objectSettings->getFeatureSettings();
                if (method_exists($object, 'getCampaigns')) {
                    $campaigns = $object->getCampaigns();
                    if (isset($campaigns['records']) && !empty($campaigns['records'])) {
                        foreach ($campaigns['records'] as $campaign) {
                            $data[$campaign['Id']] = $campaign['Name'];
                        }
                    }
                }
                $form = $this->createForm(IntegrationConfigType::class, $defaults, [
                    'integration'     => $object,
                    'csrf_protection' => false,
                    'campaigns'       => $data,
                ]);

                $html = $this->render('@MauticCore/Helper/blank_form.html.twig', [
                    'form'      => $form->createView(),
                    'function'  => 'widget',
                    'formTheme' => '@MauticPlugin/FormTheme/Integration/layout.html.twig',
                    'variables' => [
                        'integration' => $object,
                    ],
                ])->getContent();

                $prefix   = str_replace('[integration]', '[config]', $settings['name']);
                $idPrefix = str_replace(['][', '[', ']'], '_', $prefix);
                if (str_ends_with($idPrefix, '_')) {
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

    public function getIntegrationCampaignStatusAction(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $integration = $request->query->get('integration');
        $campaign    = $request->query->get('campaign');
        $settings    = $request->query->all()['settings'] ?? [];
        $dataArray   = ['success' => 0];
        $statusData  = [];
        if (!empty($integration) && !empty($campaign)) {
            /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $helper */
            $helper = $this->factory->getHelper('integration');
            /** @var \Mautic\PluginBundle\Integration\AbstractIntegration $object */
            $object = $helper->getIntegrationObject($integration);

            if ($object) {
                if (method_exists($object, 'getCampaignMemberStatus')) {
                    $campaignMemberStatus = $object->getCampaignMemberStatus($campaign);
                    if (isset($campaignMemberStatus['records']) && !empty($campaignMemberStatus['records'])) {
                        foreach ($campaignMemberStatus['records'] as $status) {
                            $statusData[$status['Label']] = $status['Label'];
                        }
                    }
                }
                $form = $this->createForm(IntegrationCampaignsType::class, $statusData, [
                    'csrf_protection'       => false,
                    'campaignContactStatus' => $statusData,
                ]);

                $html = $this->render('@MauticCore/Helper/blank_form.html.twig', [
                    'form'      => $form->createView(),
                    'formTheme' => '@MauticPlugin/FormTheme/Integration/layout.html.twig',
                    'function'  => 'widget',
                    'variables' => [
                        'integration' => $object,
                    ],
                ])->getContent();

                $prefix = str_replace('[integration]', '[campaign_member_status][campaign_member_status]', $settings['name']);

                $idPrefix = str_replace(['][', '[', ']'], '_', $prefix);

                if (str_ends_with($idPrefix, '_')) {
                    $idPrefix = substr($idPrefix, 0, -1);
                }

                $html = preg_replace('/integration_campaign_status_campaign_member_status\[(.*?)\]/', $prefix.'[$1]', $html);
                $html = str_replace('integration_campaign_status_campaign_member_status', $idPrefix, $html);
                $html = str_replace('integration_campaign_status[campaign_member_status]', $prefix, $html);

                $dataArray['success'] = 1;
                $dataArray['html']    = $html;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    public function getIntegrationCampaignsAction(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $integration = $request->query->get('integration');
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

                $html = $this->render('@MauticCore/Helper/blank_form.html.twig', [
                    'form'      => $form->createView(),
                    'formTheme' => '@MauticPlugin/FormTheme/Integration/layout.html.twig',
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

    public function matchFieldsAction(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
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
        $doNotMatchField    = ('-1' === $mautic_field || '' === $mautic_field);
        if ('lead' == $object) {
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

        $pluginModel = $this->getModel('plugin');
        \assert($pluginModel instanceof PluginModel);
        $pluginModel->saveFeatureSettings($entity);

        return $this->sendJsonResponse($dataArray);
    }
}
