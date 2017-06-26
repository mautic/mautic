<?php
/**
 * @copyright   2016 Webmecanik
 * @author      Webmecanik
 *
 * @link        http://www.webmecanik.com
 */

namespace MauticPlugin\MauticCrmBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;

/**
 * Class InesIntegration.
 */
class InesIntegration extends CrmAbstractIntegration
{
    /**
     * array   Mapping returned by integration.
     */
    protected $mapping = false;

    /**
     * string.
     */
    protected $stopSyncMauticKey = 'ines_stop_sync';

    /**
     * @return string
     */
    public function getName()
    {
        return 'Ines';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        $message = $this->getTranslator()->trans('mautic.ines.description');

        return $message;
    }

    /**
     * Integration supports "push lead to integration" action.
     *
     * @return array(string)
     */
    public function getSupportedFeatures()
    {
        return ['push_lead'];
    }

    /**
     * List of fields needed to configure the INES connexion
     * The fields in the config form, their hydration and save are handled automatically.
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'compte'   => 'mautic.ines.form.account',
            'userName' => 'mautic.ines.form.user',
            'password' => 'mautic.ines.form.password',
        ];
    }

    /**
     * Indicates which fields of the plugin config must be INPUT of type PASSWORD.
     *
     * @return array
     */
    public function getSecretKeys()
    {
        return [
            'password',
        ];
    }

    /**
     * Adds fields in the "config" and "features" forms of the plugin.
     *
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        // As long as the user navigates between the config tabs, it is preferable not to keep
        // the config from the INES WS: if it is modified at INES we must know it right away.
        // For example, adding a custom field.
        $this->unsetCurrentSyncConfig();

        // Tab Enabled/Auth
        if ($formArea == 'keys') {

            // Add a button to check auth
            $builder->add('check_api_button', 'standalone_button', [
                'label' => 'mautic.ines.form.check.btn',
                'attr'  => [
                    'class'   => 'btn btn-primary',
                    'onclick' => "
                        var btn = mQuery(this);
                        btn.next('.message').remove();
                        Mautic.postForm(mQuery('form[name=\"integration_details\"]'), function (response) {
                            if (response.newContent) {
                                Mautic.processModalContent(response, '#IntegrationEditModal');
                            } else {
                                mQuery.ajax({
                                    url: mauticAjaxUrl,
                                    type: 'POST',
                                    data: 'action=plugin:mauticCrm:inesCheckConnexion',
                                    dataType: 'json',
                                    success: function (response) {
                                        btn.after('<span class=\"message\" style=\"font-weight:bold; margin-left:10px;\">' + response.message + '</span>');
                                    },
                                    error: Mautic.processAjaxError,
                                    complete: Mautic.stopIconSpinPostEvent
                                });
                            }
                        });",
                    'icon' => 'fa fa-check',
                ],
                'required' => false,
            ]);
        }

        // Features tab
        elseif ($formArea == 'features') {

            // checkbox : full sync mode?
            $builder->add(
                'full_sync',
                'choice',
                [
                    'choices' => [
                        'is_full_sync' => 'mautic.ines.isfullsync',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.ines.form.isfullsync',
                    'label_attr'  => ['class' => 'control-label'],
                    'empty_value' => false,
                    'required'    => false,
                    'attr'     => array(
                        'onchange' => "
                            var checkbox = mQuery(this),
                                is_checked = checkbox.prop('checked');
                            if (is_checked) {
                                checkbox.closest('#features-container').find('input:checkbox:first').prop('checked', true);
                            }
                        "
                    )
                ]
            );

            // Button: open sync log
            $logsUrl = $this->dispatcher->getContainer()->get('router')->generate('ines_logs');
            $builder->add('goto_logs_button', 'standalone_button', [
                'label' => 'mautic.ines.form.gotologs.btn',
                'attr'  => [
                    'class'   => 'btn',
                    'onclick' => "window.open('$logsUrl')",
                ],
                'required' => false,
            ]);

            // List of fields available at INES and available for the "erasable field" option
            try {
                if ($this->isAuthorized()) {
                    $inesFields = $this->getApiHelper()->getLeadFields();
                    $choices    = [];
                    foreach ($inesFields as $field) {
                        if ($field['excludeFromEcrasableConfig']) {
                            continue;
                        }
                        $key           = $field['concept'].'_'.$field['inesKey'];
                        $choices[$key] = $field['inesLabel'];
                    }

                    $builder->add(
                        'not_ecrasable_fields',
                        'choice',
                        [
                            'choices'     => $choices,
                            'expanded'    => true,
                            'multiple'    => true,
                            'label'       => 'mautic.ines.form.protected.fields',
                            'label_attr'  => ['class' => ''],
                            'empty_value' => false,
                            'required'    => false,
                        ]
                    );
                }
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Checks if there is an INES session ID, or, if not, whether the WS access codes are valid or not.
     *
     * @return bool
     */
    public function isAuthorized()
    {
        if (!$this->isConfigured()) {
            return false;
        }
        $sessionID = $this->getWebServiceCurrentSessionID();

        return $sessionID ? true : $this->checkAuth();
    }

