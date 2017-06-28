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

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Form\FormBuilder;

/**
 * Class ZohoIntegration.
 */
class ZohoIntegration extends CrmAbstractIntegration
{
    /**
     * Returns the name of the social integration that must match the name of the file.
     *
     * @return string
     */
    public function getName()
    {
        return 'Zoho';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            $this->getClientIdKey()     => 'mautic.zoho.form.email',
            $this->getClientSecretKey() => 'mautic.zoho.form.password',
        ];
    }

    /**
     * @return string
     */
    public function getClientIdKey()
    {
        return 'EMAIL_ID';
    }

    /**
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'PASSWORD';
    }

    /**
     * @return string
     */
    public function getAuthTokenKey()
    {
        return 'AUTHTOKEN';
    }

    /**
     * @return string
     */
    public function getDatacenter()
    {
        $featureSettings = $this->getKeys();

        return !empty($featureSettings['datacenter']) ? $featureSettings['datacenter'] : 'zoho.com';
    }

    /**
     * @param bool $isJson
     *
     * @return string
     */
    public function getApiUrl($isJson = true)
    {
        return sprintf('https://crm.%s/crm/private/%s', $this->getDatacenter(), $isJson ? 'json' : 'xml');
    }

    /**
     * @return array
     */
    public function getFormSettings()
    {
        return [
            'requires_callback'      => false,
            'requires_authorization' => true,
        ];
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead', 'get_leads', 'push_leads'];
    }

    /**
     * @param $rows
     *
     * @return array
     */
    protected function formatZohoData($rows)
    {
        if (isset($rows['FL'])) {
            $rows = [$rows];
        }
        $fieldsValues = [];
        foreach ($rows as $row) {
            if (isset($row['FL'])) {
                $fl = $row['FL'];
                if (isset($fl['val'])) {
                    $fl = [$fl];
                }
                foreach ($fl as $field) {
                    $fieldsValues[$row['no'] - 1][$field['val']] = $field['content'];
                }
            }
        }

        return $fieldsValues;
    }

    /**
     * Amend mapped lead data before creating to Mautic.
     *
     * @param array  $data
     * @param string $object
     *
     * @return array
     */
    public function amendLeadDataBeforeMauticPopulate($data, $object = null)
    {
        if ('company' === $object) {
            $object = 'Accounts';
        }

        $result = [];
        if (isset($data[$object])) {
            $entity = null;
            /** @var IntegrationEntityRepository $integrationEntityRepo */
            $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
            $rows                  = $data[$object];
            /** @var array $rows */
            foreach ($rows as $row) {
                if (is_array($row)) {
                    $objects             = $this->formatZohoData($row);
                    $integrationEntities = [];
                    /** @var array $objects */
                    foreach ($objects as $recordId => $entityData) {
                        if ('Accounts' === $object) {
                            $recordId = $entityData['ACCOUNTID'];
                            /** @var Company $entity */
                            $entity = $this->getMauticCompany($entityData);
                            if ($entity) {
                                $result[] = $entity->getName();
                            }
                            $mauticObjectReference = 'company';
                        } elseif ('Leads' === $object || 'Contacts' === $object) {
                            $recordId = ('Leads' === $object) ? $entityData['LEADID'] : $entityData['CONTACTID'];
                            /** @var Lead $entity */
                            $entity = $this->getMauticLead($entityData);
                            if ($entity) {
                                $result[] = $entity->getEmail();
                            }
                            $mauticObjectReference = 'lead';
                        } else {
                            $this->logIntegrationError(
                                new \Exception(
                                    sprintf('Received an unexpected object "%s"', $object)
                                )
                            );
                            continue;
                        }

                        if ($entity) {
                            $integrationId = $integrationEntityRepo->getIntegrationsEntityId(
                                'Zoho',
                                $object,
                                $mauticObjectReference,
                                $entity->getId()
                            );

                            if ($integrationId == null) {
                                $integrationEntity = new IntegrationEntity();
                                $integrationEntity->setDateAdded(new \DateTime());
                                $integrationEntity->setIntegration('Zoho');
                                $integrationEntity->setIntegrationEntity($object);
                                $integrationEntity->setIntegrationEntityId($recordId);
                                $integrationEntity->setInternalEntity($mauticObjectReference);
                                $integrationEntity->setInternalEntityId($entity->getId());
                                $integrationEntities[] = $integrationEntity;
                            } else {
                                $integrationEntity = $integrationEntityRepo->getEntity($integrationId[0]['id']);
                                $integrationEntity->setLastSyncDate(new \DateTime());
                                $integrationEntities[] = $integrationEntity;
                            }
                            $this->em->detach($entity);
                            unset($entity);
                        } else {
                            continue;
                        }
                    }

                    $this->em->getRepository('MauticPluginBundle:IntegrationEntity')->saveEntities($integrationEntities);
                    $this->em->clear('Mautic\PluginBundle\Entity\IntegrationEntity');
                }
            }
            unset($integrationEntities);
        }

        return $result;
    }

    /**
     * @param array      $params
     * @param null|array $query
     *
     * @return int|null
     */
    public function getLeads($params = [], $query = null, &$executed = null, &$result = [], $object = 'Leads')
    {
        if ('Lead' === $object || 'Contact' === $object) {
            $object .= 's'; // pluralize object name for Zoho
        }

        $executed = 0;

        try {
            if ($this->isAuthorized()) {
                $config           = $this->mergeConfigToFeatureSettings();
                $fields           = $config['leadFields'];
                $config['object'] = $object;
                $aFields          = $this->getAvailableLeadFields($config);
                $mappedData       = [];

                foreach (array_keys($fields) as $k) {
                    if (isset($aFields[$object][$k])) {
                        $mappedData[] = $aFields[$object][$k]['dv'];
                    }
                }

                $fields = implode(',', $mappedData);

                $params['selectColumns'] = $object.'('.$fields.')';
                $params['toIndex']       = 200; // maximum number of records
                $data                    = $this->getApiHelper()->getLeads($params, $object);
                $result                  = $this->amendLeadDataBeforeMauticPopulate($data, $object);
                $executed += count($result);
//                // TODO: fetch more records using fromIndex and toIndex until exception is thrown
//                if ($data['has-more']) {
//                    $executed += $this->getLeads($params, $object);
//                }
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $executed;
    }

    /**
     * @param array      $params
     * @param null|array $query
     *
     * @return int|null
     */
    public function getCompanies($params = [], $query = null, &$executed = null, &$result = [])
    {
        $executed = 0;
        $object   = 'company';

        try {
            if ($this->isAuthorized()) {
                $config           = $this->mergeConfigToFeatureSettings();
                $fields           = $config['companyFields'];
                $config['object'] = $object;
                $aFields          = $this->getAvailableLeadFields($config);
                $mappedData       = [];
                if (isset($aFields['company'])) {
                    $aFields = $aFields['company'];
                }
                foreach (array_keys($fields) as $k) {
                    $mappedData[] = $aFields[$k]['dv'];
                }

                $fields = implode(',', $mappedData);

                $params['selectColumns'] = 'Accounts('.$fields.')';
                $params['toIndex']       = 200; // maximum number of records

                $data   = $this->getApiHelper()->getCompanies($params);
                $result = $this->amendLeadDataBeforeMauticPopulate($data, $object);
                $executed += count($result);
//              //TODO: fetch more records using fromIndex and toIndex until exception is thrown
//              if (isset($data['hasMore']) && $data['hasMore']) {
//                  $executed += $this->getCompanies($params);
//              }

                return $executed;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $executed;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data
     * @param array $config
     * @param null  $object
     *
     * @return array
     */
    public function populateMauticLeadData($data, $config = [], $object = 'Leads')
    {
        // Match that data with mapped lead fields
        $aFields       = $this->getAvailableLeadFields($config);
        $matchedFields = [];

        $fieldsName = ('company' === $object) ? 'companyFields' : 'leadFields';

        if (isset($aFields[$object])) {
            $aFields = $aFields[$object];
        }
        foreach ($aFields as $k => $v) {
            foreach ($data as $dk => $dv) {
                if ($dk === $v['dv']) {
                    $matchedFields[$config[$fieldsName][$k]] = $dv;
                }
            }
        }

        return $matchedFields;
    }

    /**
     * @param Form|FormBuilder $builder
     * @param array            $data
     * @param string           $formArea
     *
     * @throws \InvalidArgumentException
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea === 'keys') {
            $builder->add(
                'datacenter',
                'choice',
                [
                    'choices' => [
                        'zoho.com'    => 'mautic.plugin.zoho.zone_us',
                        'zoho.eu'     => 'mautic.plugin.zoho.zone_europe',
                        'zoho.co.jp'  => 'mautic.plugin.zoho.zone_japan',
                        'zoho.com.cn' => 'mautic.plugin.zoho.zone_china',
                    ],
                    'label'       => 'mautic.plugin.zoho.zone_select',
                    'empty_value' => false,
                    'required'    => true,
                    'attr'        => [
                        'tooltip' => 'mautic.plugin.zoho.zone.tooltip',
                    ],
                ]
            );
        } elseif ('features' === $formArea) {
            $builder->add(
                'objects',
                'choice',
                [
                    'choices' => [
                        'Leads'    => 'mautic.zoho.object.lead',
                        'Contacts' => 'mautic.zoho.object.contact',
                        'company'  => 'mautic.zoho.object.account',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => $this->getTranslator()->trans('mautic.crm.form.objects_to_pull_from', ['%crm%' => 'Zoho']),
                    'label_attr'  => ['class' => ''],
                    'empty_value' => false,
                    'required'    => false,
                ]
            );
        }
    }

    /**
     * @param array $settings
     * @param array $parameters
     *
     * @return bool|string
     *
     * @throws \InvalidArgumentException
     */
    public function authCallback($settings = [], $parameters = [])
    {
        $request_url = sprintf('https://accounts.%s/apiauthtoken/nb/create', $this->getDatacenter());
        $parameters  = array_merge($parameters, [
            'SCOPE'    => 'ZohoCRM/crmapi',
            'EMAIL_ID' => $this->keys[$this->getClientIdKey()],
            'PASSWORD' => $this->keys[$this->getClientSecretKey()],
        ]);

        $response = $this->makeRequest($request_url, $parameters, 'POST', ['authorize_session' => true]);

        if ($response['RESULT'] == 'FALSE') {
            return $this->translator->trans('mautic.zoho.auth_error', ['%cause%' => (isset($response['CAUSE']) ? $response['CAUSE'] : 'UNKNOWN')]);
        }

        return $this->extractAuthKeys($response);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $data
     * @param bool   $postAuthorization
     *
     * @return mixed
     */
    public function parseCallbackResponse($data, $postAuthorization = false)
    {
        if ($postAuthorization) {
            /*
            #
            #Wed Feb 29 03:07:33 PST 2012
            AUTHTOKEN=bad18eba1ff45jk7858b8ae88a77fa30
            RESULT=TRUE
            */
            preg_match_all('(\w*=\w*)', $data, $matches);
            if (!empty($matches[0])) {
                /** @var array $match */
                $match      = $matches[0];
                $attributes = [];
                foreach ($match as $string_attribute) {
                    $parts                 = explode('=', $string_attribute);
                    $attributes[$parts[0]] = $parts[1];
                }

                return $attributes;
            }

            return [];
        }

        return parent::parseCallbackResponse($data, $postAuthorization);
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
        return $this->getFormFieldsByObject('Accounts', $settings);
    }

    /**
     * @param array $settings
     *
     * @return array|mixed
     */
    public function getFormLeadFields($settings = [])
    {
        $leadFields    = $this->getFormFieldsByObject('Leads', $settings);
        $contactFields = $this->getFormFieldsByObject('Contacts', $settings);

        return array_merge($leadFields, $contactFields);
    }

    /**
     * @param array $settings
     *
     * @return array|bool
     *
     * @throws ApiErrorException
     */
    public function getAvailableLeadFields($settings = [])
    {
        if ($fields = parent::getAvailableLeadFields($settings)) {
            return $fields;
        }

        $zohoFields        = [];
        $silenceExceptions = isset($settings['silence_exceptions']) ? $settings['silence_exceptions'] : true;

        if (isset($settings['feature_settings']['objects'])) {
            $zohoObjects = $settings['feature_settings']['objects'];
        } else {
            $settings    = $this->settings->getFeatureSettings();
            $zohoObjects = isset($settings['objects']) ? $settings['objects'] : ['Leads'];
        }

        try {
            if ($this->isAuthorized()) {
                if (!empty($zohoObjects) && is_array($zohoObjects)) {
                    foreach ($zohoObjects as $key => $zohoObject) {
                        // Check the cache first
                        $settings['cache_suffix'] = $cacheSuffix = '.'.$zohoObject;
                        if ($fields = parent::getAvailableLeadFields($settings)) {
                            $zohoFields[$zohoObject] = $fields;

                            continue;
                        }
                        $leadObject = $this->getApiHelper()->getLeadFields($zohoObject);

                        if (null === $leadObject || (isset($leadObject['response']) && isset($leadObject['response']['error']))) {
                            return [];
                        }

                        $objKey = 'company' === $zohoObject ? 'Accounts' : $zohoObject;
                        /** @var array $opts */
                        $opts = $leadObject[$objKey]['section'];
                        foreach ($opts as $optgroup) {
                            if (!array_key_exists(0, $optgroup['FL'])) {
                                $optgroup['FL'] = [$optgroup['FL']];
                            }
                            foreach ($optgroup['FL'] as $field) {
                                if (!(bool) $field['isreadonly'] || in_array($field['type'], ['Lookup', 'OwnerLookup', 'Boolean'], true)) {
                                    continue;
                                }

                                $zohoFields[$zohoObject][$this->getFieldKey($field['dv'])] = [
                                    'type'     => 'string',
                                    'label'    => $field['label'],
                                    'dv'       => $field['dv'],
                                    'required' => $field['req'] === 'true',
                                ];
                            }
                        }

                        $this->cache->set('leadFields'.$cacheSuffix, $zohoFields[$zohoObject]);
                    }
                }
            }
        } catch (ApiErrorException $exception) {
            $this->logIntegrationError($exception);

            if (!$silenceExceptions) {
                if (strpos($exception->getMessage(), 'Invalid Ticket Id') !== false) {
                    // Use a bit more friendly message
                    $exception = new ApiErrorException('There was an issue with communicating with Zoho. Please try to reauthorize.');
                }

                throw $exception;
            }

            return false;
        }

        return $zohoFields;
    }

    /**
     * {@inheritdoc}
     *
     * @param $key
     * @param $field
     *
     * @return array
     */
    public function convertLeadFieldKey($key, $field)
    {
        return [$key, $field['dv']];
    }

    /**
     * @param $lead
     * @param $config
     *
     * @return string
     */
    public function populateLeadData($lead, $config = [])
    {
        $config['object'] = 'Leads';
        $mappedData       = parent::populateLeadData($lead, $config);

        $xmlData = '<Leads>';
        $xmlData .= '<row no="1">';
        foreach ($mappedData as $name => $value) {
            $xmlData .= sprintf('<FL val="%s"><![CDATA[%s]]></FL>', $name, $this->cleanPushData($value));
        }
        $xmlData .= '</row>';
        $xmlData .= '</Leads>';

        return $xmlData;
    }

    /**
     * @param $dv
     *
     * @return string
     */
    protected function getFieldKey($dv)
    {
        return InputHelper::alphanum(InputHelper::transliterate($dv));
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    public function pushLeads($params = [])
    {
        $limit                 = $params['limit'];
        $config                = $this->mergeConfigToFeatureSettings();
        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
        $fieldsToUpdateInZoho  = isset($config['update_mautic']) ? array_keys($config['update_mautic'], 0) : [];
        $leadFields            = array_unique(array_values($config['leadFields']));
        $totalUpdated          = $totalCreated          = $totalErrors          = 0;

        if (empty($leadFields)) {
            return [0, 0, 0];
        }

        $fields = implode(', l.', $leadFields);
        $fields = 'l.'.$fields;

        $availableFields = $this->getAvailableLeadFields(['feature_settings' => ['objects' => ['Leads', 'Contacts']]]);
        $leadFieldsZ     = array_intersect_key($availableFields['Leads'], array_flip($fieldsToUpdateInZoho));
        $contactFieldsZ  = array_intersect_key($availableFields['Contacts'], array_flip($fieldsToUpdateInZoho));

        $originalLimit = $limit;
        $progress      = false;
        $totalToUpdate = array_sum($integrationEntityRepo->findLeadsToUpdate('Zoho', 'lead', $fields, false, null, null, ['Contacts', 'Leads']));
        $totalToCreate = $integrationEntityRepo->findLeadsToCreate('Zoho', $fields, false, null, null, 'i.last_sync_date is not null');
        $totalCount    = $totalToCreate + $totalToUpdate;

        if (defined('IN_MAUTIC_CONSOLE')) {
            // start with update
            if ($totalToUpdate + $totalToCreate) {
                $output = new ConsoleOutput();
                $output->writeln("About $totalToUpdate to update and about $totalToCreate to create/update");
                $progress = new ProgressBar($output, $totalCount);
            }
        }

        // Start with contacts so we know who is a contact when we go to process converted leads
        $limit               = $originalLimit;
        $leadsToSync         = [];
        $isContact           = [];
        $integrationEntities = [];

        // Fetch them separately so we can determine which oneas are already there
        $toUpdate = $integrationEntityRepo->findLeadsToUpdate('Zoho', 'lead', $fields, $limit, null, null, 'Contacts', [])['Contacts'];

        $contactCount = count($toUpdate);
        $totalCount -= count($toUpdate);
        $totalUpdated += count($toUpdate);

        foreach ($toUpdate as $lead) {
            if (isset($lead['email']) && !empty($lead['email'])) {
                $key               = mb_strtolower($this->cleanPushData($lead['email']));
                $leadsToSync[$key] = $lead;
                $isContact[$key]   = $lead['id'];
            }
        }

        // Switch to Lead
        $toUpdate = $integrationEntityRepo->findLeadsToUpdate('Zoho', 'lead', $fields, $limit, null, null,  'Leads', [])['Leads'];

        $leadCount = count($toUpdate);
        $totalCount -= count($toUpdate);
        $totalUpdated += count($toUpdate);

        foreach ($toUpdate as $lead) {
            if (isset($lead['email']) && !empty($lead['email'])) {
                $key = mb_strtolower($this->cleanPushData($lead['email']));
                if (isset($isContact[$key])) {
                    // We already know this is a converted contact so just ignore it
                    $integrationEntity     = $this->em->getReference('MauticPluginBundle:IntegrationEntity', $lead['id']);
                    $integrationEntities[] = $integrationEntity->setInternalEntity('lead-converted');
                } else {
                    $leadsToSync[$key] = $lead;
                }
            }
        }

        unset($toUpdate);

        //create lead records, including deleted on Zoho side (last_sync = null)
        $leadsToCreate = $integrationEntityRepo->findLeadsToCreate('Zoho', $fields, $limit, null, null, 'i.last_sync_date is not null');
        $totalCount -= count($leadsToCreate);
        $totalCreated += count($leadsToCreate);
        foreach ($leadsToCreate as $lead) {
            if (isset($lead['email']) && !empty($lead['email'])) {
                $key                        = mb_strtolower($this->cleanPushData($lead['email']));
                $lead['integration_entity'] = 'Leads';
                $leadsToSync[$key]          = $lead;
                /** @var IntegrationEntity $integrationEntity */
                $integrationEntity     = $this->em->getReference('MauticPluginBundle:IntegrationEntity', $lead['id']);
                $integrationEntities[] = $integrationEntity->setLastSyncDate(new \DateTime());
            }
        }
        unset($leadsToCreate);

        if ($integrationEntities) {
            // Persist updated entities if applicable
            $integrationEntityRepo->saveEntities($integrationEntities);
            $this->em->clear(IntegrationEntity::class);
        }

        // create leads and contacts
        foreach (['Leads', 'Contacts'] as $zObject) {
            $rowid      = 1;
            $mappedData = [];
            $xmlData    = '<'.$zObject.'>';
            foreach ($leadsToSync as $email => $lead) {
                if ($zObject !== $lead['integration_entity']) {
                    continue;
                }
                if ($progress) {
                    $progress->advance();
                }
                $xmlData .= '<row no="'.($rowid++).'">';
                // Match that data with mapped lead fields
                foreach ($config['leadFields'] as $k => $v) {
                    foreach ($lead as $dk => $dv) {
                        if ($v === $dk) {
                            if ($dv) {
                                if (isset($availableFields[$zObject][$k])) {
                                    $mappedData[$availableFields[$zObject][$k]['dv']] = $dv;
                                }
                            }
                        }
                    }
                }
                foreach ($mappedData as $name => $value) {
                    $xmlData .= sprintf('<FL val="%s"><![CDATA[%s]]></FL>', $name, $this->cleanPushData($value));
                }
                $xmlData .= '</row>';
            }
            $xmlData .= '</'.$zObject.'>';

            if ($rowid > 1) {
                $this->getApiHelper()->createLead($xmlData, null, $zObject);
            }
        }

        if ($progress) {
            $progress->finish();
            $output->writeln('');
        }

        return [$totalUpdated, $totalCreated, $totalErrors];
    }

    /**
     * Get if data priority is enabled in the integration or not default is false.
     *
     * @return string
     */
    public function getDataPriority()
    {
        return true;
    }
}
