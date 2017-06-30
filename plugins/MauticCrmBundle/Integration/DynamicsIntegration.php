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

use Joomla\Http\Response;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\FormBuilder;

class DynamicsIntegration extends CrmAbstractIntegration
{
    public function getName()
    {
        return 'Dynamics';
    }

    public function getDisplayName()
    {
        return 'Dynamics CRM';
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead', 'get_leads', 'push_leads'];
    }

    /**
     * Return's authentication method such as oauth2, oauth1a, key, etc.
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'oauth2';
    }

    /**
     * Return array of key => label elements that will be converted to inputs to
     * obtain from the user.
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'resource'      => 'mautic.integration.dynamics.resource',
            'client_id'     => 'mautic.integration.dynamics.client_id',
            'client_secret' => 'mautic.integration.dynamics.client_secret',
        ];
    }

    /**
     * @param FormBuilder $builder
     * @param array       $data
     * @param string      $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea === 'features') {
            $builder->add(
                'objects',
                'choice',
                [
                    'choices' => [
                        'contacts' => 'mautic.dynamics.object.contact',
                        'company'  => 'mautic.dynamics.object.company',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.dynamics.form.objects_to_pull_from',
                    'label_attr'  => ['class' => ''],
                    'empty_value' => false,
                    'required'    => false,
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sortFieldsAlphabetically()
    {
        return false;
    }

    /**
     * Get the array key for the auth token.
     *
     * @return string
     */
    public function getAuthTokenKey()
    {
        return 'access_token';
    }

