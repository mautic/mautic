<?php


/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Integration;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\StagesChangeLog;
use Mautic\StageBundle\Entity\Stage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class HubspotIntegration.
 */
class HubspotIntegration extends CrmAbstractIntegration
{
    /**
     * @var UserHelper
     */
    protected $userHelper;

    /**
     * HubspotIntegration constructor.
     *
     * @param UserHelper $userHelper
     */
    public function __construct(UserHelper $userHelper)
    {
        $this->userHelper = $userHelper;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Hubspot';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            $this->getApiKey() => 'mautic.hubspot.form.apikey',
        ];
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return 'hapikey';
    }

    /**
     * Get the array key for the auth token.
     *
     * @return string
     */
    public function getAuthTokenKey()
    {
        return 'hapikey';
    }

    /**
     * @return array
     */
    public function getFormSettings()
    {
        return [
            'requires_callback'      => false,
            'requires_authorization' => false,
        ];
    }
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'key';
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return 'https://api.hubapi.com';
    }

    /**
     * Get available company fields for choices in the config UI.
     *
     * @param array $settings
     *
     * @return array
     */
    public function getFormCompanyFields($settings = [])
    {
        return $this->getFormFieldsByObject('company', $settings);
    }

    /**
     * @param array $settings
     *
     * @return array|mixed
     */
    public function getFormLeadFields($settings = [])
    {
        return $this->getFormFieldsByObject('contacts', $settings);
    }

    /**
     * @return array|mixed
     */
    public function getAvailableLeadFields($settings = [])
    {
        if ($fields = parent::getAvailableLeadFields()) {
            return $fields;
        }

        $hubsFields        = [];
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;

        if (isset($settings['feature_settings']['objects'])) {
            $hubspotObjects = $settings['feature_settings']['objects'];
        } else {
            $settings       = $this->settings->getFeatureSettings();
            $hubspotObjects = isset($settings['objects']) ? $settings['objects'] : ['contacts'];
        }

        try {
            if ($this->isAuthorized()) {
                if (!empty($hubspotObjects) and is_array($hubspotObjects)) {
                    foreach ($hubspotObjects as $key => $object) {
                        // Check the cache first
                        $settings['cache_suffix'] = $cacheSuffix = '.'.$object;
                        if ($fields = parent::getAvailableLeadFields($settings)) {
                            $hubsFields[$object] = $fields;

                            continue;
                        }

                        $leadFields = $this->getApiHelper()->getLeadFields($object);
                        if (isset($leadFields)) {
                            foreach ($leadFields as $fieldInfo) {
                                $hubsFields[$object][$fieldInfo['name']] = [
                                    'type'     => 'string',
                                    'label'    => $fieldInfo['label'],
                                    'required' => ('email' === $fieldInfo['name']),
                                ];
                            }
                        }

                        $this->cache->set('leadFields'.$cacheSuffix, $hubsFields[$object]);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            if (!$silenceExceptions) {
                throw $e;
            }
        }

        return $hubsFields;
    }

    /**
     * Format the lead data to the structure that HubSpot requires for the createOrUpdate request.
     *
     * @param array $leadData All the lead fields mapped
     *
     * @return array
     */
    public function formatLeadDataForCreateOrUpdate($leadData, $lead, $updateLink = false)
    {
        $formattedLeadData = [];

        if (!$updateLink) {
            foreach ($leadData as $field => $value) {
                if ($field == 'lifecyclestage' || $field == 'associatedcompanyid') {
                    continue;
                }
                $formattedLeadData['properties'][] = [
                    'property' => $field,
                    'value'    => $value,
                ];
            }
        }

        if ($lead && !empty($lead->getId())) {
            //put mautic timeline link
            $formattedLeadData['properties'][] = [
                'property' => 'mautic_timeline',
                'value'    => $this->router->generate(
                    'mautic_plugin_timeline_view',
                    ['integration' => 'Hubspot', 'leadId' => $lead->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }

        return $formattedLeadData;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isAuthorized()
    {
        $keys = $this->getKeys();

        return isset($keys[$this->getAuthTokenKey()]);
    }

    public function getHubSpotApiKey()
    {
        $tokenData = $this->getKeys();

        return $tokenData[$this->getAuthTokenKey()];
    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'features') {
            $builder->add(
                'objects',
                'choice',
                [
                    'choices' => [
                        'contacts' => 'mautic.hubspot.object.contact',
                        'company'  => 'mautic.hubspot.object.company',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.hubspot.form.objects_to_pull_from',
                    'label_attr'  => ['class' => ''],
                    'empty_value' => false,
                    'required'    => false,
                ]
            );
        }
    }

    public function amendLeadDataBeforeMauticPopulate($data, $object)
    {
        foreach ($data['properties'] as $key => $field) {
            $fieldsValues[$key] = $field['value'];
        }
        if ($object == 'Lead' && !isset($fieldsValues['email'])) {
            foreach ($data['identity-profiles'][0]['identities'] as $identifiedProfile) {
                if ($identifiedProfile['type'] == 'EMAIL') {
                    $fieldsValues['email'] = $identifiedProfile['value'];
                }
            }
        }

        return $fieldsValues;
    }

    public function getLeads($params = [], $query = null)
    {
        $executed = null;

        try {
            if ($this->isAuthorized()) {
                $config                         = $this->mergeConfigToFeatureSettings();
                $fields                         = implode('&property=', array_keys($config['leadFields']));
                $params['post_append_to_query'] = '&property='.$fields.'&property=lifecyclestage';

                $data = $this->getApiHelper()->getContacts($params);
                if (isset($data['contacts'])) {
                    foreach ($data['contacts'] as $contact) {
                        if (is_array($contact)) {
                            $contactData = $this->amendLeadDataBeforeMauticPopulate($contact, 'Lead');
                            $contact     = $this->getMauticLead($contactData);
                            if ($contact) {
                                ++$executed;
                            }
                        }
                    }
                    if ($data['has-more']) {
                        $params['vidOffset']  = $data['vid-offset'];
                        $params['timeOffset'] = $data['time-offset'];
                        $this->getLeads($params);
                    }
                }

                return $executed;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $executed;
    }

    public function getCompanies($params = [], $id = false)
    {
        $executed = 0;
        $results  = [];
        try {
            if ($this->isAuthorized()) {
                $data = $this->getApiHelper()->getCompanies($params, $id);
                if ($id) {
                    $results['results'][] = array_merge($results, $data);
                } else {
                    $results['results'] = array_merge($results, $data['results']);
                }
                if (isset($results['results'])) {
                    foreach ($results['results'] as $company) {
                        if (isset($company['properties'])) {
                            $companyData = $this->amendLeadDataBeforeMauticPopulate($company, null);
                            $company     = $this->getMauticCompany($companyData);
                            if ($id) {
                                return $company;
                            }
                            if ($company) {
                                ++$executed;
                            }
                        }
                    }
                    if (isset($data['hasMore']) and $data['hasMore']) {
                        $params['vidOffset']  = $data['vid-offset'];
                        $params['timeOffset'] = $data['time-offset'];
                        $this->getCompanies($params);
                    }
                }

                return $executed;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $executed;
    }

    /**
     * Create or update existing Mautic lead from the integration's profile data.
     *
     * @param mixed       $data        Profile data from integration
     * @param bool|true   $persist     Set to false to not persist lead to the database in this method
     * @param array|null  $socialCache
     * @param mixed||null $identifiers
     *
     * @return Lead
     */
    public function getMauticLead($data, $persist = true, $socialCache = null, $identifiers = null)
    {
        if (is_object($data)) {
            // Convert to array in all levels
            $data = json_encode(json_decode($data), true);
        } elseif (is_string($data)) {
            // Assume JSON
            $data = json_decode($data, true);
        }

        if (isset($data['lifecyclestage'])) {
            $stageName = $data['lifecyclestage'];
            unset($data['lifecyclestage']);
        }

        if (isset($data['associatedcompanyid'])) {
            $company = $this->getCompanies([], $data['associatedcompanyid']);
            unset($data['associatedcompanyid']);
        }

        // Match that data with mapped lead fields
        $matchedFields = $this->populateMauticLeadData($data);

        if (empty($matchedFields)) {
            return;
        }

        // Find unique identifier fields used by the integration
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel           = $this->leadModel;
        $uniqueLeadFields    = $this->fieldModel->getUniqueIdentiferFields();
        $uniqueLeadFieldData = [];

        foreach ($matchedFields as $leadField => $value) {
            if (array_key_exists($leadField, $uniqueLeadFields) && !empty($value)) {
                $uniqueLeadFieldData[$leadField] = $value;
            }
        }

        // Default to new lead
        $lead = new Lead();
        $lead->setNewlyCreated(true);

        if (count($uniqueLeadFieldData)) {
            $existingLeads = $this->em->getRepository('MauticLeadBundle:Lead')
                ->getLeadsByUniqueFields($uniqueLeadFieldData);

            if (!empty($existingLeads)) {
                $lead = array_shift($existingLeads);
            }
        }

        $leadModel->setFieldValues($lead, $matchedFields, false, false);

        // Update the social cache
        $leadSocialCache = $lead->getSocialCache();
        if (!isset($leadSocialCache[$this->getName()])) {
            $leadSocialCache[$this->getName()] = [];
        }

        if (null !== $socialCache) {
            $leadSocialCache[$this->getName()] = array_merge($leadSocialCache[$this->getName()], $socialCache);
        }

        // Check for activity while here
        if (null !== $identifiers && in_array('public_activity', $this->getSupportedFeatures())) {
            $this->getPublicActivity($identifiers, $leadSocialCache[$this->getName()]);
        }

        $lead->setSocialCache($leadSocialCache);

        // Update the internal info integration object that has updated the record
        if (isset($data['internal'])) {
            $internalInfo                   = $lead->getInternal();
            $internalInfo[$this->getName()] = $data['internal'];
            $lead->setInternal($internalInfo);
        }

        if (isset($company)) {
            if (!isset($matchedFields['companyname'])) {
                if (isset($matchedFields['companywebsite'])) {
                    $matchedFields['companyname'] = $matchedFields['companywebsite'];
                }
            }
            $leadModel->addToCompany($lead, $company);
        }

        if (isset($stageName)) {
            $stage = $this->em->getRepository('MauticStageBundle:Stage')->getStageByName($stageName);

            if (empty($stage)) {
                $stage = new Stage();
                $stage->setName($stageName);
                $stages[$stageName] = $stage;
            }
            if (!$lead->getStage() && $lead->getStage() != $stage) {
                $lead->setStage($stage);

                //add a contact stage change log
                $log = new StagesChangeLog();
                $log->setStage($stage);
                $log->setEventName($stage->getId().':'.$stage->getName());
                $log->setLead($lead);
                $log->setActionName(
                    $this->translator->trans(
                        'mautic.stage.import.action.name',
                        [
                            '%name%' => $this->userHelper->getUser()->getUsername(),
                        ]
                    )
                );
                $log->setDateAdded(new \DateTime());
                $lead->stageChangeLog($log);
            }
        }
        $pushData['email'] = $lead->getEmail();
        $this->getApiHelper()->createLead($pushData, $lead, $updateLink = true);
        if ($persist) {
            // Only persist if instructed to do so as it could be that calling code needs to manipulate the lead prior to executing event listeners
            try {
                $leadModel->saveEntity($lead, false);
            } catch (\Exception $exception) {
                $this->logger->addWarning($exception->getMessage());

                return;
            }
        }

        return $lead;
    }
}
