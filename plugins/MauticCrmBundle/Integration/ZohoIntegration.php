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
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
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
        } elseif ('Lead' === $object || 'Contact' === $object) {
            $object .= 's'; // pluralize object name for Zoho
        }

        $config = $this->mergeConfigToFeatureSettings([]);

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
                        $isModified = false;
                        if ('Accounts' === $object) {
                            $recordId = $entityData['ACCOUNTID'];
                            // first try to find integration entity
                            $integrationId = $integrationEntityRepo->getIntegrationsEntityId('Zoho', $object, 'company',
                                null, null, null, false, 0, 0, "'".$recordId."'");
                            if (count($integrationId)) { // company exists, then update local fields
                                /** @var Company $entity */
                                $entity        = $this->companyModel->getEntity($integrationId[0]['internal_entity_id']);
                                $matchedFields = $this->populateMauticLeadData($entityData, $config, 'company');

                                // Match that data with mapped lead fields
                                $fieldsToUpdateInMautic = $this->getPriorityFieldsForMautic($config, $object, 'mautic_company');
                                if (!empty($fieldsToUpdateInMautic)) {
                                    $fieldsToUpdateInMautic = array_intersect_key($config['companyFields'], array_flip($fieldsToUpdateInMautic));
                                    $newMatchedFields       = array_intersect_key($matchedFields, array_flip($fieldsToUpdateInMautic));
                                } else {
                                    $newMatchedFields = $matchedFields;
                                }
                                if (!isset($newMatchedFields['companyname'])) {
                                    if (isset($newMatchedFields['companywebsite'])) {
                                        $newMatchedFields['companyname'] = $newMatchedFields['companywebsite'];
                                    }
                                }

                                // update values if already empty
                                foreach ($matchedFields as $field => $value) {
                                    if (empty($entity->getFieldValue($field))) {
                                        $newMatchedFields[$field] = $value;
                                    }
                                }

                                // remove unchanged fields
                                foreach ($newMatchedFields as $k => $v) {
                                    if ($entity->getFieldValue($k) === $v) {
                                        unset($newMatchedFields[$k]);
                                    }
                                }

                                if (count($newMatchedFields)) {
                                    $this->companyModel->setFieldValues($entity, $newMatchedFields, false, false);
                                    $this->companyModel->saveEntity($entity, false);
                                    $isModified = true;
                                }
                            } else {
                                $entity = $this->getMauticCompany($entityData, 'company');
                            }
                            if ($entity) {
                                $result[] = $entity->getName();
                            }
                            $mauticObjectReference = 'company';
                        } elseif ('Leads' === $object) {
                            $recordId = $entityData['LEADID'];
                            // first try to find integration entity
                            $integrationId = $integrationEntityRepo->getIntegrationsEntityId('Zoho', $object, 'lead',
                                null, null, null, false, 0, 0, "'".$recordId."'");
                            if (count($integrationId)) { // lead exists, then update
                                /** @var Lead $entity */
                                $entity        = $this->leadModel->getEntity($integrationId[0]['internal_entity_id']);
                                $matchedFields = $this->populateMauticLeadData($entityData, $config, $object);

                                // Match that data with mapped lead fields
                                $fieldsToUpdateInMautic = $this->getPriorityFieldsForMautic($config, $object, 'mautic');
                                if (!empty($fieldsToUpdateInMautic)) {
                                    $fieldsToUpdateInMautic = array_intersect_key($config['leadFields'], array_flip($fieldsToUpdateInMautic));
                                    $newMatchedFields       = array_intersect_key($matchedFields, array_flip($fieldsToUpdateInMautic));
                                } else {
                                    $newMatchedFields = $matchedFields;
                                }

                                // update values if already empty
                                foreach ($matchedFields as $field => $value) {
                                    if (empty($entity->getFieldValue($field))) {
                                        $newMatchedFields[$field] = $value;
                                    }
                                }

                                // remove unchanged fields
                                foreach ($newMatchedFields as $k => $v) {
                                    if ($entity->getFieldValue($k) === $v) {
                                        unset($newMatchedFields[$k]);
                                    }
                                }

                                if (count($newMatchedFields)) {
                                    $this->leadModel->setFieldValues($entity, $newMatchedFields, false, false);
                                    $this->leadModel->saveEntity($entity, false);
                                    $isModified = true;
                                }
                            } else {
                                /** @var Lead $entity */
                                $entity = $this->getMauticLead($entityData);
                            }

                            if ($entity) {
                                $result[] = $entity->getEmail();
                            }

                            // Associate lead company
                            if (!empty($entityData['Company'])
                                && $entityData['Company'] !== $this->translator->trans(
                                    'mautic.integration.form.lead.unknown'
                                )
                            ) {
                                $company = IdentifyCompanyHelper::identifyLeadsCompany(
                                    ['company' => $entityData['Company']],
                                    null,
                                    $this->companyModel
                                );

                                if (!empty($company[2])) {
                                    $syncLead = $this->companyModel->addLeadToCompany($company[2], $entity);
                                    $this->em->detach($company[2]);
                                }
                            }

                            $mauticObjectReference = 'lead';
                        } elseif ('Contacts' === $object) {
                            $recordId = $entityData['CONTACTID'];

                            $integrationId = $integrationEntityRepo->getIntegrationsEntityId('Zoho', $object, 'lead',
                                null, null, null, false, 0, 0, "'".$recordId."'");
                            if (count($integrationId)) { // contact exists, then update
                                /** @var Lead $entity */
                                $entity        = $this->leadModel->getEntity($integrationId[0]['internal_entity_id']);
                                $matchedFields = $this->populateMauticLeadData($entityData, $config, $object);

                                // Match that data with mapped lead fields
                                $fieldsToUpdateInMautic = $this->getPriorityFieldsForMautic($config, $object, 'mautic');
                                if (!empty($fieldsToUpdateInMautic)) {
                                    $fieldsToUpdateInMautic = array_intersect_key($config['leadFields'], array_flip($fieldsToUpdateInMautic));
                                    $newMatchedFields       = array_intersect_key($matchedFields, array_flip($fieldsToUpdateInMautic));
                                } else {
                                    $newMatchedFields = $matchedFields;
                                }

                                // update values if already empty
                                foreach ($matchedFields as $field => $value) {
                                    if (empty($entity->getFieldValue($field))) {
                                        $newMatchedFields[$field] = $value;
                                    }
                                }

                                // remove unchanged fields
                                foreach ($newMatchedFields as $k => $v) {
                                    if ($entity->getFieldValue($k) === $v) {
                                        unset($newMatchedFields[$k]);
                                    }
                                }

                                if (count($newMatchedFields)) {
                                    $this->leadModel->setFieldValues($entity, $newMatchedFields, false, false);
                                    $this->leadModel->saveEntity($entity, false);
                                    $isModified = true;
                                }
                            } else {
                                /** @var Lead $entity */
                                $entity = $this->getMauticLead($entityData);
                            }

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

                            if (0 === count($integrationId)) {
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
                                if ($isModified) {
                                    $integrationEntity->setLastSyncDate(new \DateTime());
                                    $integrationEntities[] = $integrationEntity;
                                }
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
    public function getLeads($params, $query, &$executed, $result = [], $object = 'Lead')
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

                $MAX_RECORDS              = 200;
                $fields                   = implode(',', $mappedData);
                $oparams['selectColumns'] = $object.'('.$fields.')';
                $oparams['toIndex']       = $MAX_RECORDS; // maximum number of records
                if (isset($params['fetchAll'], $params['start']) && !$params['fetchAll']) {
                    $oparams['lastModifiedTime'] = date('Y-m-d H:i:s', strtotime($params['start']));
                }

                if (isset($params['output']) && $params['output']->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
                    $progress = new ProgressBar($params['output']);
                    $progress->start();
                }

                while (true) {
                    // {"response":{"nodata":{"code":"4422","message":"There is no data to show"},"uri":"/crm/private/json/Contacts/getRecords"}}
                    $data = $this->getApiHelper()->getLeads($oparams, $object);
                    if (!isset($data[$object])) {
                        break; // no more data, exit loop
                    }
                    $result = $this->amendLeadDataBeforeMauticPopulate($data, $object);
                    $executed += count($result);
                    if (isset($params['output'])) {
                        if ($params['output']->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                            $params['output']->writeln($result);
                        } else {
                            $progress->advance();
                        }
                    }

                    // prepare next loop
                    $oparams['fromIndex'] = $oparams['toIndex'] + 1;
                    $oparams['toIndex'] += $MAX_RECORDS;
                }

                if (isset($params['output']) && $params['output']->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
                    $progress->finish();
                }
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

                $MAX_RECORDS              = 200;
                $oparams['selectColumns'] = 'Accounts('.$fields.')';
                $oparams['toIndex']       = $MAX_RECORDS; // maximum number of records
                if (isset($params['fetchAll'], $params['start']) && !$params['fetchAll']) {
                    $oparams['lastModifiedTime'] = date('Y-m-d H:i:s', strtotime($params['start']));
                }

                if (isset($params['output']) && $params['output']->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
                    $progress = new ProgressBar($params['output']);
                    $progress->start();
                }

                while (true) {
                    $data = $this->getApiHelper()->getCompanies($oparams);
                    if (!isset($data['Accounts'])) {
                        break; // no more data, exit loop
                    }
                    $result = $this->amendLeadDataBeforeMauticPopulate($data, $object);
                    $executed += count($result);
                    if (isset($params['output'])) {
                        if ($params['output']->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                            $params['output']->writeln($result);
                        } else {
                            $progress->advance();
                        }
                    }

                    // prepare next loop
                    $oparams['fromIndex'] = $oparams['toIndex'] + 1;
                    $oparams['toIndex'] += $MAX_RECORDS;
                }

                if (isset($params['output']) && $params['output']->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
                    $progress->finish();
                }

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
        $builder->add(
            'updateBlanks',
            'choice',
            [
                'choices' => [
                    'updateBlanks' => 'mautic.integrations.blanks',
                ],
                'expanded'    => true,
                'multiple'    => true,
                'label'       => 'mautic.integrations.form.blanks',
                'label_attr'  => ['class' => 'control-label'],
                'empty_value' => false,
                'required'    => false,
            ]
        );
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
        $MAX_RECORDS = (isset($params['limit']) && $params['limit'] < 100) ? $params['limit'] : 100;
        if (isset($params['fetchAll']) && $params['fetchAll']) {
            $params['start'] = null;
            $params['end']   = null;
        }
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

        $availableFields            = $this->getAvailableLeadFields(['feature_settings' => ['objects' => ['Leads', 'Contacts']]]);
        $fieldsToUpdate['Leads']    = array_values(array_intersect(array_keys($availableFields['Leads']), $fieldsToUpdateInZoho));
        $fieldsToUpdate['Contacts'] = array_values(array_intersect(array_keys($availableFields['Contacts']), $fieldsToUpdateInZoho));
        $fieldsToUpdate['Leads']    = array_intersect_key($config['leadFields'], array_flip($fieldsToUpdate['Leads']));
        $fieldsToUpdate['Contacts'] = array_intersect_key($config['leadFields'], array_flip($fieldsToUpdate['Contacts']));

        $progress      = false;
        $totalToUpdate = array_sum($integrationEntityRepo->findLeadsToUpdate('Zoho', 'lead', $fields, false, $params['start'], $params['end'], ['Contacts', 'Leads']));
        $totalToCreate = $integrationEntityRepo->findLeadsToCreate('Zoho', $fields, false, $params['start'], $params['end']);
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
        $leadsToCreateInZ    = [];
        $leadsToUpdateInZ    = [];
        $isContact           = [];
        $integrationEntities = [];

        // Fetch them separately so we can determine which oneas are already there
        $toUpdate = $integrationEntityRepo->findLeadsToUpdate('Zoho', 'lead', $fields, $totalToUpdate, $params['start'], $params['end'], 'Contacts', [])['Contacts'];

        if (is_array($toUpdate)) {
            $totalCount -= count($toUpdate);
            $totalUpdated += count($toUpdate);
            foreach ($toUpdate as $lead) {
                if (isset($lead['email']) && !empty($lead['email'])) {
                    $key                        = mb_strtolower($this->cleanPushData($lead['email']));
                    $lead['integration_entity'] = 'Contacts';
                    $leadsToUpdateInZ[$key]     = $lead;
                    $isContact[$key]            = $lead;
                }
            }
        }

        // Switch to Lead
        $toUpdate = $integrationEntityRepo->findLeadsToUpdate('Zoho', 'lead', $fields, $totalToUpdate, $params['start'], $params['end'],  'Leads', [])['Leads'];

        if (is_array($toUpdate)) {
            $leadCount = count($toUpdate);
            $totalCount -= count($toUpdate);
            $totalUpdated += count($toUpdate);

            foreach ($toUpdate as $lead) {
                if (isset($lead['email']) && !empty($lead['email'])) {
                    $key = mb_strtolower($this->cleanPushData($lead['email']));
                    if (isset($isContact[$key])) {
                        $isContact[$key] = $lead; // lead-converted
                    } else {
                        $lead['integration_entity'] = 'Leads';
                        $leadsToUpdateInZ[$key]     = $lead;
                        $integrationEntity          = $this->em->getReference('MauticPluginBundle:IntegrationEntity', $lead['id']);
                        $integrationEntities[]      = $integrationEntity->setLastSyncDate(new \DateTime());
                    }
                }
            }
        }
        unset($toUpdate);

        // convert ignored contacts
        foreach ($isContact as $email => $lead) {
            // do not call update
            $integrationEntity     = $this->em->getReference('MauticPluginBundle:IntegrationEntity', $lead['id']);
            $integrationEntities[] = $integrationEntity->setLastSyncDate(new \DateTime());
            $integrationId         = $integrationEntityRepo->getIntegrationsEntityId('Zoho', 'Leads', 'lead',
                $lead['internal_entity_id']);
            if (count($integrationId)) { // lead exists, then update
                $integrationEntity     = $this->em->getReference('MauticPluginBundle:IntegrationEntity', $integrationId[0]['id']);
                $integrationEntities[] = $integrationEntity->setInternalEntity('lead-converted');
                unset($leadsToUpdateInZ[$email]);
            }
        }

        //create lead records, including deleted on Zoho side (last_sync = null)
        /** @var array $leadsToCreate */
        $leadsToCreate = $integrationEntityRepo->findLeadsToCreate('Zoho', $fields, $totalToCreate, $params['start'], $params['end']);

        if (is_array($leadsToCreate)) {
            $totalCount -= count($leadsToCreate);
            $totalCreated += count($leadsToCreate);
            foreach ($leadsToCreate as $lead) {
                if (isset($lead['email']) && !empty($lead['email'])) {
                    $key                        = mb_strtolower($this->cleanPushData($lead['email']));
                    $lead['integration_entity'] = 'Leads';
                    $leadsToCreateInZ[$key]     = $lead;
                }
            }
        }
        unset($leadsToCreate);

        if (count($integrationEntities)) {
            // Persist updated entities if applicable
            $integrationEntityRepo->saveEntities($integrationEntities);
            $this->em->clear(IntegrationEntity::class);
        }

        // update leads and contacts
        foreach (['Leads', 'Contacts'] as $zObject) {
            $rowid      = 1;
            $mappedData = [];
            $xmlData    = '<'.$zObject.'>';
            foreach ($leadsToUpdateInZ as $email => $lead) {
                if ($zObject !== $lead['integration_entity']) {
                    continue;
                }
                if ($progress) {
                    $progress->advance();
                }
                $mappedData['Id'] = $lead['integration_entity_id'];

                $existingPerson = $this->getExistingRecord('email', $lead['email'], $zObject);
                $objectFields   = $this->prepareFieldsForPush($availableFields[$zObject]);

                $fieldsToUpdate[$zObject] = $this->getBlankFieldsToUpdate($fieldsToUpdate[$zObject], $existingPerson, $objectFields, $config);

                $xmlData .= '<row no="'.($rowid++).'">';
                // Match that data with mapped lead fields
                foreach ($fieldsToUpdate[$zObject] as $k => $v) {
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

                // ONLY 100 RECORDS CAN BE SENT AT A TIME
                if ($MAX_RECORDS === $rowid) {
                    $xmlData .= '</'.$zObject.'>';
                    $this->getApiHelper()->updateLead($xmlData, null, $zObject);
                    $xmlData = '<'.$zObject.'>';
                    $rowid   = 1;
                }
            }
            $xmlData .= '</'.$zObject.'>';

            if ($rowid > 1) {
                $this->logger->debug($xmlData);
                $this->getApiHelper()->updateLead($xmlData, null, $zObject);
            }
        }

        // create leads and contacts
        foreach (['Leads', 'Contacts'] as $zObject) {
            $rowid      = 1;
            $mappedData = [];
            $xmlData    = '<'.$zObject.'>';
            foreach ($leadsToCreateInZ as $email => $lead) {
                if ($zObject !== $lead['integration_entity']) {
                    continue;
                }
                if ($progress) {
                    $progress->advance();
                }
                ++$rowid;
                $xmlData .= '<row no="'.$lead['internal_entity_id'].'">';
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

                // ONLY 100 RECORDS CAN BE SENT AT A TIME
                if ($MAX_RECORDS === $rowid) {
                    $xmlData .= '</'.$zObject.'>';
                    $response = $this->getApiHelper()->createLead($xmlData, null, $zObject);
                    $this->createIntegrationEntityFromResponse($response, $zObject, $integrationEntityRepo);
                    $xmlData = '<'.$zObject.'>';
                    $rowid   = 1;
                }
            }
            $xmlData .= '</'.$zObject.'>';

            if ($rowid > 1) {
                $response = $this->getApiHelper()->createLead($xmlData, null, $zObject);
                $this->createIntegrationEntityFromResponse($response, $zObject, $integrationEntityRepo);
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

    /**
     * @param array $response
     * @param $zObject
     * @param IntegrationEntityRepository $integrationEntityRepo
     */
    private function createIntegrationEntityFromResponse($response, $zObject, $integrationEntityRepo)
    {
        $text    = preg_replace('/_/', ' ', implode('', array_keys($response))).'='.implode('', array_values($response));
        $xml     = simplexml_load_string($text);
        $doc     = json_decode(json_encode((array) $xml), true);
        $rows    = $doc['result']['row'];
        $total   = count($rows);
        $success = 0;
        foreach ($rows as $row) {
            if (isset($row['success'])) {
                $fl = $row['success']['details']['FL'];
                if (isset($fl[0])) {
                    ++$success;
                    $this->logger->debug('CREATE INTEGRATION ENTITY: '.$fl[0]);
                    $integrationId = $integrationEntityRepo->getIntegrationsEntityId('Zoho', $zObject,
                        'lead', null, null, null, false, 0, 0,
                        "'".$fl[0]."'"
                    );

                    if (0 === count($integrationId)) {
                        $leadId = $row['@attributes']['no'];
                        $this->createIntegrationEntity($zObject, $fl[0], 'lead', $leadId);
                    }
                }
            }
        }
    }

    /**
     * @param $seachColumn
     * @param $searchValue
     * @param string $object
     *
     * @return mixed
     */
    private function getExistingRecord($seachColumn, $searchValue, $object = 'Leads')
    {
        $availableFields = $this->getAvailableLeadFields(['feature_settings' => ['objects' => ['Leads', 'Contacts']]]);
        $selectColumns   = implode(',', $availableFields[$object]);
        $records         = $this->getApiHelper()->getSearchRecords($selectColumns, $seachColumn, $searchValue, $object);
        $parsedRecords   = $this->parseZohoRecord($records, $availableFields[$object]);

        return $parsedRecords;
    }

    /**
     * @param $data
     * @param $fields
     * @param string $object
     *
     * @return mixed
     */
    private function parseZohoRecord($data, $fields, $object = 'Leads')
    {
        if (!empty($data['response']['result'][$object])) {
            $records = $data['response']['result'][$object]['row']['FL'];
            foreach ($fields as $key => $field) {
                foreach ($records as $record) {
                    if ($record['val'] == $field['dv']) {
                        $parsedData[$key] = $record['content'];
                        continue;
                    }
                }
            }
        }

        return $parsedData;
    }

    /**
     * @param $fields
     * @param $sfRecord
     * @param $config
     * @param $objectFields
     */
    public function getBlankFieldsToUpdate($fields, $sfRecord, $objectFields, $config)
    {
        //check if update blank fields is selected
        if (isset($config['updateBlanks']) && isset($config['updateBlanks'][0]) && $config['updateBlanks'][0] == 'updateBlanks') {
            foreach ($sfRecord as $fieldName => $sfField) {
                if (array_key_exists($fieldName, $objectFields['required']['fields'])) {
                    continue; // this will be treated differently
                }
                if ($sfField === 'null' && array_key_exists($fieldName, $objectFields['create']) && !array_key_exists($fieldName, $fields)) {
                    //map to mautic field
                    $fields[$fieldName] = $objectFields['create'][$fieldName];
                }
            }
        }

        return $fields;
    }
}
