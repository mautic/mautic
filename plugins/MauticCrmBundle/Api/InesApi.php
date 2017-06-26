<?php
/**
 * @copyright   2016 Webmecanik
 * @author      Webmecanik
 * @link        http://www.webmecanik.com
 */

namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class InesApi
 */
class InesApi extends CrmApi
{

    /**
     * Forces the deletion / update of the session ID of the web service
     * Used by the "Test Connection" button on the config tab
     *
     * @return int Session ID
     * @throws ApiErrorException
     */
    public function refreshSessionID()
    {
        $this->integration->unsetWebServiceCurrentSessionID();
        $newSessionID = $this->getSessionID();
        return $newSessionID;
    }


    /**
     * Returns the list of fields available at INES
     *
     * @return array
     */
    public function getLeadFields()
    {
        $inesConfig = $this->getInesSyncConfig();

        // Keys that will be mapped to the values below
        $fieldKeys = array(
            'concept', 'inesKey', 'inesLabel', 'isCustomField',
            'isMappingRequired', 'autoMapping', 'excludeFromEcrasableConfig',
            'mauticCustomFieldToCreate',
        );

        /////// STEP 1
        // All standard INES fields that can be mapped with Mautic
        // Not included: Contact score and Unsubscribe flag (DNC) because managed off-mapping
        // Warning: the e-mail field must not be auto-mapped, otherwise the sync may not be triggered by CrmAbstractIntegration :: pushLead

        $defaultInesFields = array();
        $defaultInesFields[] = array('contact', 'InternalContactRef', 'mautic.ines.field.internalcontactref', false, true, 'ines_contact_ref', true, ['alias' => "ines_contact_ref", 'type' => "number"]);
        $defaultInesFields[] = array('contact', 'InternalCompanyRef', 'mautic.ines.field.internalcompanyref', false, true, 'ines_client_ref', true, ['alias' => "ines_client_ref", 'type' => "number"]);
        $defaultInesFields[] = array('contact', 'PrimaryMailAddress', 'mautic.ines.field.primarymailaddress', false, true, false, true, false);
        $defaultInesFields[] = array('contact', 'Genre', 'mautic.ines.field.genre.contact', false, false, false, false, ['alias' => "ines_contact_civilite"]);
        $defaultInesFields[] = array('contact', 'LastName', 'mautic.ines.field.lastname.contact', false, false, 'lastname', false, false);
        $defaultInesFields[] = array('contact', 'FirstName', 'mautic.ines.field.firstname.contact', false, false, 'firstname', false, false);
        $defaultInesFields[] = array('contact', 'Function', 'mautic.ines.field.function.contact', false, false, false, false, ['alias' => "ines_contact_fonction"]);
        $defaultInesFields[] = array('contact', 'Type', 'mautic.ines.field.type.contact', false, false, false, false, ['alias' => "ines_contact_type", 'type' => "select", 'valuesFromWS' => "GetTypeContactList", 'firstValueAsDefault' => true]);
        $defaultInesFields[] = array('contact', 'Service', 'mautic.ines.field.service.contact', false, false, false, false, ['alias' => "ines_contact_service"]);
        $defaultInesFields[] = array('contact', 'BussinesTelephone', 'mautic.ines.field.bussinestelephone.contact', false, false, false, false, ['alias' => "ines_contact_tel_bureau", 'type' => "tel"]);
        $defaultInesFields[] = array('contact', 'HomeTelephone', 'mautic.ines.field.hometelephone.contact', false, false, false, false, ['alias' => "ines_contact_tel_domicile", 'type' => "tel"]);
        $defaultInesFields[] = array('contact', 'MobilePhone', 'mautic.ines.field.mobilephone.contact', false, false, false, false, ['alias' => "ines_contact_tel_mobile", 'type' => "tel"]);
        $defaultInesFields[] = array('contact', 'Fax', 'mautic.ines.field.fax.contact', false, false, false, false, ['alias' => "ines_contact_fax", 'type' => "tel"]);
        $defaultInesFields[] = array('contact', 'HomeAddress', 'mautic.ines.field.homeaddress.contact', false, false, false, false, ['alias' => "ines_contact_adr1"]);
        $defaultInesFields[] = array('contact', 'BusinessAddress', 'mautic.ines.field.businessaddress.contact', false, false, false, false, ['alias' => "ines_contact_adr2"]);
        $defaultInesFields[] = array('contact', 'ZipCode', 'mautic.ines.field.zipcode.contact', false, false, false, false, ['alias' => "ines_contact_cp"]);
        $defaultInesFields[] = array('contact', 'City', 'mautic.ines.field.city.contact', false, false, false, false, ['alias' => "ines_contact_ville"]);
        $defaultInesFields[] = array('contact', 'State', 'mautic.ines.field.state.contact', false, false, false, false, ['alias' => "ines_contact_region"]);
        $defaultInesFields[] = array('contact', 'Country', 'mautic.ines.field.country.contact', false, false, false, false, ['alias' => "ines_contact_pays"]);
        $defaultInesFields[] = array('contact', 'Language', 'mautic.ines.field.language.contact', false, false, false, false, ['alias' => "ines_contact_lang"]);
        $defaultInesFields[] = array('contact', 'Author', 'mautic.ines.field.author.contact', false, false, false, false, ['alias' => "ines_contact_resp", 'type' => "select", 'valuesFromWS' => "GetUserInfoFromUserRef"]);
        $defaultInesFields[] = array('contact', 'Comment', 'mautic.ines.field.comment.contact', false, false, false, false, ['alias' => "ines_contact_remarque"]);
        $defaultInesFields[] = array('contact', 'Confidentiality', 'mautic.ines.field.confidentiality.contact', false, false, false, false, ['alias' => "ines_contact_diffusion", 'type' => "select", 'values' => ["-1" => "lecture seule", "0" => "lecture / écriture", "1" => "confidentiel"] ]);
        $defaultInesFields[] = array('contact', 'DateOfBirth', 'mautic.ines.field.dateofbirth.contact', false, false, false, false, ['alias' => "ines_contact_birthday", 'type' => "date"]);
        $defaultInesFields[] = array('contact', 'Rang', 'mautic.ines.field.rang.contact', false, false, false, false, ['alias' => "ines_contact_etat", 'type' => "select", 'values' => ["0" => "secondaire", "1" => "principal", "2" => "archivé"] ]);
        $defaultInesFields[] = array('contact', 'SecondaryMailAddress', 'mautic.ines.field.secondarymailaddress.contact', false, false, false, false, ['alias' => "ines_contact_email2"]);
        $defaultInesFields[] = array('contact', 'NPai', 'mautic.ines.field.npai', false, false, 'ines_contact_npai', true, ['alias' => 'ines_contact_npai', 'type' => "boolean"]);
        $defaultInesFields[] = array('client', 'CompanyName', 'mautic.ines.field.companyname', false, true, 'company', true, false);
        $defaultInesFields[] = array('client', 'Type', 'mautic.ines.field.type.company', false, false, false, false, ['alias' => "ines_client_type", 'type' => "select", 'valuesFromWS' => "GetTypeClientList", 'firstValueAsDefault' => true]);
        $defaultInesFields[] = array('client', 'Manager', 'mautic.ines.field.manager.company', false, false, false, false, ['alias' => "ines_client_resp_dossier", 'type' => "select", 'valuesFromWS' => "GetUserInfoFromUserRef"]);
        $defaultInesFields[] = array('client', 'SalesResponsable', 'mautic.ines.field.salesresponsable.company', false, false, false, false, ['alias' => "ines_client_commercial", 'type' => "select", 'valuesFromWS' => "GetUserInfoFromRHRef"]);
        $defaultInesFields[] = array('client', 'TechnicalResponsable', 'mautic.ines.field.technicalresponsable.company', false, false, false, false, ['alias' => "ines_client_resp_tech", 'type' => "select", 'valuesFromWS' => "GetUserInfoFromUserRef"]);
        $defaultInesFields[] = array('client', 'Phone', 'mautic.ines.field.phone.company', false, false, false, false, ['alias' => "ines_client_tel", 'type' => "tel"]);
        $defaultInesFields[] = array('client', 'Fax', 'mautic.ines.field.fax.company', false, false, false, false, ['alias' => "ines_client_fax", 'type' => "tel"]);
        $defaultInesFields[] = array('client', 'Address1', 'mautic.ines.field.address1.company', false, false, false, false, ['alias' => "ines_client_adr1"]);
        $defaultInesFields[] = array('client', 'Address2', 'mautic.ines.field.address2.company', false, false, false, false, ['alias' => "ines_client_adr2"]);
        $defaultInesFields[] = array('client', 'ZipCode', 'mautic.ines.field.zipcode.company', false, false, false, false, ['alias' => "ines_client_cp"]);
        $defaultInesFields[] = array('client', 'City', 'mautic.ines.field.city.company', false, false, false, false, ['alias' => "ines_client_ville"]);
        $defaultInesFields[] = array('client', 'State', 'mautic.ines.field.state.company', false, false, false, false, ['alias' => "ines_client_region"]);
        $defaultInesFields[] = array('client', 'Country', 'mautic.ines.field.country.company', false, false, false, false, ['alias' => "ines_client_pays"]);
        $defaultInesFields[] = array('client', 'Origin', 'mautic.ines.field.origin.company', false, false, false, false, ['alias' => "ines_client_origine", 'type' => "select", 'valuesFromWS' => "GetOriginList"]);
        $defaultInesFields[] = array('client', 'Website', 'mautic.ines.field.website.company', false, false, false, false, ['alias' => "ines_client_site_web", 'type' => "url"]);
        $defaultInesFields[] = array('client', 'Confidentiality', 'mautic.ines.field.confidentiality.company', false, false, false, false, ['alias' => "ines_client_diffusion", 'type' => "select", 'values' => ["-1" => "lecture seule", "0" => "lecture / écriture", "1" => "confidentiel"]]);
        $defaultInesFields[] = array('client', 'Comments', 'mautic.ines.field.comments.company', false, false, false, false, ['alias' => "ines_client_remarque"]);
        $defaultInesFields[] = array('client', 'CustomerNumber', 'mautic.ines.field.customernumber.company', false, false, false, false, ['alias' => "ines_client_num_client", 'type' => "number"]);
        $defaultInesFields[] = array('client', 'Language', 'mautic.ines.field.language.company', false, false, false, false, ['alias' => "ines_client_lang"]);
        $defaultInesFields[] = array('client', 'Activity', 'mautic.ines.field.activity.company', false, false, false, false, ['alias' => "ines_client_activite"]);
        $defaultInesFields[] = array('client', 'Scoring', 'mautic.ines.field.scoring.company', false, false, false, false, ['alias' => "ines_client_score"]);

        $inesFields = array();
        foreach($defaultInesFields as $field) {
            $inesField = array_combine($fieldKeys, $field);
            $inesField['inesLabel'] = $this->integration->getTranslator()->trans($inesField['inesLabel']);
            $inesFields[] = $inesField;
        }


        /////// STEP 2
        //// include INES custom fields

        $availableCustomFieldTypes = [
            'text' => 'text', 'url' => 'url', 'list' => 'select', 'user' => 'select', 'numeral' => 'number', 'boolean' => 'boolean', 'date' => 'date'
        ];

        $concepts = ['contact' => 'Contact', 'client' => 'Company'];
        foreach($concepts as $concept => $conceptLabel) {
            if (is_array($inesConfig[$conceptLabel.'CustomFields'])) {
                foreach($inesConfig[$conceptLabel.'CustomFields'] as $field) {

                    // Conversion of INES field type to Mautic field type
                    if (!isset($availableCustomFieldTypes[$field['Type']])) {
                        continue;
                    }
                    $mauticType = $availableCustomFieldTypes[$field['Type']];

                    $mauticCustomFieldToCreate = [
                        'alias' => 'ines_'.$concept.'_custom_'.$field['InesID'],
                        'type' => $mauticType
                    ];

                    // Type INES "user"
                    if ($field['Type'] == 'user') {
                        $mauticCustomFieldToCreate['valuesFromWS'] = 'GetUserInfoFromUserRef';
                    }
                    // List of values with same keys
                    else if ($mauticType == 'select') {

                        $values = end($field['ValueList']);

                        // Exclude empty values
                        $values = array_filter($values, function ($value) {
                            return !empty($value);
                        });

                        // For these lists of values, only the keys are used: the labels are blank
                        $mauticCustomFieldToCreate['values'] = array_combine(
                            array_values($values),
                            array_fill(0, count($values), "")
                        );
                    }

                    $inesLabel = $field['InesName'].' ('.(($concept == 'contact') ? 'contact' : 'société').')';

                    $inesFields[] = array_combine(
                        $fieldKeys,
                        [$concept, $field['InesID'], $inesLabel, true, false, false, false, $mauticCustomFieldToCreate]
                    );
                }
            }
        }


        /////// ETAPE 3
        // Fill in possible keys / values for fields whose values depend on a INES WS

        foreach($inesFields as $k => $field) {

            // If current field is concerned...
            if ($field['mauticCustomFieldToCreate'] !== false && isset($field['mauticCustomFieldToCreate']['valuesFromWS'])) {

                // Does the requested WS exist in the config?
                $wsName = $field['mauticCustomFieldToCreate']['valuesFromWS'];
                if (isset($inesConfig['ValuesFromWS'][$wsName])) {

                    // If yes, use the keys / values read in this WS for the current field
                    $values = $inesConfig['ValuesFromWS'][$wsName];
                    $inesFields[$k]['mauticCustomFieldToCreate']['values'] = $values;
                    unset($inesFields[$k]['mauticCustomFieldToCreate']['ValuesFromWS']);
                }
                else {
                    $this->integration->log("INEW WS not found : $wsName");
                }
            }
        }

        return $inesFields;
    }


