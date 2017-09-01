<?php

namespace MauticPlugin\MauticCrmBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\Company;

class InesCRMIntegration extends CrmAbstractIntegration
{
    private $defaultContactFields;

    private $defaultCompanyFields;

    public function __construct(MauticFactory $factory = null) {
        parent::__construct($factory);

        $defaultFields = json_decode(self::INES_DEFAULT_FIELDS_JSON);
        $defaultContactFields = [];
        $defaultCompanyFields = [];

        foreach ($defaultFields as $f) {
            $fieldValue = [
                'label' => $f->inesLabel,
                'required' => $f->isMappingRequired,
            ];

            if ($f->concept === 'contact') {
                $defaultContactFields[$f->inesKey] = $fieldValue;
            } else if ($f->concept === 'client') {
                $defaultCompanyFields[$f->inesKey] = $fieldValue;
            }
        }

        $this->defaultContactFields = $defaultContactFields;
        $this->defaultCompanyFields = $defaultCompanyFields;
    }

    public function getName()
    {
        return 'InesCRM';
    }

    public function getDisplayName()
    {
        return 'Ines CRM';
    }

    public function getRequiredKeyFields()
    {
        return [
            'compte'   => 'mautic.ines_crm.form.account',
            'userName' => 'mautic.ines_crm.form.username',
            'password' => 'mautic.ines_crm.form.password',
        ];
    }

    public function getSecretKeys()
    {
        return [
            'password',
        ];
    }

    public function getSupportedFeatures()
    {
        return ['push_lead', 'push_leads'];
    }

    public function pushLead($lead, $config = []) {
        $config = $this->mergeConfigToFeatureSettings($config);

        $companyFields = $config['companyFields'];
        $leadFields = $config['leadFields'];

        $apiHelper = $this->getApiHelper();

        $companyRepo = $this->em->getRepository(Company::class);
        $leadRepo = $this->em->getRepository(Lead::class);

        $company = null;
        $companies = $companyRepo->getCompaniesByLeadId($lead->getId());

        foreach ($companies as $c) {
            if ($c['is_primary']) {
                $company = $companyRepo->getEntity($c['id']);
                break;
            }
        }

        if ($company === null) {
            $this->logger->debug('INES: Will not push contact without company', compact('lead', 'config'));
            return;
        }

        $companyInternalRefGetter = 'get' . ucfirst($companyFields['InternalRef']);
        $leadInternalRefGetter = 'get' . ucfirst($leadFields['InternalRef']);

        $companyInternalRefSetter = 'set' . ucfirst($companyFields['InternalRef']);
        $leadInternalRefSetter = 'set' . ucfirst($leadFields['InternalRef']);

        $inesClientRef = $company->$companyInternalRefGetter();
        $inesContactRef = $lead->$leadInternalRefGetter();

        if (!$inesClientRef) {
            if (!$inesContactRef) {
                $this->logger->debug('INES: Will create Client and Contact', compact('lead', 'company', 'config'));

                $mappedData = $this->getClientWithContactsTemplate();

                $mappedData->client->AutomationRef = $company->getId();
                $mappedData->client->Contacts->ContactInfoAuto[0]->AutomationRef = $lead->getId();

                $this->mapCompanyToInesClient($config, $company, $mappedData->client);
                $this->mapLeadToInesContact($config, $lead, $mappedData->client->Contacts->ContactInfoAuto[0]);

                $mappedData->client->InternalRef = 0;
                $mappedData->client->Contacts->ContactInfoAuto[0]->InternalRef = 0;

                $response = $apiHelper->createClientWithContacts($mappedData);
                $result = $response->AddClientWithContactsResult;

                $inesClientRef = $result->InternalRef;
                $inesContactRef = $result->Contacts->ContactInfoAuto->InternalRef;

                $company->$companyInternalRefSetter($inesClientRef);
                $lead->$leadInternalRefSetter($inesContactRef);

                $companyRepo->saveEntity($company);
                $leadRepo->saveEntity($lead);
            } else {
                $this->logger->debug('INES: Will create Client and update Contact', compact('lead', 'company', 'config'));

                $mappedData = (object) [
                    'client' => $this->getClientTemplate(),
                ];

                $this->mapCompanyToInesClient($config, $company, $mappedData->client);
                $mappedData->client->InternalRef = 0;

                $response = $apiHelper->createClient($mappedData);
                $inesClientRef = $response->AddClientResult->InternalRef;

                $company->$companyInternalRefSetter($inesClientRef);
                $companyRepo->saveEntity($company);

                $inesContact = $apiHelper->getContact($inesContactRef)->GetContactResult;

                // TODO: Figure out how to transfer a contact from a client to another
                $shouldUpdateContact = $this->mapLeadUpdatesToInesContact($config, $lead, $inesContact);

                if ($shouldUpdateContact) {
                    $response = $apiHelper->updateContact($inesContact);
                }
            }
        } else {
            if (!$inesContactRef) {
                $this->logger->debug('INES: Will update Client and create Contact', compact('lead', 'company', 'config'));

                $inesClient = $apiHelper->getClient($inesClientRef)->GetClientResult;

                $shouldUpdateClient = $this->mapCompanyUpdatesToInesClient($config, $company, $inesClient);

                $mappedData = (object) [
                    'contact' => $this->getContactTemplate(),
                    'AutomationRef' => $lead->getId(),
                    'clientRef' => $inesClientRef,
                    'scoring' => $lead->getPoints(),
                    // TODO: add unsubscribe status
                ];

                $this->mapLeadToInesContact($config, $lead, $mappedData->contact);

                $mappedData->contact->InternalRef = 0;

                $response = $apiHelper->createContact($mappedData);
                $inesContactRef = $response->AddContactResult->InternalRef;

                $lead->$leadInternalRefSetter($inesContactRef);
                $leadRepo->saveEntity($lead);
            } else {
                $this->logger->debug('INES: Will update Client and Contact', compact('lead', 'company', 'config'));

                $inesClient = $apiHelper->getClient($inesClientRef)->GetClientResult;
                $inesContact = $apiHelper->getContact($inesContactRef)->GetContactResult;

                $shouldUpdateClient = $this->mapCompanyUpdatesToInesClient($config, $company, $inesClient);
                $shouldUpdateContact = $this->mapLeadUpdatesToInesContact($config, $lead, $inesContact);

                if ($shouldUpdateClient) {
                    $apiHelper->updateClient($inesClient);
                }

                if ($shouldUpdateContact) {
                    $apiHelper->updateContact($inesContact);
                }
            }
        }
    }