    /**
     * Get the keys for the refresh token and expiry.
     *
     * @return array
     */
    public function getRefreshTokenKeys()
    {
        return ['refresh_token', 'expires_on'];
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return $this->keys['resource'];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAccessTokenUrl()
    {
        return 'https://login.microsoftonline.com/common/oauth2/token';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationUrl()
    {
        return 'https://login.microsoftonline.com/common/oauth2/authorize';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthLoginUrl()
    {
        $url = parent::getAuthLoginUrl();
        $url .= '&resource='.urlencode($this->keys['resource']);

        return $url;
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $inAuthorization
     */
    public function getBearerToken($inAuthorization = false)
    {
        if (!$inAuthorization && isset($this->keys[$this->getAuthTokenKey()])) {
            return $this->keys[$this->getAuthTokenKey()];
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function getDataPriority()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param $section
     *
     * @return string|array
     */
    public function getFormNotes($section)
    {
        if ('custom' === $section) {
            return [
                'template'   => 'MauticCrmBundle:Integration:dynamics.html.php',
                'parameters' => [

                ],
            ];
        }

        return parent::getFormNotes($section);
    }

    /**
     * @param $lead
     * @param $config
     *
     * @return array
     */
    public function populateLeadData($lead, $config = [], $object = 'Contacts')
    {
        if ('company' === $object) {
            $object = 'accounts';
        }
        $config['object'] = $object;

        return parent::populateLeadData($lead, $config);
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
        return $this->getFormFieldsByObject('accounts', $settings);
    }

    /**
     * @param array $settings
     *
     * @return array|mixed
     */
    public function getFormLeadFields($settings = [])
    {
        return  $this->getFormFieldsByObject('contacts', $settings);
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
        $dynamicsFields    = [];
        $silenceExceptions = isset($settings['silence_exceptions']) ? $settings['silence_exceptions'] : true;
        if (isset($settings['feature_settings']['objects'])) {
            $dynamicsObjects = $settings['feature_settings']['objects'];
        } else {
            $settings        = $this->settings->getFeatureSettings();
            $dynamicsObjects = isset($settings['objects']) ? $settings['objects'] : ['contacts'];
        }
        try {
            if ($this->isAuthorized()) {
                if (!empty($dynamicsObjects) && is_array($dynamicsObjects)) {
                    foreach ($dynamicsObjects as $key => $dynamicsObject) {
                        // Check the cache first
                        $settings['cache_suffix'] = $cacheSuffix = '.'.$dynamicsObject;
                        if ($fields = parent::getAvailableLeadFields($settings)) {
                            $dynamicsFields[$dynamicsObject] = $fields;
                            continue;
                        }
                        $leadObject = $this->getApiHelper()->getLeadFields($dynamicsObject);
                        if (null === $leadObject || !array_key_exists('value', $leadObject)) {
                            return [];
                        }
                        /** @var array $opts */
                        $fields = $leadObject['value'];
                        foreach ($fields as $field) {
                            $dynamicsFields[$dynamicsObject][$field['LogicalName']] = [
                                'type'     => 'string',
                                'label'    => $field['DisplayName']['UserLocalizedLabel']['Label'],
                                'dv'       => $field['LogicalName'],
                                'required' => 'ApplicationRequired' === $field['RequiredLevel']['Value'],
                            ];
                        }
                        $this->cache->set('leadFields'.$cacheSuffix, $dynamicsFields[$dynamicsObject]);
                    }
                }
            }
        } catch (ApiErrorException $exception) {
            $this->logIntegrationError($exception);
            if (!$silenceExceptions) {
                throw $exception;
            }

            return false;
        }

        return $dynamicsFields;
    }

    /**
     * @param Lead  $lead
     * @param array $config
     *
     * @return array|bool
     */
    public function pushLead($lead, $config = [])
    {
        $config = $this->mergeConfigToFeatureSettings($config);

        if (empty($config['leadFields'])) {
            return [];
        }

        $mappedData = $this->populateLeadData($lead, $config);

        $this->amendLeadDataBeforePush($mappedData);

        if (empty($mappedData)) {
            return false;
        }

        try {
            if ($this->isAuthorized()) {
                $object = 'contacts';
                /** @var IntegrationEntityRepository $integrationEntityRepo */
                $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
                $integrationId         = $integrationEntityRepo->getIntegrationsEntityId('Dynamics', $object, 'lead', $lead->getId());
                if (!empty($integrationId)) {
                    $integrationEntityId = $integrationId[0]['integration_entity_id'];
                    $this->getApiHelper()->updateLead($mappedData, $integrationEntityId);

                    return $integrationEntityId;
                }
                /** @var Response $response */
                $response = $this->getApiHelper()->createLead($mappedData, $lead);
                // OData-EntityId: https://clientname.crm.dynamics.com/api/data/v8.2/contacts(9844333b-c955-e711-80f1-c4346bad526c)
                $header = $response->headers['OData-EntityId'];
                if (preg_match('/contacts\((.+)\)/', $header, $out)) {
                    $id = $out[1];
                    if (empty($integrationId)) {
                        $integrationEntity = new IntegrationEntity();
                        $integrationEntity->setDateAdded(new \DateTime());
                        $integrationEntity->setIntegration('Dynamics');
                        $integrationEntity->setIntegrationEntity($object);
                        $integrationEntity->setIntegrationEntityId($id);
                        $integrationEntity->setInternalEntity('lead');
                        $integrationEntity->setInternalEntityId($lead->getId());
                    } else {
                        $integrationEntity = $integrationEntityRepo->getEntity($integrationId[0]['id']);
                    }
                    $integrationEntity->setLastSyncDate(new \DateTime());
                    $this->em->persist($integrationEntity);
                    $this->em->flush($integrationEntity);

                    return $id;
                }

                return true;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return false;
    }

    /**
     * @param array      $params
     * @param null|array $query
     *
     * @return int|null
     */
    public function getLeads($params = [], $query = null, &$executed = null, $result = [], $object = 'contacts')
    {
        if ('Contact' === $object) {
            $object = 'contacts';
        }
        $executed    = 0;
        $MAX_RECORDS = 200; // Default max records is 5000
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
                $oparams['request_settings']['headers']['Prefer'] = 'odata.maxpagesize='.$MAX_RECORDS;
                $oparams['$select']                               = implode(',', $mappedData);
                if (isset($params['fetchAll'], $params['start']) && !$params['fetchAll']) {
                    $oparams['$filter'] = sprintf('createdon ge %sZ', substr($params['start'], 0, '-6'));
                }

                if (isset($params['output']) && $params['output']->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
                    $progress = new ProgressBar($params['output']);
                    $progress->start();
                }

                while (true) {
                    $data = $this->getApiHelper()->getLeads($oparams);

                    if (!isset($data['value'])) {
                        break; // no more data, exit loop
                    }

                    $result = $this->amendLeadDataBeforeMauticPopulate($data, $object);
                    $executed += array_key_exists('value', $data) ? count($data['value']) : count($result);

                    if (isset($params['output'])) {
                        if ($params['output']->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                            $params['output']->writeln($result);
                        } else {
                            $progress->advance();
                        }
                    }

                    if (!isset($data['@odata.nextLink'])) {
                        break; // default exit
                    }

                    // prepare next loop
                    $nextLink              = $data['@odata.nextLink'];
                    $oparams['$skiptoken'] = urldecode(substr($nextLink, strpos($nextLink, '$skiptoken=') + 11));
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
     * @param array $params
     *
     * @return int|null
     */
    public function getCompanies($params = [])
    {
        $executed    = 0;
        $MAX_RECORDS = 200; // Default max records is 5000
        $object      = 'company';
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
                $oparams['request_settings']['headers']['Prefer'] = 'odata.maxpagesize='.$MAX_RECORDS;
                $oparams['$select']                               = implode(',', $mappedData);
                if (isset($params['fetchAll'], $params['start']) && !$params['fetchAll']) {
                    $oparams['$filter'] = sprintf('createdon ge %sZ', substr($params['start'], 0, '-6'));
                }

                if (isset($params['output']) && $params['output']->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
                    $progress = new ProgressBar($params['output']);
                    $progress->start();
                }

                while (true) {
                    $data = $this->getApiHelper()->getCompanies($oparams);
                    if (!isset($data['value'])) {
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

                    if (!isset($data['@odata.nextLink'])) {
                        break; // default exit
                    }

                    // prepare next loop
                    $nextLink              = $data['@odata.nextLink'];
                    $oparams['$skiptoken'] = urldecode(substr($nextLink, strpos($nextLink, '$skiptoken=') + 11));
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
            $object = 'accounts';
        } elseif ('Lead' === $object || 'Contact' === $object) {
            $object = 'contacts';
        }

        $config = $this->mergeConfigToFeatureSettings([]);

        $result = [];
        if (isset($data['value'])) {
            $entity = null;
            /** @var IntegrationEntityRepository $integrationEntityRepo */
            $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
            $objects               = $data['value'];
            $integrationEntities   = [];
            /** @var array $objects */
            foreach ($objects as $entityData) {
                $isModified = false;
                if ('accounts' === $object) {
                    $recordId = $entityData['accountid'];
                    // first try to find integration entity
                    $integrationId = $integrationEntityRepo->getIntegrationsEntityId('Dynamics', $object, 'company',
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
                } elseif ('contacts' === $object) {
                    $recordId = $entityData['contactid'];
                    // first try to find integration entity
                    $integrationId = $integrationEntityRepo->getIntegrationsEntityId('Dynamics', $object, 'lead',
                        null, null, null, false, 0, 0, "'".$recordId."'");
                    if (count($integrationId)) { // lead exists, then update
                        /** @var Lead $entity */
                        $entity        = $this->leadModel->getEntity($integrationId[0]['internal_entity_id']);
                        $matchedFields = $this->populateMauticLeadData($entityData, $config);

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
                    if (!empty($entityData['parentcustomerid']) // company
                        && $entityData['parentcustomerid'] !== $this->translator->trans(
                            'mautic.integration.form.lead.unknown'
                        )
                    ) {
                        $company = IdentifyCompanyHelper::identifyLeadsCompany(
                            ['company' => $entityData['parentcustomerid']],
                            null,
                            $this->companyModel
                        );

                        if (!empty($company[2])) {
                            $syncLead = $this->companyModel->addLeadToCompany($company[2], $entity);
                            $this->em->detach($company[2]);
                        }
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
                        'Dynamics',
                        $object,
                        $mauticObjectReference,
                        $entity->getId()
                    );

                    if (0 === count($integrationId)) {
                        $integrationEntity = new IntegrationEntity();
                        $integrationEntity->setDateAdded(new \DateTime());
                        $integrationEntity->setIntegration('Dynamics');
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

            unset($integrationEntities);
        }

        return $result;
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
        $object                = 'contacts';
        $config                = $this->mergeConfigToFeatureSettings();
        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
        $fieldsToUpdateInCrm   = isset($config['update_mautic']) ? array_keys($config['update_mautic'], 0) : [];
        $leadFields            = array_unique(array_values($config['leadFields']));
        $totalUpdated          = $totalCreated          = $totalErrors          = 0;

        if (empty($leadFields)) {
            return [0, 0, 0];
        }

        $fields = implode(', l.', $leadFields);
        $fields = 'l.'.$fields;

        $availableFields         = $this->getAvailableLeadFields(['feature_settings' => ['objects' => [$object]]]);
        $fieldsToUpdate[$object] = array_values(array_intersect(array_keys($availableFields[$object]), $fieldsToUpdateInCrm));
        $fieldsToUpdate[$object] = array_intersect_key($config['leadFields'], array_flip($fieldsToUpdate[$object]));

        $progress      = false;
        $totalToUpdate = array_sum($integrationEntityRepo->findLeadsToUpdate('Dynamics', 'lead', $fields, false, $params['start'], $params['end'], [$object]));
        $totalToCreate = $integrationEntityRepo->findLeadsToCreate('Dynamics', $fields, false, $params['start'], $params['end']);
        $totalCount    = $totalToCreate + $totalToUpdate;

        if (defined('IN_MAUTIC_CONSOLE')) {
            // start with update
            if ($totalToUpdate + $totalToCreate) {
                $output = new ConsoleOutput();
                $output->writeln("About $totalToUpdate to update and about $totalToCreate to create/update");
                $output->writeln('<info>This could take some time. Please wait until the process is completed</info>');
                $progress = new ProgressBar($output, $totalCount);
            }
        }

        // Start with contacts so we know who is a contact when we go to process converted leads
        $leadsToCreateInD    = [];
        $leadsToUpdateInD    = [];
        $integrationEntities = [];

        $toUpdate = $integrationEntityRepo->findLeadsToUpdate('Dynamics', 'lead', $fields, $totalToUpdate, $params['start'], $params['end'],  $object, [])[$object];

        if (is_array($toUpdate)) {
            $totalUpdated += count($toUpdate);
            foreach ($toUpdate as $lead) {
                if (isset($lead['email']) && !empty($lead['email'])) {
                    $key                        = mb_strtolower($this->cleanPushData($lead['email']));
                    $lead['integration_entity'] = $object;
                    $leadsToUpdateInD[$key]     = $lead;
                    $integrationEntity          = $this->em->getReference('MauticPluginBundle:IntegrationEntity', $lead['id']);
                    $integrationEntities[]      = $integrationEntity->setLastSyncDate(new \DateTime());
                }
            }
        }
        unset($toUpdate);

        //create lead records, including deleted on D side (last_sync = null)
        /** @var array $leadsToCreate */
        $leadsToCreate = $integrationEntityRepo->findLeadsToCreate('Dynamics', $fields, $totalToCreate, $params['start'], $params['end']);
        if (is_array($leadsToCreate)) {
            $totalCreated += count($leadsToCreate);
            foreach ($leadsToCreate as $lead) {
                if (isset($lead['email']) && !empty($lead['email'])) {
                    $key                        = mb_strtolower($this->cleanPushData($lead['email']));
                    $lead['integration_entity'] = $object;
                    $leadsToCreateInD[$key]     = $lead;
                }
            }
        }
        unset($leadsToCreate);

        if (count($integrationEntities)) {
            // Persist updated entities if applicable
            $integrationEntityRepo->saveEntities($integrationEntities);
            $this->em->clear(IntegrationEntity::class);
        }

        // update contacts
        $leadData = [];
        $rowNum   = 0;
        foreach ($leadsToUpdateInD as $email => $lead) {
            $mappedData = [];
            if (defined('IN_MAUTIC_CONSOLE') && $progress) {
                $progress->advance();
            }
            // Match that data with mapped lead fields
            foreach ($fieldsToUpdate[$object] as $k => $v) {
                foreach ($lead as $dk => $dv) {
                    if ($v === $dk) {
                        if ($dv) {
                            if (isset($availableFields[$object][$k])) {
                                $mappedData[$availableFields[$object][$k]['dv']] = $dv;
                            }
                        }
                    }
                }
            }
            $leadData[$lead['integration_entity_id']] = $mappedData;

            ++$rowNum;
            // SEND 100 RECORDS AT A TIME
            if ($MAX_RECORDS === $rowNum) {
                $this->getApiHelper()->updateLeads($leadData, $object);
                $leadData = [];
                $rowNum   = 0;
            }
        }
        $this->getApiHelper()->updateLeads($leadData, $object);

        // create  contacts
        $leadData = [];
        $rowNum   = 0;
        foreach ($leadsToCreateInD as $email => $lead) {
            $mappedData = [];
            if (defined('IN_MAUTIC_CONSOLE') && $progress) {
                $progress->advance();
            }
            // Match that data with mapped lead fields
            foreach ($config['leadFields'] as $k => $v) {
                foreach ($lead as $dk => $dv) {
                    if ($v === $dk) {
                        if ($dv) {
                            if (isset($availableFields[$object][$k])) {
                                $mappedData[$availableFields[$object][$k]['dv']] = $dv;
                            }
                        }
                    }
                }
            }
            $leadData[$lead['internal_entity_id']] = $mappedData;

            ++$rowNum;
            // SEND 100 RECORDS AT A TIME
            if ($MAX_RECORDS === $rowNum) {
                $ids = $this->getApiHelper()->createLeads($leadData, $object);
                $this->createIntegrationEntities($ids, $object, $integrationEntityRepo);
                $leadData = [];
                $rowNum   = 0;
            }
        }
        $ids = $this->getApiHelper()->createLeads($leadData, $object);
        $this->createIntegrationEntities($ids, $object, $integrationEntityRepo);

        if ($progress) {
            $progress->finish();
            $output->writeln('');
        }

        return [$totalUpdated, $totalCreated, $totalErrors];
    }

    /**
     * @param array $ids
     * @param $object
     * @param IntegrationEntityRepository $integrationEntityRepo
     */
    private function createIntegrationEntities($ids, $object, $integrationEntityRepo)
    {
        foreach ($ids as $oid => $leadId) {
            $this->logger->debug('CREATE INTEGRATION ENTITY: '.$oid);
            $integrationId = $integrationEntityRepo->getIntegrationsEntityId('Dynamics', $object,
                'lead', null, null, null, false, 0, 0,
                "'".$oid."'"
            );

            if (0 === count($integrationId)) {
                $this->createIntegrationEntity($object, $oid, 'lead', $leadId);
            }
        }
    }
}