    /**
     * Called by "push lead to integration", out of a FORM, a CAMPAIGN ...
     *
     * @param array                         $mappedData These data are not used
     * @param Mautic\LeadBundle\Entity\Lead $lead
     */
    public function createLead($mappedData, Lead $lead)
    {
        $leadId = $lead->getId();
        $company = $this->integration->getLeadMainCompany($leadId);

        // A lead is synchronized only if it has at least an email and a company
        if ( !empty($lead->getEmail()) && !empty($company)) {
            try {
                $this->syncLeadToInes($lead, true);

                // If a lead is synchronized by a "push contact to integaration" direct action,
                // it is removed from the sync queue, dedicated to asynchronous sync via a CRONJOB
                $this->integration->dequeuePendingLead($lead->getId());
            }
            catch (\Exception $e) {
                $this->integration->logIntegrationError($e);
            }
        }
    }


    /**
     * Push any lead to INES CRM
     *
     * @param Lead $lead
     * @param bool $syncTriggeredFromPushLead Indicates whether the sync is
     *                                        from a "push lead to integration"
     *                                        action
     *
     * @return bool Success or failure of sync
     */
    public function syncLeadToInes(Lead $lead, $syncTriggeredFromPushLead = false)
    {
        $leadId = $lead->getId();
        $leadPoints = $lead->getPoints();
        $leadDesaboFlag = empty($lead->getDoNotContact()->toArray()) ? 0 : 1;
        $company = $this->integration->getLeadMainCompany($leadId, false);
        $dontSyncToInes = $this->integration->getDontSyncFlag($lead);

        if ($syncTriggeredFromPushLead) {
            $addLeadDescription = "Lead Mautic - ".$this->integration->getLastTimelineEvent($lead);
        }
        else {
            $addLeadDescription = "Lead Mautic";
        }

        if ( !isset($company['companyname']) || empty($company['companyname']) || $dontSyncToInes) {
            return false;
        }

        // Read all the current lead fields
        $rawFields = $lead->getFields();
        $fieldsValues = array();
        foreach($rawFields as $fieldGroup => $localFields) {
            foreach($localFields as $fieldKey => $field) {
                $fieldsValues[$fieldKey] = $field['value'];
            }
        }

        // Applying mapping to the current lead
        // Disassociating information from the contact and the company
        // as well as standard and custom fields (at INES)
        $mappedDatas = array(
            'contact' => array(
                'standardFields' => array(),
                'customFields' => array()
            ),
            'client' => array(
                'standardFields' => array(),
                'customFields' => array()
            )
        );

        // Structure for storing non-erasable fields
        $inesProtectedFields = array(
            'contact' => array(),
            'client' => array()
        );

        $mapping = $this->integration->getMapping();

        foreach($mapping as $mappingItem) {

            // Lead value for the current field
            // If not defined, it is not stored in the mapped data
            // Special case: company
            if ($mappingItem['mauticFieldKey'] == 'company') {
                $leadValue = $company['companyname'];
            // General case
            } else {
                $leadValue = $fieldsValues[ $mappingItem['mauticFieldKey'] ];
                if ($leadValue == null) {
                    continue;
                }
            }

            // Field key in INES
            $inesFieldKey = $mappingItem['inesFieldKey'];

            // Concept: contact or client
            $concept = $mappingItem['concept'];

            // If the field is not erasable, it is stored: will be useful during the UPDATE (see below)
            if ( !$mappingItem['isEcrasable']) {
                array_push($inesProtectedFields[$concept], $inesFieldKey);
            }

            // Standard or custom field (in INES) ?
            $fieldCategory = ($mappingItem['isCustomField'] == 0) ? 'standardFields' : 'customFields';

            $mappedDatas[$concept][$fieldCategory][$inesFieldKey] = $leadValue;
        }


        // Read the INES references for the contact and the company
        // If it is a new lead, they are unknown, otherwise they must have been previously stored in Mautic
        $internalContactRef = isset($mappedDatas['contact']['standardFields']['InternalContactRef']) ? $mappedDatas['contact']['standardFields']['InternalContactRef'] : 0;
        $internalCompanyRef = isset($mappedDatas['contact']['standardFields']['InternalCompanyRef']) ? $mappedDatas['contact']['standardFields']['InternalCompanyRef'] : 0;

        //// CREATE
        if ( !$internalCompanyRef || !$internalContactRef) {

            // Get INES datas template for WS
            $datas = $this->getClientWithContactsTemplate();

            // Hydratation of the standard fields (concept: client)
            foreach($mappedDatas['client']['standardFields'] as $inesFieldKey => $fieldValue) {
                $datas['client'][$inesFieldKey] = $fieldValue;
            }

            // Hydratation of the standard fields (concept: contact)
            foreach($mappedDatas['contact']['standardFields'] as $inesFieldKey => $fieldValue) {
                $datas['client']['Contacts']['ContactInfoAuto'][0][$inesFieldKey] = $fieldValue;
            }

            // If not specified, the field "Company type" is imposed by the config defined by INES
            if ( !isset($datas['client']['Type']) || $datas['client']['Type'] == 0) {
                $inesConfig = $this->getInesSyncConfig();
                $datas['client']['Type'] = $inesConfig['SocieteType'];
            }

            // Contact ID in Mautic
            $datas['client']['Contacts']['ContactInfoAuto'][0]['MauticRef'] = $leadId;

            // Mautic score
            $datas['client']['Contacts']['ContactInfoAuto'][0]['Scoring'] = $leadPoints;

            // Mautic DNC flag
            $datas['client']['Contacts']['ContactInfoAuto'][0]['Desabo'] = $leadDesaboFlag;

            // SOAP request: create client + contact at INES
            $response = $this->request('ws/wsAutomationsync.asmx', 'AddClientWithContacts', $datas, true, true);

            if (!isset($response['AddClientWithContactsResult']['InternalRef'])
             || !isset($response['AddClientWithContactsResult']['Contacts']['ContactInfoAuto']['InternalRef'])) {
                return false;
            }

            // and retrieving a contact and client key
            $internalCompanyRef = $response['AddClientWithContactsResult']['InternalRef'];
            $internalContactRef = $response['AddClientWithContactsResult']['Contacts']['ContactInfoAuto']['InternalRef'];
            if ( !$internalCompanyRef || !$internalContactRef) {
                return false;
            }

            // Save INES references into dedicated Mautic fields
            $this->integration->setInesKeysToLead($lead, $internalCompanyRef, $internalContactRef);

            // If a lead channel has been configured at INES, the creation of the contact must be followed by the writing of a weblead (in the INES sense of the term)
            $inesConfig = $this->getInesSyncConfig();
            if (isset($inesConfig['LeadRef']) && $inesConfig['LeadRef'] >= 0) {
                $this->addLeadToInesContact(
                    $internalContactRef,
                    $internalCompanyRef,
                    $datas['client']['Contacts']['ContactInfoAuto'][0]['PrimaryMailAddress'],
                    $inesConfig['LeadRef'],
                    $addLeadDescription
                );
            }
        //// UPDATE
        } else {

            // Before any update, we retrieve the existing data from INES
            $clientWithContact = array(
                'contact' => $this->getContactFromInes($internalContactRef),
                'client' => $this->getClientFromInes($internalCompanyRef)
            );

            // If the contact or the customer no longer exist at INES, it is because they have been deleted
            // It is therefore necessary to erase locally the known INES references and sync again this lead
            if ($clientWithContact['contact'] === false || $clientWithContact['client'] === false) {
                $lead = $this->integration->setInesKeysToLead($lead, 0, 0);
                return $this->syncLeadToInes($lead);
            }

            // Update the contact, if necessary, and then the client, if necessary
            foreach($clientWithContact as $concept => $conceptDatas) {

                $updateNeeded = false;
                foreach($mappedDatas[$concept]['standardFields'] as $inesFieldKey => $fieldValue) {

                    if ( !isset($conceptDatas[$inesFieldKey])) {
                        continue;
                    }

                    $currentFieldValue = $conceptDatas[$inesFieldKey];

                    $isProtectedField = in_array($inesFieldKey, $inesProtectedFields[$concept]);

                    // A field is updated if it has changed, and provided it is not non-erasable, unless it is empty at INES
                    if ($currentFieldValue != $fieldValue &&
                        ( !$isProtectedField || empty($currentFieldValue))
                    ){
                        $conceptDatas[$inesFieldKey] = $fieldValue;
                        $updateNeeded = true;
                    }
                }

                // Call WS only if necessary
                if ($updateNeeded) {

                    $wsDatas = array($concept => $conceptDatas);

                    // Update client
                    if ($concept == 'client') {

                        $wsDatas['client']['ModifiedDate'] = date("Y-m-d\TH:i:s");

                        $response = $this->request('ws/wsicm.asmx', 'UpdateClient', $wsDatas, true, true);

                        if ( !isset($response['UpdateClientResult']['InternalRef'])) {
                            return false;
                        }
                    }
                    // Update contact
                    else {
                        $wsDatas['contact']['ModificationDate'] = date("Y-m-d\TH:i:s");
                        $wsDatas['contact']['MauticRef'] = $leadId;
                        $wsDatas['contact']['Scoring'] = $leadPoints;
                        $wsDatas['contact']['Desabo'] = $leadDesaboFlag;
                        $wsDatas['contact']['IsNew'] = false;

                        // Filtering fields: only those requested by the WS
                        $contactDatas = $this->getContactTemplate();
                        foreach($contactDatas as $key => $value) {
                            if (isset($wsDatas['contact'][$key])) {
                                $contactDatas[$key] = $wsDatas['contact'][$key];
                            }
                        }
                        $wsDatas['contact'] = $contactDatas;

                        $response = $this->request('ws/wsAutomationsync.asmx', 'UpdateContact', $wsDatas, true, true);

                        if ( !isset($response['UpdateContactResult']) || $response['UpdateContactResult'] != $wsDatas['contact']['InternalRef']) {
                            return false;
                        }
                    }
                }
            }

            // If "push lead to integration" action, add a weblead to INES
            if ($syncTriggeredFromPushLead) {
                $inesConfig = $this->getInesSyncConfig();
                if (isset($inesConfig['LeadRef']) && $inesConfig['LeadRef'] >= 0) {
                    $this->addLeadToInesContact(
                        $internalContactRef,
                        $internalCompanyRef,
                        $clientWithContact['contact']['PrimaryMailAddress'],
                        $inesConfig['LeadRef'],
                        $addLeadDescription
                    );
                }
            }
        }

        // Processing of INES custom fields, if any
        $concepts = array('contact', 'client');
        foreach($concepts as $concept) {

            if (empty($mappedDatas[$concept]['customFields'])) {
                continue;
            }

            $inesRef = ($concept == 'contact') ? $internalContactRef : $internalCompanyRef;
            $inesRefKey = ($concept == 'contact') ? 'ctRef' : 'clRef';
            $wsConcept = ($concept == 'contact') ? 'Contact' : 'Company';

            // Read, through WS, the current fields of INES
            $currentCustomFields = $this->getCurrentCustomFields($concept, $inesRef);

            // Mautic fields to be updated
            foreach($mappedDatas[$concept]['customFields'] as $inesFieldKey => $fieldValue) {

                $datas = array(
                    $inesRefKey => $inesRef,
                    'chdefRef' => $inesFieldKey,
                    'chpValue' => $fieldValue
                );

                $ws = false;

                // If the field does not yet exist in INES: INSERT
                if ( !isset($currentCustomFields[$inesFieldKey])) {
                    $ws = 'Insert'.$wsConcept.'CF';
                    $datas['chvLies'] = 0;
                    $datas['chvGroupeAssoc'] = 0;
                }
                // If the field already exists and the value has changed: UPDATE
                else if ($currentCustomFields[$inesFieldKey]['chpValue'] != $fieldValue) {

                    $isProtectedField = in_array($inesFieldKey, $inesProtectedFields[$concept]);
                    if ( !$isProtectedField) {
                        $ws = 'Update'.$wsConcept.'CF';

                        // Updating a field refers to its INES identifier, previously read
                        $datas['chpRef'] = $currentCustomFields[$inesFieldKey]['chpRef'];
                    }
                }

                if ($ws) {
                    // WS call to create or update a custom field
                    $response = $this->request('ws/wscf.asmx', $ws, $datas, true, true);
                    if ( !isset($response[$ws.'Result'])) {
                        return false;
                    }
                }
            }
        }

        return true;
    }