    /**
     * Indicates whether full sync mode is checked or not in the plugin config.
     *
     * @return bool
     */
    public function isFullSync()
    {
        $settings = $this->getIntegrationSettings();

        // If the integration is deactivated, nothing should be synchronized
        if ($settings->getIsPublished() === false) {
            return false;
        }

        $featureSettings = $settings->getFeatureSettings();
        $isFullSync      = (isset($featureSettings['full_sync']) && count($featureSettings['full_sync']) === 1);

        return $isFullSync;
    }

    /**
     * Checks if the access codes to the web services are valid
     * The INES session is deleted first if it exists.
     *
     * @return bool
     */
    public function checkAuth()
    {
        try {
            $this->getApiHelper()->refreshSessionID();

            return true;
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            return false;
        }
    }

    /**
     * Respectively: reads, writes and clean WS session ID in cache.
     */
    public function getWebServiceCurrentSessionID()
    {
        return $this->getCache()->get('ines_session_id');
    }
    public function setWebServiceCurrentSessionID($sessionID)
    {
        $this->getCache()->set('ines_session_id', $sessionID);
    }
    public function unsetWebServiceCurrentSessionID()
    {
        $this->getCache()->delete('ines_session_id');
    }

    /**
     * Respectively: reads, writes and clean INES sync config in cache.
     */
    public function getCurrentSyncConfig()
    {
        return $this->getCache()->get('ines_sync_config');
    }
    public function setCurrentSyncConfig($syncConfig)
    {
        $this->getCache()->set('ines_sync_config', $syncConfig);
    }
    public function unsetCurrentSyncConfig()
    {
        $this->getCache()->delete('ines_sync_config');
    }

    /**
     * Prepare field mapping form.
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getAvailableLeadFields($settings = [])
    {
        $inesFields        = [];
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;

        try {
            if ($this->isAuthorized()) {
                $leadFields = $this->getApiHelper()->getLeadFields();

                // Preparing Form Fields
                foreach ($leadFields as $field) {

                    // Fields whose mapping is imposed internally are excluded from the mapping form
                    if ($field['autoMapping'] !== false) {
                        continue;
                    }

                    $key   = $field['concept'].'_'.$field['inesKey'];
                    $value = [
                        'type'     => 'string',
                        'label'    => $field['inesLabel'],
                        'required' => $field['isMappingRequired'],
                    ];

                    $inesFields[$key] = $value;
                }
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            if (!$silenceExceptions) {
                throw $e;
            }
        }

        return $inesFields;
    }

    /**
     * Returns the raw mapping from the mapping form as an associative array: ines_key => mautic_key
          * Automatically mapped fields are not included.
     *
     * @return array
     */
    public function getRawMapping()
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $featureSettings = $this->getIntegrationSettings()->getFeatureSettings();
        $rawMapping      = $featureSettings['leadFields'];