    public function pushCompany($company, $config = []) {
        $config = $this->mergeConfigToFeatureSettings($config);

        $companyFields = [];
        foreach ($config['companyFields'] as $k => $v) {
            $companyFields[$k] = mb_substr($v, 7);
        }

        $mappedData = [];

        foreach ($companyFields as $integrationField => $mauticField) {
            $method = 'get' . ucfirst($mauticField);
            $mappedData[$integrationField] = $company->$method();
        }

        $this->getApiHelper()->createCompany($mappedData);
    }

    public function pushLeads($params = []) {
        $config                  = $this->mergeConfigToFeatureSettings();
        list($fromDate, $toDate) = $this->getSyncTimeframeDates($params);
        $fetchAll                = $params['fetchAll'];
        $limit                   = $params['limit'];

        if (in_array('lead', $config['objects'])) {
            $leadRepo = $this->em->getRepository(Lead::class);
            $qb = $leadRepo->createQueryBuilder('l');
            $qb->where('l.email is not null')->andWhere('l.email != \'\'');

            if (!$fetchAll) {
                $qb->andWhere('l.dateAdded >= :fromDate')
                   ->andWhere('l.dateAdded <= :toDate')
                   ->setParameters(compact('fromDate', 'toDate'));
           }

            if ($limit) {
                $qb->setMaxResults($limit);
            }

            $iterableLeads = $qb->getQuery()->iterate();

            foreach($iterableLeads as $lead) {
                $this->pushLead($lead[0], $config);
            }
        }

        if (in_array('company', $config['objects'])) {
            $companyRepo = $this->em->getRepository(Company::class);
            $qb = $companyRepo->createQueryBuilder('c');

            if (!$fetchAll) {
                $qb->andWhere('c.dateAdded >= :fromDate')
                   ->andWhere('c.dateAdded <= :toDate')
                   ->setParameters(compact('fromDate', 'toDate'));
           }

            if ($limit) {
                $qb->setMaxResults($limit);
            }

            $iterableLeads = $qb->getQuery()->iterate();

            foreach($iterableLeads as $company) {
                $this->pushCompany($company[0], $config);
            }
        }
    }

    public function getDataPriority()
    {
        return true;
    }