    /**
     * Write to INES a new weblead relative to a contact
     *
     * @param int    $internalContactRef
     * @param int    $internalCompanyRef
     * @param string $email
     * @param int    $LeadRef Lead channel, defined in the INES config
     *
     * @return bool
     */
    public function addLeadToInesContact($internalContactRef, $internalCompanyRef, $email, $leadRef, $description)
    {
        try {
            $response = $this->request('ws/wsAutomationsync.asmx', 'AddLead', array(
                'info' => array(
                    'ClRef' => $internalCompanyRef,
                    'CtRef' => $internalContactRef,
                    'MailExpe' => $email,
                    'DescriptionCourte' => $description,
                    'ReclaDescDetail' => '',
                    'FileRef' => $leadRef,
                    'CriticiteRef' => 0,
                    'TypeRef' => 0,
                    'EtatRef' => 0,
                    'OrigineRef' => 0,
                    'DossierRef' => 0,
                    'CampagneRef' => 0,
                    'ArticleRef' => 0,
                    'ReclaMere' => 0,
                    'Propietaire' => 0,
                    'Gestionnaire' => 0
                )
            ), true, true);

            return isset($response['AddLeadResult']);
        } catch (\Exception $e) {
            $this->integration->logIntegrationError($e);
            return false;
        }
    }