        return $rawMapping;
    }

    /**
     * Returns the identifier of the Mautic field containing the "do not synchronize" flag.
     *
     * @return string
     */
    public function getDontSyncMauticKey()
    {
        // Le champ custom existe-t-il ?
        $repo         = $this->em->getRepository('MauticLeadBundle:LeadField');
        $searchResult = $repo->findByAlias($this->stopSyncMauticKey);

        return empty($searchResult) ? '' : $this->stopSyncMauticKey;
    }

    /**
     * Checks whether a contact has the "do not sync" flag raised.
     *
     * @param Mautic\LeadBundle\Entity\Lead $lead
     *
     * @return bool
     */
    public function getDontSyncFlag(Lead $lead)
    {
        $dontSyncMauticKey = $this->getDontSyncMauticKey();

        // Search "don't sync" field
        $fields = $lead->getProfileFields();
        foreach ($fields as $key => $value) {
            if ($key == $dontSyncMauticKey) {
                return (bool) $value;
            }
        }

        // If not found, don't sync
        return true;
    }

    /**
     * Retourn the list of non-erasable fields.
     *
     * @param string $filterByConcept contact | client
     *
     * @return array
     */
    public function getNotEcrasableFields($filterByConcept = false)
    {
        $featureSettings = $this->getIntegrationSettings()->getFeatureSettings();
        $fields          = isset($featureSettings['not_ecrasable_fields']) ? $featureSettings['not_ecrasable_fields'] : [];

        // Gross return, syntax : concept_fieldKey
        if ($filterByConcept === false) {
            return $fields;
        }

        // Return filtered and without prefix
        $conceptLength = strlen($filterByConcept);
        foreach ($fields as $f => $field) {
            if (substr($field, 0, $conceptLength) == $filterByConcept) {
                $fields[$f] = substr($field, $conceptLength + 1);
            }
        }

        return $fields;
    }

    /**
     * Returns full mapping, with all known details about each field.
     *
     * @return array
     */
    public function getMapping()
    {
        // If already generated in the same runtime, return it immediately
        if ($this->mapping !== false) {
            return $this->mapping;
        }

        $mappedFields = [];

        // List of all available INES fields
        $leadFields = $this->getApiHelper()->getLeadFields();

        // Non erasable fields ? (syntax : concept_inesKey)
        // 1 : Those checked in the form by the user
        $notEcrasableFields = $this->getNotEcrasableFields();
        // 2 : Fields excluded from the form
        foreach ($leadFields as $field) {
            if ($field['excludeFromEcrasableConfig']) {
                $notEcrasableFields[] = $field['concept'].'_'.$field['inesKey'];
            }
        }

        // Custom fields
        $customFields = [];
        foreach ($leadFields as $field) {
            if ($field['isCustomField']) {
                $customFields[] = $field['concept'].'_'.$field['inesKey'];
            }
        }

        // Auto-mapped fields
        foreach ($leadFields as $field) {
            if ($field['autoMapping'] !== false) {
                $internalKey = $field['concept'].'_'.$field['inesKey'];

                $mappedFields[] = [
                    'concept'       => $field['concept'],
                    'inesFieldKey'  => $field['inesKey'],
                    'isCustomField' => $field['isCustomField'] ? 1 : 0,
                    'mauticFieldKey'  => $field['autoMapping'],
                    'isEcrasable'   => in_array($internalKey, $notEcrasableFields) ? 0 : 1,
                ];
            }
        }

        // Field mapped by user
        $rawMapping = $this->getRawMapping();
        foreach ($rawMapping as $internalKey => $mauticKey) {
            list($concept, $inesKey) = explode('_', $internalKey);

            $mappedFields[] = [
                'concept'       => $concept,
                'inesFieldKey'  => $inesKey,
                'isCustomField' => in_array($internalKey, $customFields) ? 1 : 0,
                'mauticFieldKey'  => $mauticKey,
                'isEcrasable'   => in_array($internalKey, $notEcrasableFields) ? 0 : 1,
            ];
        }

        // Storing result in case of a call in the same runtime
        $this->mapping = $mappedFields;

        return $mappedFields;
    }

    /**
     * Save INES contact and client keys (= company) in the dedicated fields of a lead (defined by the mapping).
     *
     * @param Mautic\LeadBundle\Entity\Lead $lead
     * @param int                           $internalCompanyRef INES client ID
     * @param int                           $internalContactRef INES contact ID
     *
     * @return Mautic\LeadBundle\Entity\Lead
     */
    public function setInesKeysToLead($lead, $internalCompanyRef, $internalContactRef)
    {
        $fieldsToUpdate = [];

        // Search for dedicated Mautic fields to store these keys
        $mapping = $this->getMapping();
        foreach ($mapping as $mappingItem) {
            if ($mappingItem['inesFieldKey'] == 'InternalContactRef') {
                $fieldsToUpdate[ $mappingItem['mauticFieldKey'] ] = $internalContactRef;
            }
            if ($mappingItem['inesFieldKey'] == 'InternalCompanyRef') {
                $fieldsToUpdate[ $mappingItem['mauticFieldKey'] ] = $internalCompanyRef;
            }
        }

        // Save lead fields
        $model = $this->dispatcher->getContainer()->get('mautic.lead.model.lead');
        $model->setFieldValues($lead, $fieldsToUpdate, true);
        $model->saveEntity($lead);

        return $lead;
    }

    /**
     * Returns INES contact and client keys saved in an Mautic lead.
     *
     * @param Mautic\LeadBundle\Entity\Lead $lead
     *
     * @return [int|false, int|false]      [$contactRef, $clienRef]
     */
    public function getInesKeys(Lead $lead)
    {
        $fields = $lead->getProfileFields();

        // Mautic fields containing the INES keys
        $mauticFieldsKeys = $this->getApiHelper()->getMauticFieldsKeysFromInesFieldsKeys(['InternalContactRef', 'InternalCompanyRef']);

        // Values for these 2 fields (contactRef and clientRef) if they are defined
        $contactRef = false;
        if (isset($mauticFieldsKeys['InternalContactRef'])) {
            $inesContactMauticKey = $mauticFieldsKeys['InternalContactRef'];
            if (isset($fields[$inesContactMauticKey]) && $fields[$inesContactMauticKey]) {
                $contactRef = $fields[$inesContactMauticKey];
            }
        }

        $clientRef = false;
        if (isset($mauticFieldsKeys['InternalCompanyRef'])) {
            $inesCompanyMauticKey = $mauticFieldsKeys['InternalCompanyRef'];
            if (isset($fields[$inesCompanyMauticKey]) && $fields[$inesCompanyMauticKey]) {
                $clientRef = $fields[$inesCompanyMauticKey];
            }
        }

        return [$contactRef, $clientRef];
    }

    /**
     * SOAP request
     * If the request must contain a header, fill $settings['soapHeader'].
     *
     * @param string $url        Full URL of the SOAP request
     * @param array  $parameters Arguments to pass to $method
     * @param string $method     SOAP method
     * @param array  $settings   Request settings
     *
     * @return object
     */
    public function makeRequest($url, $parameters = [], $method = '', $settings = [])
    {
        $client = new \SoapClient($url);

        // SOAP Header
        $soapHeader = isset($settings['soapHeader']) ? $settings['soapHeader'] : false;
        if ($soapHeader !== false) {
            $client->__setSoapHeaders(
                new \SoapHeader($soapHeader['namespace'], $soapHeader['name'], $soapHeader['datas'])
            );
        }

        // Web-service call, with or without args
        if (!empty($parameters)) {
            $response = $client->$method($parameters);
        } else {
            $response = $client->$method();
        }

        return $response;
    }

    /**
     * Enqueue a lead in sync queue, if not already enqueued.
     *
     * @param Mautic\LeadBundle\Entity\Lead $lead
     * @param string                        $action 'UPDATE' | 'DELETE'
     *
     * @return bool
     */
    public function enqueueLead(Lead $lead, $action = 'UPDATE')
    {
        $leadId         = $lead->getId();
        $company        = $this->getLeadMainCompany($leadId);
        $dontSyncToInes = $this->getDontSyncFlag($lead);

        // The lead must not be anonymous
        if (!empty($lead->getEmail()) && !empty($company) && !$dontSyncToInes) {

            // 'full sync' mode required
            if ($this->isFullSync()) {

                // If the lead already exists in the queue, it is deleted
                // Avoid multiple updates.
                // Considers the last action to be priority over others.
                $this->dequeuePendingLead($lead->getId());

                // Insert line into table "ines_sync_log"
                $inesSyncLogModel = $this->dispatcher->getContainer()->get('mautic.crm.model.ines_sync_log');
                $entity           = $inesSyncLogModel->getEntity();

                $company = $this->getLeadMainCompany($lead->getId());

                if ($action == 'UPDATE') {
                    // If UPDATE : reference = Mautic contact ID
                    $refId = $lead->getId();
                } else {
                    // If DELETE : reference = INES contact ref
                    list($contactRef, $clientRef) = $this->getInesKeys($lead);
                    $refId                        = $contactRef;
                }

                $entity->setAction($action)
                       ->setLeadId($refId)
                       ->setLeadEmail($lead->getEmail())
                       ->setLeadCompany($company);

                $inesSyncLogModel->saveEntity($entity);

                return true;
            }
        }

        return false;
    }

    /**
     * Dequeue pending lead from sync queue.
     *
     * @param int $leadId
     */
    public function dequeuePendingLead($leadId)
    {
        $inesSyncLogModel = $this->dispatcher->getContainer()->get('mautic.crm.model.ines_sync_log');
        $inesSyncLogModel->removeEntitiesBy(
            [
                'leadId' => $leadId,
                'status' => 'PENDING',
            ]
        );
    }

    /**
     * Adds in the queue a batch of leads that have never been synchronized, only if the queue is empty.
     * Allows to automatically and progressively manage the 1st sync when the full-sync mode is enable.
     *
     * @param $limit
     *
     * @return $enqueuedCounter Number of leads added
     */
    public function firstSyncCheckAndEnqueue($limit = 100)
    {
        $inesSyncLogModel = $this->dispatcher->getContainer()->get('mautic.crm.model.ines_sync_log');
        $leadModel        = $this->dispatcher->getContainer()->get('mautic.lead.model.lead');

        // If the queue is not empty, nothing is done
        if (!$inesSyncLogModel->havePendingEntities('UPDATE')) {
            return 0;
        }

        // Search for Mautic keys containing INES contact and client keys
        $mauticFieldsKeys = $this->getApiHelper()->getMauticFieldsKeysFromInesFieldsKeys(
            ['InternalContactRef', 'InternalCompanyRef']
        );
        if (isset($mauticFieldsKeys['InternalContactRef']) && isset($mauticFieldsKeys['InternalCompanyRef'])) {
            $inesContactMauticKey = $mauticFieldsKeys['InternalContactRef'];
            $inesClientMauticKey  = $mauticFieldsKeys['InternalCompanyRef'];
        } else {
            return 0;
        }

        // Search for leads with a company AND an email AND INES keys not filled in
        $items = $this->em
             ->getConnection()
             ->createQueryBuilder()
             ->select('DISTINCT(l.id)')
             ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl')
             ->innerJoin('cl', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = cl.lead_id')
             ->where(
                'l.email <> "" AND ('.
                    'l.'.$inesContactMauticKey.' IS NULL OR '.
                    'l.'.$inesContactMauticKey.' <= 0 OR '.
                    'l.'.$inesContactMauticKey.' LIKE "" OR '.
                    'l.'.$inesClientMauticKey.' IS NULL OR '.
                    'l.'.$inesClientMauticKey.' <= 0 OR '.
                    'l.'.$inesClientMauticKey.' LIKE "" '.
                ')'
             )
             ->setFirstResult(0)
             ->setMaxResults($limit)
             ->execute()
             ->fetchAll();

        // Enqueue leads found
        $enqueuedCounter = 0;
        if ($items) {
            foreach ($items as $item) {
                $leadId = $item['id'];
                $lead   = $leadModel->getEntity($leadId);
                if ($this->enqueueLead($lead)) {
                    ++$enqueuedCounter;
                }
            }
        }

        return $enqueuedCounter;
    }

    /**
     * Synchronizes a batch of leads from sync queue.
     *
     * @param int $numberToProcess
     */
    public function syncPendingLeadsToInes($numberToProcess)
    {
        $updatedCounter       = 0;
        $failedUpdatedCounter = 0;
        $deletedCounter       = 0;
        $failedDeletedCounter = 0;
        $apiHelper            = $this->getApiHelper();
        $leadModel            = $this->dispatcher->getContainer()->get('mautic.lead.model.lead');

        // STEP 1 : UPDATE a batch of leads
        $inesSyncLogModel = $this->dispatcher->getContainer()->get('mautic.crm.model.ines_sync_log');
        $pendingItems     = $inesSyncLogModel->getPendingEntities('UPDATE', $numberToProcess);

        foreach ($pendingItems as $item) {

            // Current lead?
            $leadId = $item->getLeadId();
            $lead   = $leadModel->getEntity($leadId);

            // Sync if found
            if ($lead && $lead->getId() == $leadId) {
                $syncOk = $apiHelper->syncLeadToInes($lead);

                $itemCounter = $item->getCounter();

                // Sync OK
                if ($syncOk) {
                    ++$updatedCounter;
                    $itemStatus = 'DONE';
                    ++$itemCounter;

                    // In the case of the sync of a new lead, the writing of INES keys contact and client in the lead
                    // trigger an inadvertent addition of the lead to the queue. Hence this cleaning:
                    $this->dequeuePendingLead($lead->getId());
                }
                // Sync FAILED
                else {
                    ++$failedUpdatedCounter;
                    ++$itemCounter;
                    if ($itemCounter == 3) {
                        $itemStatus = 'FAILED';
                    }
                }

                // Update DB
                $item->setCounter($itemCounter);
                $item->setStatus($itemStatus);
                $inesSyncLogModel->saveEntity($item);
            }
            // If not found: FAILED
            else {
                $item->setStatus('FAILED');
                $inesSyncLogModel->saveEntity($item);
            }
        }

        // STEP 2: DELETE a batch of leads
        $pendingDeletingItems = $inesSyncLogModel->getPendingEntities('DELETE', $numberToProcess);
        foreach ($pendingDeletingItems as $item) {
            $inesRefId = $item->getLeadId();

            if ($inesRefId > 0) {
                $itemCounter = $item->getCounter();

                $deleteOk = $apiHelper->deleteContact($inesRefId);

                if ($deleteOk) {
                    ++$deletedCounter;
                    $itemStatus = 'DONE';
                    ++$itemCounter;
                } else {
                    ++$failedDeletedCounter;
                    ++$itemCounter;
                    if ($itemCounter == 3) {
                        $itemStatus = 'FAILED';
                    }
                }

                $item->setCounter($itemCounter);
                $item->setStatus($itemStatus);
                $inesSyncLogModel->saveEntity($item);
            } else {
                $item->setStatus('FAILED');
                $inesSyncLogModel->saveEntity($item);
            }
        }

        return [$updatedCounter, $failedUpdatedCounter, $deletedCounter, $failedDeletedCounter];
    }

    /**
     * Returns the name of the main company (= 1st in the list) linked to a contact.
     * Or an empty string if it does not exist.
     * If $onlyName is set to false, returns all fields in that company, or false.
     *
     * @param int  $leadId
     * @param bool $onlyName
     *
     * @return string | array | false
     */
    public function getLeadMainCompany($leadId, $onlyName = true)
    {
        $companyRepo = $this->em->getRepository('MauticLeadBundle:Company');
        $companies   = $companyRepo->getCompaniesByLeadId($leadId);

        if ($onlyName) {
            return isset($companies[0]) ? $companies[0]['companyname'] : '';
        } else {
            if (!isset($companies[0]['id'])) {
                return false;
            }

            $company_id = $companies[0]['id'];
            $company    = $companyRepo->getEntity($company_id);

            return $company->getProfileFields();
        }
    }

    /**
     * Returns the last event written in the timeline of a lead.
     *
     * @param Lead $lead
     *
     * @return string
     */
    public function getLastTimelineEvent(Lead $lead)
    {
        $leadModel = $this->dispatcher->getContainer()->get('mautic.lead.model.lead');

        $leadEngagements = $leadModel->getEngagements($lead, $filters = [], null, 1, $limit = 1);

        if (is_array($leadEngagements['events'])) {
            $lastTimelineEvent = $leadEngagements['events'][0];
            $eventDescription  = $this->getTranslator()->trans($lastTimelineEvent['event']);
            $eventDescription .= ' - '.$lastTimelineEvent['eventLabel']['label'];
        } else {
            $eventDescription = '';
        }

        return $eventDescription;
    }

    /**
     * Creates or updates, in Mautic, the custom fiels that the user may need for mapping
     * Each field has a type (int, bool, list, ...) and a configuration (list of values, etc.)
     * The config of certain fields is fixed, and for others it is read via a WS INES.
     */
    public function updateMauticCustomFieldsDefinitions()
    {
        $model = $this->dispatcher->getContainer()->get('mautic.lead.model.field');
        $repo  = $model->getRepository();

        $this->log('Check Mautic custom fields');

        // List of fields that must exist in Mautic and must be checked
        $fieldsToCheck = [];
        $inesFields    = $this->getApiHelper()->getLeadFields();
        foreach ($inesFields as $inesField) {
            if ($inesField['mauticCustomFieldToCreate'] !== false) {
                $fieldToCheck         = $inesField['mauticCustomFieldToCreate'];
                $fieldToCheck['name'] = $inesField['inesLabel'];

                $fieldsToCheck[] = $fieldToCheck;
            }
        }

        // Also create the field "Do not sync to INES"
        $fieldsToCheck[] = [
            'name'  => 'Stop Synchro INES',
            'type'  => 'boolean',
            'alias' => $this->stopSyncMauticKey,
        ];

        // Check each field
        foreach ($fieldsToCheck as $fieldToCheck) {
            $alias        = $fieldToCheck['alias'];
            $type         = isset($fieldToCheck['type']) ? $fieldToCheck['type'] : 'text';
            $defaultValue = null;

            // Preparing the field config
            // (will only be useful if creating or updating)
            if ($type == 'number') {
                $properties = [
                    'roundmode' => 4,
                    'precision' => 0,
                ];
            } elseif ($type == 'boolean') {
                $properties = [
                    'no'  => 'No',
                    'yes' => 'Yes',
                ];
            } elseif ($type == 'select') {

                // Possible (Keys / Values) for field
                $properties = ['list' => []];
                foreach ($fieldToCheck['values'] as $value => $label) {
                    $properties['list'][] = ['label' => $label, 'value' => $value];
                }

                // If requested, first value of the list = default value
                if (isset($fieldToCheck['firstValueAsDefault']) && $fieldToCheck['firstValueAsDefault']) {
                    $keys         = array_keys($fieldToCheck['values']);
                    $defaultValue = $keys[0];
                }
            } else {
                $properties = [];
            }

            // Does the field exist?
            $searchResult = $repo->findByAlias($alias);
            if (empty($searchResult)) {

                // The field does not exist: CREATE

                $this->log('Create custom field : '.$alias);

                $fieldEntity = new LeadField();
                $fieldEntity->setGroup('core');
                $fieldEntity->setAlias($alias);
                $fieldEntity->setName($fieldToCheck['name']);
                $fieldEntity->setType($type);
                $model->setFieldProperties($fieldEntity, $properties);

                if ($defaultValue !== null) {
                    $fieldEntity->setDefaultValue($defaultValue);
                }

                try {
                    $model->saveEntity($fieldEntity);
                } catch (\Exception $e) {
                    $this->log("Can't create field ".$alias.' '.$e->getMessage());
                }
            } else {
                // The field exists: is its config correct?
                $fieldEntity       = $searchResult[0];
                $currentProperties = $fieldEntity->getProperties();

                $updateNeeded = false;

                // The field type must match
                if ($fieldEntity->getType() != $type) {
                    $updateNeeded = true;
                } elseif ($type == 'select') {

                    // The list of possible values must match
                    if (!isset($currentProperties['list']) || !is_array($currentProperties['list'])) {
                        $updateNeeded = true;
                    } else {
                        // Comparison of existing and desired torques (key / value)
                        // If there is an error, the field must be updated
                        $tmpValues = $fieldToCheck['values'];
                        foreach ($currentProperties['list'] as $pair) {
                            if (array_key_exists($pair['value'], $tmpValues) && $tmpValues[$pair['value']] == $pair['label']) {
                                unset($tmpValues[$pair['value']]);
                            } else {
                                $updateNeeded = true;
                                break;
                            }
                        }
                        if (!$updateNeeded && !empty($tmpValues)) {
                            $updateNeeded = true;
                        }
                    }
                }

                if ($updateNeeded) {
                    $this->log('Update custom field : '.$alias);

                    $fieldEntity->setType($type);
                    $model->setFieldProperties($fieldEntity, $properties);

                    try {
                        $model->saveEntity($fieldEntity);
                    } catch (\Exception $e) {
                        $this->log("Can't update field ".$alias.' '.$e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Writes a line in the log of Mautic.
     *
     * @param object $object
     */
    public function log($object)
    {
        $linearObject = is_string($object) ? $object : var_export($object, true);
        $this->dispatcher->getContainer()->get('logger')->log('info', 'INES LOG : '.$linearObject);
    }
}