    public function getFormLeadFields($settings = []) {
        $leadFields = $this->defaultContactFields;

        $customFields = $this->getApiHelper()->getCustomFields()
                             ->GetSyncInfoResult
                             ->ContactCustomFields
                             ->CustomFieldToAuto;

        foreach ($customFields as $f) {
            $leadFields['ines_custom_' . $f->InesID] = [
                'label' => $f->InesName,
                'required' => false,
            ];
        }

        return $leadFields;
    }

    public function getFormCompanyFields($settings = []) {
        $companyFields = $this->defaultCompanyFields;

        $customFields = $this->getApiHelper()->getCustomFields()
                             ->GetSyncInfoResult
                             ->CompanyCustomFields
                             ->CustomFieldToAuto;

        foreach ($customFields as $f) {
            $companyFields['ines_custom_' . $f->InesID] = [
                'label' => $f->InesName,
                'required' => false,
            ];
        }

        return $companyFields;
    }

    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'features') {
            $builder->add('objects', 'choice', [
                'choices' => [
                    'lead'    => 'mautic.ines_crm.object.lead',
                    'company' => 'mautic.ines_crm.object.company',
                ],
                'expanded'    => true,
                'multiple'    => true,
                'label'       => 'mautic.ines_crm.form.objects_to_push_to',
                'label_attr'  => ['class' => ''],
                'empty_value' => false,
                'required'    => false,
            ]);
        }
    }

    private function mapFieldsFromMauticToInes($fields, $mauticObject, $inesObject) {
        foreach ($fields as $inesField => $mauticField) {
            if (substr($inesField, 0, 12) !== 'ines_custom_') { // FIXME: There's probably a better way to do this...
                $method = 'get' . ucfirst($mauticField);
                $inesObject->$inesField = $mauticObject->$method($mauticField);
            }
        }
    }

    private function mapFieldUpdatesFromMauticToInes($fields, $mauticObject, $inesObject) {
        $shouldUpdate = false;

        foreach ($fields as $inesField => $mauticField) {
            if (substr($inesField, 0, 12) !== 'ines_custom_') { // FIXME: There's probably a better way to do this...
                $method = 'get' . ucfirst($mauticField);
                if ((string) $inesObject->$inesField !== (string) $mauticObject->$method($mauticField)) {
                    $shouldUpdate = true;
                    $inesObject->$inesField = $mauticObject->$method($mauticField);
                }
            }
        }

        return $shouldUpdate;
    }

    private function mapCompanyToInesClient($config, $company, $inesClient) {
        $companyFields = $config['companyFields'];

        $this->mapFieldsFromMauticToInes($companyFields, $company, $inesClient);
    }

    private function mapLeadToInesContact($config, $lead, $inesContact) {
        $leadFields = $config['leadFields'];

        $this->mapFieldsFromMauticToInes($leadFields, $lead, $inesContact);
    }

    private function mapCompanyUpdatesToInesClient($config, $company, $inesClient) {
        $companyFields = $config['companyFields'];

        return $this->mapFieldUpdatesFromMauticToInes($companyFields, $company, $inesClient);
    }

    private function mapLeadUpdatesToInesContact($config, $lead, $inesContact) {
        $leadFields = $config['leadFields'];

        return $this->mapFieldUpdatesFromMauticToInes($leadFields, $lead, $inesContact);
    }

    private function getClientTemplate()
    {
        return (object) [
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
            'CreationDate' => date('Y-m-d\TH:i:s'),
            'ModifiedDate' => date('Y-m-d\TH:i:s'),
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
            'AutomationRef' => 0, /* don't fill because Mautic company concept isn't managed by the plugin */
            'InternalRef' => 0
        ];
    }

    private function getContactTemplate()
    {
        return (object) [
            'Author' => 0,
            'BusinessAddress' => '',
            'BussinesTelephone' => '',
            'City' => '',
            'Comment' => "",
            'CompanyRef' => 0,
            'Confidentiality' => 'Undefined',
            'Country' => '',
            'CreationDate' => date('Y-m-d\TH:i:s'),
            'DateOfBirth' => date('Y-m-d\TH:i:s'),
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
            'AutomationRef' => 0,
            'Scoring' => 0
        ];
    }

    private function getClientWithContactsTemplate($nbContacts = 1)
    {
        $data = (object) [
            'client' => $this->getClientTemplate()
        ];

        $data->client->Contacts->ContactInfoAuto = [];

        for($i = 0; $i < $nbContacts; $i += 1) {
            $data->client->Contacts->ContactInfoAuto[] = $this->getContactTemplate();
        }

        return $data;
    }

    const INES_DEFAULT_FIELDS_JSON = <<<'JSON'
        [
            {
                "concept": "contact",
                "inesKey": "InternalRef",
                "inesLabel": "INES reference (contact)",
                "isCustomField": false,
                "isMappingRequired": true,
                "autoMapping": "ines_contact_ref",
                "excludeFromEcrasableConfig": true,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_ref",
                    "type": "number"
                }
            },
            {
                "concept": "client",
                "inesKey": "InternalRef",
                "inesLabel": "INES reference (soci\u00e9t\u00e9)",
                "isCustomField": false,
                "isMappingRequired": true,
                "autoMapping": "ines_client_ref",
                "excludeFromEcrasableConfig": true,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_ref",
                    "type": "number"
                }
            },
            {
                "concept": "contact",
                "inesKey": "PrimaryMailAddress",
                "inesLabel": "Primary Email Address",
                "isCustomField": false,
                "isMappingRequired": true,
                "autoMapping": false,
                "excludeFromEcrasableConfig": true,
                "mauticCustomFieldToCreate": false
            },
            {
                "concept": "contact",
                "inesKey": "Genre",
                "inesLabel": "Genre (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_civilite"
                }
            },
            {
                "concept": "contact",
                "inesKey": "LastName",
                "inesLabel": "Last name (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": "lastname",
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": false
            },
            {
                "concept": "contact",
                "inesKey": "FirstName",
                "inesLabel": "First name (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": "firstname",
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": false
            },
            {
                "concept": "contact",
                "inesKey": "Function",
                "inesLabel": "Function (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_fonction"
                }
            },
            {
                "concept": "contact",
                "inesKey": "Type",
                "inesLabel": "Type (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_type",
                    "type": "select",
                    "valuesFromWS": "GetTypeContactList",
                    "firstValueAsDefault": true
                }
            },
            {
                "concept": "contact",
                "inesKey": "Service",
                "inesLabel": "Service (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_service"
                }
            },
            {
                "concept": "contact",
                "inesKey": "BussinesTelephone",
                "inesLabel": "Business phone (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_tel_bureau",
                    "type": "tel"
                }
            },
            {
                "concept": "contact",
                "inesKey": "HomeTelephone",
                "inesLabel": "Home phone (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_tel_domicile",
                    "type": "tel"
                }
            },
            {
                "concept": "contact",
                "inesKey": "MobilePhone",
                "inesLabel": "Mobile mobile (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_tel_mobile",
                    "type": "tel"
                }
            },
            {
                "concept": "contact",
                "inesKey": "Fax",
                "inesLabel": "Fax (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_fax",
                    "type": "tel"
                }
            },
            {
                "concept": "contact",
                "inesKey": "HomeAddress",
                "inesLabel": "Address 1 (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_adr1"
                }
            },
            {
                "concept": "contact",
                "inesKey": "BusinessAddress",
                "inesLabel": "Address 2 (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_adr2"
                }
            },
            {
                "concept": "contact",
                "inesKey": "ZipCode",
                "inesLabel": "Zip code (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_cp"
                }
            },
            {
                "concept": "contact",
                "inesKey": "City",
                "inesLabel": "City (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_ville"
                }
            },
            {
                "concept": "contact",
                "inesKey": "State",
                "inesLabel": "State (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_region"
                }
            },
            {
                "concept": "contact",
                "inesKey": "Country",
                "inesLabel": "Country (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_pays"
                }
            },
            {
                "concept": "contact",
                "inesKey": "Language",
                "inesLabel": "Language (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_lang"
                }
            },
            {
                "concept": "contact",
                "inesKey": "Author",
                "inesLabel": "Author (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_resp",
                    "type": "select",
                    "valuesFromWS": "GetUserInfoFromUserRef"
                }
            },
            {
                "concept": "contact",
                "inesKey": "Comment",
                "inesLabel": "Comment (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_remarque"
                }
            },
            {
                "concept": "contact",
                "inesKey": "Confidentiality",
                "inesLabel": "Confidentiality (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_diffusion",
                    "type": "select",
                    "values": {
                        "-1": "lecture seule",
                        "0": "lecture \/ \u00e9criture",
                        "1": "confidentiel"
                    }
                }
            },
            {
                "concept": "contact",
                "inesKey": "DateOfBirth",
                "inesLabel": "Date of birth (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_birthday",
                    "type": "date"
                }
            },
            {
                "concept": "contact",
                "inesKey": "Rang",
                "inesLabel": "Rang (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_etat",
                    "type": "select",
                    "values": [
                        "secondaire",
                        "principal",
                        "archiv\u00e9"
                    ]
                }
            },
            {
                "concept": "contact",
                "inesKey": "SecondaryMailAddress",
                "inesLabel": "Secondary email (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_email2"
                }
            },
            {
                "concept": "contact",
                "inesKey": "NPai",
                "inesLabel": "NPAI (contact)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": "ines_contact_npai",
                "excludeFromEcrasableConfig": true,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_contact_npai",
                    "type": "boolean"
                }
            },
            {
                "concept": "client",
                "inesKey": "CompanyName",
                "inesLabel": "Company name",
                "isCustomField": false,
                "isMappingRequired": true,
                "autoMapping": "company",
                "excludeFromEcrasableConfig": true,
                "mauticCustomFieldToCreate": false
            },
            {
                "concept": "client",
                "inesKey": "Type",
                "inesLabel": "Type (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_type",
                    "type": "select",
                    "valuesFromWS": "GetTypeClientList",
                    "firstValueAsDefault": true
                }
            },
            {
                "concept": "client",
                "inesKey": "Manager",
                "inesLabel": "Manager (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_resp_dossier",
                    "type": "select",
                    "valuesFromWS": "GetUserInfoFromUserRef"
                }
            },
            {
                "concept": "client",
                "inesKey": "SalesResponsable",
                "inesLabel": "Sales responsable (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_commercial",
                    "type": "select",
                    "valuesFromWS": "GetUserInfoFromRHRef"
                }
            },
            {
                "concept": "client",
                "inesKey": "TechnicalResponsable",
                "inesLabel": "Technical responsable (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_resp_tech",
                    "type": "select",
                    "valuesFromWS": "GetUserInfoFromUserRef"
                }
            },
            {
                "concept": "client",
                "inesKey": "Phone",
                "inesLabel": "Phone (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_tel",
                    "type": "tel"
                }
            },
            {
                "concept": "client",
                "inesKey": "Fax",
                "inesLabel": "Fax (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_fax",
                    "type": "tel"
                }
            },
            {
                "concept": "client",
                "inesKey": "Address1",
                "inesLabel": "Address 1 (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_adr1"
                }
            },
            {
                "concept": "client",
                "inesKey": "Address2",
                "inesLabel": "Address 2 (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_adr2"
                }
            },
            {
                "concept": "client",
                "inesKey": "ZipCode",
                "inesLabel": "Zip code (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_cp"
                }
            },
            {
                "concept": "client",
                "inesKey": "City",
                "inesLabel": "City (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_ville"
                }
            },
            {
                "concept": "client",
                "inesKey": "State",
                "inesLabel": "State (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_region"
                }
            },
            {
                "concept": "client",
                "inesKey": "Country",
                "inesLabel": "Country (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_pays"
                }
            },
            {
                "concept": "client",
                "inesKey": "Origin",
                "inesLabel": "Origin (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_origine",
                    "type": "select",
                    "valuesFromWS": "GetOriginList"
                }
            },
            {
                "concept": "client",
                "inesKey": "Website",
                "inesLabel": "Website (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_site_web",
                    "type": "url"
                }
            },
            {
                "concept": "client",
                "inesKey": "Confidentiality",
                "inesLabel": "Confidentiality (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_diffusion",
                    "type": "select",
                    "values": {
                        "-1": "lecture seule",
                        "0": "lecture \/ \u00e9criture",
                        "1": "confidentiel"
                    }
                }
            },
            {
                "concept": "client",
                "inesKey": "Comments",
                "inesLabel": "Comments (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_remarque"
                }
            },
            {
                "concept": "client",
                "inesKey": "CustomerNumber",
                "inesLabel": "Customer number (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_num_client",
                    "type": "number"
                }
            },
            {
                "concept": "client",
                "inesKey": "Language",
                "inesLabel": "Language (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_lang"
                }
            },
            {
                "concept": "client",
                "inesKey": "Activity",
                "inesLabel": "Activity (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_activite"
                }
            },
            {
                "concept": "client",
                "inesKey": "Scoring",
                "inesLabel": "Scoring (company)",
                "isCustomField": false,
                "isMappingRequired": false,
                "autoMapping": false,
                "excludeFromEcrasableConfig": false,
                "mauticCustomFieldToCreate": {
                    "alias": "ines_client_score"
                }
            }
        ]
JSON;
}