    /**
     * Removes a contact from INES (set a flag "no longer synchronize")
     *
     * @param int $inesRef INES contact reference
     *
     * @return bool
     */
    public function deleteContact($inesRef)
    {
        $response = $this->request('ws/wsAutomationsync.asmx', 'DeleteMauticContact', array(
            'InesRef' => $inesRef
        ), true, true);

        return (isset($response['DeleteMauticContactResult']) && $response['DeleteMauticContactResult'] == 'Success');
    }


    /**
     * Search a contact at INES, from its ID
     *
     * @param int $internalContactRef
     *
     * @return array | false
     */
    public function getContactFromInes($internalContactRef)
    {
        $response = $this->request('ws/wsicm.asmx', 'GetContact', array(
            'reference' => $internalContactRef
        ), true, true);

        if (isset($response['GetContactResult']['InternalRef']) &&
            $response['GetContactResult']['InternalRef'] == $internalContactRef
        ){
            return $response['GetContactResult'];
        }

        return false;
    }


    /**
     * Search a company at INES, from its ID
     *
     * @param int $internalCompanyRef
     *
     * @return array | false
     */
    public function getClientFromInes($internalCompanyRef)
    {
        $response = $this->request('ws/wsicm.asmx', 'GetClient', array(
            'reference' => $internalCompanyRef
        ), true, true);

        if (isset($response['GetClientResult']['InternalRef']) &&
            $response['GetClientResult']['InternalRef'] == $internalCompanyRef
        ){
            return $response['GetClientResult'];
        }

        return false;
    }


