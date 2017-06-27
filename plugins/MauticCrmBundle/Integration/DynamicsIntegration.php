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
    public function populateLeadData($lead, $config = [], $object = 'contacts')
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
    public function getLeads($params = [], $query = null, &$executed = null, &$result = [], $object = 'contacts')
    {
        if ('Contact' === $object) {
            $object = 'contacts';
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
                $oparams['request_settings']['headers']['Prefer'] = 'odata.maxpagesize='.$params['limit'] ?? 100;
                $oparams['$select']                               = implode(',', $mappedData);
                $data                                             = $this->getApiHelper()->getLeads($oparams);
                $populate                                         = $this->amendLeadDataBeforeMauticPopulate($data, $object);
                $result                                           = array_merge($result, $populate);
                $executed += array_key_exists('value', $data) ? count($data['value']) : 0;
                // TODO: fetch more records using "@odata.nextLink" value
                // https://CLIENTURL.crm.dynamics.com/api/data/v8.2/contacts?=firstname,emailaddress1,lastname&=<cookie pagenumber="2" pagingcookie=" <cookie page="1"><contactid last="{D74F6223-FF4A-E711-8101-C4346BADA738}" first="{465B158C-541C-E511-80D3-3863BB347BA8}" /></cookie> " istracking="False" />
//              if (isset($data['hasMore']) && $data['hasMore']) {
//                  $executed += $this->getLeads($params);
//              }
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
                $oparams['request_settings']['headers']['Prefer'] = 'odata.maxpagesize='.$params['limit'] ?? 100;
                $oparams['$select']                               = implode(',', $mappedData);
                $data                                             = $this->getApiHelper()->getCompanies($oparams);
                $populate                                         = $this->amendLeadDataBeforeMauticPopulate($data, $object);
                $result                                           = array_merge($result, $populate);
                $executed += count($populate);
                // TODO: fetch more records using "@odata.nextLink" value
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
     * Amend mapped lead data before creating to Mautic.
     *
     * @param array  $data
     * @param string $object
     *
     * @return array
     */
    public function amendLeadDataBeforeMauticPopulate($data, $object = 'contacts')
    {
        if ('company' === $object) {
            $object = 'accounts';
        }
        $result = [];
        if (isset($data['value'])) {
            $entity = null;
            /** @var IntegrationEntityRepository $integrationEntityRepo */
            $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
            $objects               = $data['value'];
            $integrationEntities   = [];
            /** @var array $objects */
            foreach ($objects as $entityData) {
                if ('accounts' === $object) {
                    $recordId = $entityData['accountid'];
                    /** @var Company $entity */
                    $entity = $this->getMauticCompany($entityData);
                    if ($entity) {
                        $result[] = $entity->getName();
                    }
                    $internalEntity = 'company';
                } elseif ('contacts' === $object) {
                    $recordId = $entityData['contactid'];
                    /** @var Lead $entity */
                    $entity = $this->getMauticLead($entityData);
                    if ($entity) {
                        $result[] = $entity->getEmail();
                    }
                    $internalEntity = 'lead';
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
                        $internalEntity,
                        $entity->getId()
                    );
                    if ($integrationId == null) {
                        $integrationEntity = new IntegrationEntity();
                        $integrationEntity->setDateAdded(new \DateTime());
                        $integrationEntity->setIntegration('Dynamics');
                        $integrationEntity->setIntegrationEntity($object);
                        $integrationEntity->setIntegrationEntityId($recordId);
                        $integrationEntity->setInternalEntity($internalEntity);
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
        $object = 'contacts';
        $limit  = $params['limit'];
        $config = $this->mergeConfigToFeatureSettings();
        /** @var IntegrationEntityRepository $integrationEntityRepo */
        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
        $leadFields            = array_unique(array_values($config['leadFields']));
        $totalUpdated          = $totalCreated          = $totalErrors          = 0;
        if (empty($leadFields)) {
            return [0, 0, 0];
        }
        $fields          = implode(', l.', $leadFields);
        $fields          = 'l.'.$fields;
        $availableFields = $this->getAvailableLeadFields(['feature_settings' => ['objects' => [$object]]]);
        $progress        = false;
        $totalToUpdate   = array_sum($integrationEntityRepo->findLeadsToUpdate('Dynamics', 'lead', $fields, false, null, null, [$object]));
        $totalToCreate   = $integrationEntityRepo->findLeadsToCreate('Dynamics', $fields, false, null, null);
        $totalCount      = $totalToCreate + $totalToUpdate;
        if (defined('IN_MAUTIC_CONSOLE')) {
            // start with update
            if ($totalToUpdate + $totalToCreate) {
                $output = new ConsoleOutput();
                $output->writeln("About $totalToUpdate to update and about $totalToCreate to create");
                $progress = new ProgressBar($output, $totalCount);
            }
        }
        $leadsToUpdate       = [];
        $leadsToCreate       = [];
        $integrationEntities = [];
        // Fetch them separately so we can determine which oneas are already there
        $toUpdate = $integrationEntityRepo->findLeadsToUpdate('Dynamics', 'lead', $fields, $limit, null, null, $object, [])[$object];
        $totalCount -= count($toUpdate);
        $totalUpdated += count($toUpdate);
        foreach ($toUpdate as $lead) {
            if (isset($lead['email']) && !empty($lead['email'])) {
                $key                 = mb_strtolower($this->cleanPushData($lead['email']));
                $leadsToUpdate[$key] = $lead;
            }
        }
        unset($toUpdate);
        $toCreate = $integrationEntityRepo->findLeadsToCreate('Dynamics', $fields, $limit, null, null);
        $totalCount -= count($toCreate);
        $totalCreated += count($toCreate);
        foreach ($toCreate as $lead) {
            if (isset($lead['email']) && !empty($lead['email'])) {
                $key                        = mb_strtolower($this->cleanPushData($lead['email']));
                $lead['integration_entity'] = $object;
                $leadsToCreate[$key]        = $lead;
                if ($lead['id']) {
                    /** @var IntegrationEntity $integrationEntity */
                    $integrationEntity     = $this->em->getReference('MauticPluginBundle:IntegrationEntity', $lead['id']);
                    $integrationEntities[] = $integrationEntity->setLastSyncDate(new \DateTime());
                }
            }
        }
        unset($toCreate);

        // update leads
        $leadData = [];
        foreach ($leadsToUpdate as $email => $lead) {
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
            $leadData[$lead['integration_entity_id']] = $mappedData;
        }
        $this->getApiHelper()->updateLeads($leadData, $object);

        // create leads
        $leadData = [];
        foreach ($leadsToCreate as $email => $lead) {
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
            $leadData[$lead->getId()] = $mappedData;
        }

        $ids = $this->getApiHelper()->createLeads($leadData, $object);
        foreach ($ids as $id) {
            $integrationEntity = new IntegrationEntity();
            $integrationEntity->setDateAdded(new \DateTime());
            $integrationEntity->setIntegration('Dynamics');
            $integrationEntity->setIntegrationEntity($object);
            $integrationEntity->setIntegrationEntityId($id);
            $integrationEntity->setInternalEntity('lead');
            $integrationEntity->setInternalEntityId($lead->getId());
        }

        if ($integrationEntities) {
            // Persist updated entities if applicable
            $integrationEntityRepo->saveEntities($integrationEntities);
            $this->em->clear(IntegrationEntity::class);
        }

        if (defined('IN_MAUTIC_CONSOLE')) {
            if ($progress) {
                $progress->finish();
            }
            if ($output) {
                $output->writeln('');
            }
        }

        return [$totalUpdated, $totalCreated, $totalErrors];
    }
}