    /**
     * Searches, from field mapping, Mautic fields that correspond to a list of INES fields
     *
     * @param array $inesFieldsKeys
     *
     * @return array List of Mautic fields identifiers founded
     */
    public function getMauticFieldsKeysFromInesFieldsKeys($inesFieldsKeys)
    {
        $mauticFields = array();
        $mapping = $this->integration->getMapping();

        foreach($mapping as $mappingItem) {

            $inesFieldKey = $mappingItem['inesFieldKey'];

            if (in_array($inesFieldKey, $inesFieldsKeys)) {
                $mauticFields[$inesFieldKey] = $mappingItem['mauticFieldKey'];
            }
        }
        return $mauticFields;
    }


    /**
     * Returns custom fields already present at INES for a contact or a client / company
     *
     * @param array $concept contact | client alias company
     * @param array $inesRef contactID or clientID
     *
     * @return     array | false
     */
    public function getCurrentCustomFields($concept, $inesRef)
    {
        $concept = ucfirst($concept);
        if ($concept == 'Client') {
            $concept = 'Company';
        }

        // Call WS: Read Fields
        $response = $this->request('ws/wscf.asmx', 'Get'.$concept.'CF', array('reference' => $inesRef), true, true);

        if ( !isset($response['Get'.$concept.'CFResult']['Values'])) {
            return false;
        }

        $customFields = array();
        $values = $response['Get'.$concept.'CFResult']['Values'];
        if ( !empty($values)) {
            foreach($values as $value_item) {

                // If several values exist for a single field, we are interested in the last element only
                if ( !isset($value_item['DefinitionRef'])) {
                    $value_item = end($value_item);
                }

                $chdefRef = $value_item['DefinitionRef'];

                $customFields[$chdefRef] = array(
                    'chpRef' => $value_item['Ref'],
                    'chpValue' => $value_item['Value']
                );
            }
        }

        return $customFields;
    }


    /**
     * Returns a session ID required for INES web-services calls
     * From cache if exists
     * Otherwise request one from INES (via the WS)
     *
     * @return    int (session ID)
     * @throws     ApiErrorException
     */
    protected function getSessionID()
    {
        // If a session already exists in cache, it is used
        $sessionID = $this->integration->getWebServiceCurrentSessionID();

        // Otherwise we ask for one
        if ( !$sessionID) {
            $this->integration->log('Refresh session ID');

            $args = array(
                'request' => $this->integration->getDecryptedApiKeys()
            );

            $response = $this->request('wslogin/login.asmx', 'authenticationWs', $args, false);

            if (
                is_object($response) &&
                isset($response->authenticationWsResult->codeReturn) &&
                $response->authenticationWsResult->codeReturn == 'ok'
            ){
                $sessionID = $response->authenticationWsResult->idSession;

                // And it is saved for later
                $this->integration->setWebServiceCurrentSessionID($sessionID);
            }
            else {
                throw new ApiErrorException("INES WS : Can't get session ID");
            }
        }

        return $sessionID;
    }


    /**
     * Read the sync configuration defined in the INES CRM: custom fields to map, lead channel to use, type of company to use
     */
    protected function getInesSyncConfig()
    {
        $syncConfig = $this->integration->getCurrentSyncConfig();
        if ( !$syncConfig) {

            $this->integration->log('Refresh sync config');

            // WS call
            $response = $this->request('Ws/WSAutomationSync.asmx', 'GetSyncInfo', array(), true);
            $results = isset($response->GetSyncInfoResult) ? $response->GetSyncInfoResult : false;
            if ($results === false) {
                throw new ApiErrorException("INES WS : Can't get sync config");
            }

            $companyCustomFields = json_decode(json_encode($results->CompanyCustomFields), true);
            $companyCustomFields = isset($companyCustomFields['CustomFieldToAuto']) ? $companyCustomFields['CustomFieldToAuto'] : [];

            $contactCustomFields = json_decode(json_encode($results->ContactCustomFields), true);
            $contactCustomFields = isset($contactCustomFields['CustomFieldToAuto']) ? $contactCustomFields['CustomFieldToAuto'] : [];

            $syncConfig = array(
                'LeadRef' => isset($results->LeadRef) ? $results->LeadRef : 0,
                'SocieteType' => isset($results->SocieteType) ? $results->SocieteType : 0,
                'CompanyCustomFields' => $companyCustomFields,
                'ContactCustomFields' => $contactCustomFields,
                'ValuesFromWS' => []
            );


            ///////// Reading all keys / values via the WS

            // All keys / values for "TYPE CONTACT"
            $response = $this->request('ws/wsicm.asmx', 'GetTypeContactList', array(), true, true);
            if (!isset($response['GetTypeContactListResult']['ContactTypeInfo'])) {
                throw new ApiErrorException("INES WS GetTypeContactList failed.");
            }
            $items = $response['GetTypeContactListResult']['ContactTypeInfo'];
            $values = [];
            // Sort by Order then Description
            usort($items, function ($a, $b) {
                $isAsupB = ($a['Order'] == $b['Order']) ? ($a['Description'] > $b['Description']) : ($a['Order'] > $b['Order']);
                return $isAsupB ? 1 : -1;
            });
            foreach($items as $item) {
                $values[ $item['InternalRef'] ] = $item['Description'];
            }
            $syncConfig['ValuesFromWS']['GetTypeContactList'] = $values;

            // All keys / values for "TYPE CLIENT"
            $response = $this->request('ws/wsicm.asmx', 'GetTypeClientList', array(), true, true);
            if (!isset($response['GetTypeClientListResult']['ClientTypeInfo'])) {
                throw new ApiErrorException("INES WS GetTypeClientList failed.");
            }
            $items = $response['GetTypeClientListResult']['ClientTypeInfo'];
            // Sort by Order then Description
            usort($items, function ($a, $b) {
                $isAsupB = ($a['Order'] == $b['Order']) ? ($a['Description'] > $b['Description']) : ($a['Order'] > $b['Order']);
                return $isAsupB ? 1 : -1;
            });
            $values = [];
            foreach($items as $item) {
                $values[ $item['InternalRef'] ] = $item['Description'];
            }
            $syncConfig['ValuesFromWS']['GetTypeClientList'] = $values;

            // All keys / values for "ORIGIN"
            $response = $this->request('ws/wsicm.asmx', 'GetOriginList', array(), true, true);
            if (!isset($response['GetOriginListResult']['OriginInfo'])) {
                throw new ApiErrorException("INES WS GetOriginList failed.");
            }
            $items = $response['GetOriginListResult']['OriginInfo'];
            $values = [];
            foreach($items as $item) {
                $values[ $item['InternalRef'] ] = $item['Description'];
            }
            $syncConfig['ValuesFromWS']['GetOriginList'] = $values;

            // All keys / values for "USER REF"
            $response = $this->request('ws/wsicm.asmx', 'GetUserInfoFromUserRef', array(), true, true);
            if (!isset($response['GetUserInfoFromUserRefResult']['UserInfoRH'])) {
                throw new ApiErrorException("INES WS GetUserInfoFromUserRef failed.");
            }
            $items = $response['GetUserInfoFromUserRefResult']['UserInfoRH'];
            $values = [];
            foreach($items as $item) {
                $values[ $item['UserRef'] ] = $item['LastName'].' '.$item['FirstName'];
            }
            asort($values);
            $syncConfig['ValuesFromWS']['GetUserInfoFromUserRef'] = $values;

            // All keys / values for "RH REF"
            $response = $this->request('ws/wsicm.asmx', 'GetUserInfoFromRHRef', array(), true, true);
            if (!isset($response['GetUserInfoFromRHRefResult']['UserInfoRH'])) {
                throw new ApiErrorException("INES WS GetUserInfoFromRHRef failed.");
            }
            $items = $response['GetUserInfoFromRHRefResult']['UserInfoRH'];
            $values = [];
            foreach($items as $item) {
                $values[ $item['RHRef'] ] = $item['LastName'].' '.$item['FirstName'];
            }
            asort($values);
            $syncConfig['ValuesFromWS']['GetUserInfoFromRHRef'] = $values;

            // Save for later
            $this->integration->setCurrentSyncConfig($syncConfig);

            // Whenever the config is regenerated, we check that the custom fields are up-to-date in Mautic
            $this->integration->updateMauticCustomFieldsDefinitions();
        }

        return $syncConfig;
    }



    /**
     * Request to an INES web-service
     *
     * @param string  $ws_relative_url Example : wslogin/login.asmx
     * @param string  $method          SOAP method. Example : 'authenticationWs'
     * @param array   $args
     * @param bool    $auth_needed     true if a session ID is required
     * @param bool    $return_as_array true to convert output from object to array
     *
     * @return Object
     *
     * @throws Exception
     */
    protected function request($ws_relative_url, $method, $args, $auth_needed = true, $return_as_array = false)
    {
        $client_url = ($auth_needed ? 'https' : 'http') . '://webservices.inescrm.com/';
        $client_url .= ltrim($ws_relative_url, '/') . '?wsdl';

        // SOAP header with session ID, if auth needed
        if ($auth_needed) {

            $sessionID = $this->getSessionID();

            $settings = array(
                'soapHeader' => array(
                    'namespace' => 'http://webservice.ines.fr',
                    'name' => 'SessionID',
                    'datas' => array('ID' => $sessionID)
                )
            );
        }
        else {
            $settings = false;
        }

        try {
            $response = $this->integration->makeRequest($client_url, $args, $method, $settings);
        } catch (\Exception $e) {

            // If a request that requires a sessionID fails, the session may have expired
            // So we try to refresh this ID before a second try
            if ($auth_needed) {
                try {
                    $this->refreshSessionID();
                    $response = $this->integration->makeRequest($client_url, $args, $method, $settings);
                } catch (\Exception $e) {
                    throw $e;
                }
            }
            else {
                throw $e;
            }
        }

        if ($return_as_array) {
            $response = json_decode(json_encode($response), true);
        }

        return $response;
    }


    /**
     * Returns the minimum parameters to request an INES WS using the concept of client (= ines company)
     *
     * @return     Array     List of fields
     */
    protected function getClientTemplate()
    {
        return array(
            'Confidentiality' => 'Undefined',
            'CompanyName' => '',
            'Type' => 0, /* filled from INES config : company type */
            'Service' => '',
            'Address1' => '',
            'Address2' => '',
            'ZipCode' => '',
            'City' => '',
            'State' => '',
            'Country' => '',
            'Phone' => '',
            'Fax' => '',
            'Website' => '',
            'Comments' => '',
            'Manager' => 0,
            'SalesResponsable' => 0,
            'TechnicalResponsable' => 0,
            'CreationDate' => date("Y-m-d\TH:i:s"),
            'ModifiedDate' => date("Y-m-d\TH:i:s"),
            'Origin' => 0,
            'CustomerNumber' => 0,
            'CompanyTaxCode' => '',
            'VatTax' => 0,
            'Bank' => '',
            'BankAccount' => '',
            'PaymentMethod' => '',
            'PaymentMethodRef' => 1, /* MANDATORY AND NOT NULL, OTHERWISE ERROR */
            'Discount' => 0,
            'HeadQuarter' => 0,
            'Language' => '',
            'Activity' => '',
            'AccountingCode' => '',
            'Scoring' => '',
            'Remainder' => 0,
            'MaxRemainder' => 0,
            'Moral' => 0,
            'Folder' => 0,
            'Currency' => '',
            'BankReference' => 0,
            'TaxType' => 0,
            'VatTaxValue' => 0,
            'Creator' => 0,
            'Delivery' => 0,
            'Billing' => 0,
            'IsNew' => true,
            'MauticRef' => 0, /* don't fill because Mautic company concept isn't managed by the plugin */
            'InternalRef' => 0
        );
    }


    /**
     * Returns the minimal parameters to request a WS INES using the concept of contact
     *
     * @return     Array     List of fields
     */
    protected function getContactTemplate()
    {
        return array(
            'Author' => 0,
            'BusinessAddress' => '',
            'BussinesTelephone' => '',
            'City' => '',
            'Comment' => "",
            'CompanyRef' => 0,
            'Confidentiality' => 'Undefined',
            'Country' => '',
            'CreationDate' => date("Y-m-d\TH:i:s"),
            'DateOfBirth' => date("Y-m-d\TH:i:s"),
            'Fax' => '',
            'FirstName' => '',
            'Function' => '',
            'Genre' => '',
            'HomeAddress' => '',
            'HomeTelephone' => '',
            'IsNew' => true,
            'Language' => '',
            'LastName' => '',
            'MobilePhone' => '',
            'ModificationDate' => date("Y-m-d\TH:i:s"),
            'PrimaryMailAddress' => '',
            'Rang' => 'Principal',
            'SecondaryMailAddress' => '',
            'Service' => '',
            'Type' => 0,
            'State' => '',
            'ZipCode' => '',
            'Desabo' => '',
            'NPai' => '',
            'InternalRef' => 0,
            'MauticRef' => 0,
            'Scoring' => 0
        );
    }


    /**
     * Returns the minimum parameters to request an INES WS using the concept of "client with contacts"
     *
     * @param int $nbContacts
     *
     * @return array List of fields
     */
    protected function getClientWithContactsTemplate($nbContacts = 1)
    {
        // client template
        $datas = array(
            'client' => $this->getClientTemplate()
        );

        $datas['client']['Contacts'] = array(
            'ContactInfoAuto' => array()
        );

        $contactTemplate = $this->getContactTemplate();
        for($i=0; $i<$nbContacts; $i++) {
            array_push(
                $datas['client']['Contacts']['ContactInfoAuto'],
                $contactTemplate
            );
        }

        return $datas;
    }

}
