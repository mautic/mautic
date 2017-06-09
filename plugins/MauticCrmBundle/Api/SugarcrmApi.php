<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

class SugarcrmApi extends CrmApi
{
    private $module   = 'Leads';
    protected $object = 'Leads';

    /**
     * @param        $sMethod
     * @param array  $data
     * @param string $method
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function request($sMethod, $data = [], $method = 'GET', $object = null)
    {
        if (!$object) {
            $object = $this->object;
        }
        $tokenData = $this->integration->getKeys();

        if ($tokenData['version'] == '6') {
            $request_url = sprintf('%s/service/v4_1/rest.php', $tokenData['sugarcrm_url']);

            $sessionParams = [
                'session' => $tokenData['id'],
            ];
            if (!isset($data['module_names'])) {
                $sessionParams['module_name'] = $object;
            } //Making sure that module_name is the second value of the array
            else {
                $sessionParams['module_names'] = $data['module_names'];
            }

            $sessionParams = array_merge($sessionParams, $data);
            $parameters    = [
                'method'        => $sMethod,
                'input_type'    => 'JSON',
                'response_type' => 'JSON',
                'rest_data'     => json_encode($sessionParams),
            ];

            $response = $this->integration->makeRequest($request_url, $parameters, $method);

            if (is_array($response) && !empty($response['name']) && !empty($response['number'])) {
                throw new ApiErrorException($response['name'].' '.$object.' '.$sMethod.' '.$method);
            } else {
                return $response;
            }
        } else {
            $request_url = sprintf('%s/rest/v10/%s', $tokenData['sugarcrm_url'], $sMethod);
            $response    = $this->integration->makeRequest($request_url, $data, $method);

            if (isset($response['error'])) {
                throw new ApiErrorException($response['error_message'], ($response['error'] == 'invalid_grant') ? 1 : 500);
            }

            return $response;
        }
    }

    /**
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getLeadFields($object = null)
    {
        if (!$object) {
            $object = $this->object;
        }
        if ($object == 'company') {
            $object = 'Accounts'; //sugarCRM object name
        } elseif ($object == 'lead' || $object == 'Lead') {
            $object = 'Leads';
        } elseif ($object == 'contact' || $object == 'Contact') {
            $object = 'Contacts';
        }

        $tokenData = $this->integration->getKeys();

        if ($tokenData['version'] == '6') {
            $resp = $this->request('get_module_fields', [], 'GET', $object);

            return $resp;
        } else {
            $parameters = [
                'module_filter' => $object,
                'type_filter'   => 'modules',
            ];

            $response = $this->request('metadata', $parameters, 'GET', $object);

            return $response['modules'][$object];
        }
    }

    /**
     * @param array $fields
     *
     * @return array
     *
     * @throws \Mautic\PluginBundle\Exception\ApiErrorException
     */
    public function createLead(array $fields, $lead)
    {
        $tokenData       = $this->integration->getKeys();
        $createdLeadData = [];
        //search for Sugar id in mautic records first to avoid making an API call
        if (is_object($lead)) {
            $sugarLeadRecords = $this->integration->getSugarLeadId($lead);
        }
        if ($tokenData['version'] == '6') {

            //if not found then go ahead and make an API call to find all the records with that email
            if (isset($fields['email1']) && empty($sugarLeadRecords)) {
                $sLeads           = $this->getLeads(['email' => $fields['email1'], 'offset' => 0, 'max_results' => 1000], 'Leads');
                $sugarLeadRecords = $sLeads['entry_list'];
            }
            $leadFields = [];
            foreach ($fields as $name => $value) {
                if ($name != 'id') {
                    $leadFields[] = [
                        'name'  => $name,
                        'value' => $value,
                    ];
                }
            }
            $parameters = [
                'name_value_list' => $leadFields,
            ];

            if (!empty($sugarLeadRecords)) {
                foreach ($sugarLeadRecords as $sLeadRecord) {
                    $localParam  = $parameters;
                    $sugarLeadId = (isset($sLeadRecord['integration_entity_id']) ? $sLeadRecord['integration_entity_id'] : $sLeadRecord['id']);
                    $sugarObject = (isset($sLeadRecord['integration_entity']) ? $sLeadRecord['integration_entity'] : 'Leads');
                    //update the converted contact if found and not the Lead
                    if (isset($sLeadRecord['contact_id']) && $sLeadRecord['contact_id'] != null && $sLeadRecord['contact_id'] != '') {
                        unset($fields['Company']); //because this record is not in the Contact object
                        $localParams['name_value_list'][] = ['name' => 'id', 'value' => $sLeadRecord['contact_id']];
                        $createdLeadData[]                = $this->request('set_entry', $localParams, 'POST', 'Contacts');
                    } else {
                        $localParams['name_value_list'][] = ['name' => 'id', 'value' => $sugarLeadId];
                        $createdLeadData[]                = $this->request('set_entry', $localParams, 'POST', $sugarObject);
                    }
                }
            } else {
                $createdLeadData = $this->request('set_entry', $parameters, 'POST', 'Leads');
            }

            //$createdLeadData[] = $this->request('set_entry', $parameters, 'POST');
        } else {
            $createdLeadData[] = $this->request('set_entry', $fields, 'POST', 'Leads');
        }

        return $createdLeadData;
    }

    /**
     * @param array $leads
     *
     * @return array
     *
     * @throws \Mautic\PluginBundle\Exception\ApiErrorException
     */
    public function syncLeadsToSugar(array $data)
    {
        $tokenData = $this->integration->getKeys();
        $object    = $this->object;

        if ($tokenData['version'] == '6') {
            $leadFieldsList = [];
            $response       = [];

            foreach ($data as $object => $leadFieldsList) {
                $parameters = [
                    'name_value_lists' => $leadFieldsList,
                ];
                $resp = $this->request('set_entries', $parameters, 'POST', $object);
                if (!empty($resp)) {
                    foreach ($leadFieldsList as $k => $leadFields) {
                        $fields = [];
                        foreach ($leadFields as $item) {
                            $fields[$item['name']] = $item['value'];
                        }
                        $result = ['reference_id' => $fields['reference_id'],
                                   'id'           => $resp['ids'][$k],
                                   'new'          => !isset($fields['id']),
                                   'ko'           => false, ];
                        if (isset($fields['id']) && $fields['id'] != $resp['ids'][$k]) {
                            $result['ko']    = true;
                            $result['error'] = 'Returned ID does not correspond to input id';
                        }

                        $response[] = $result;
                    }
                }
            }

            return $response;
        } else {
            return false;
        }
    }

    /**
     * @param array $activity
     * @param       $object
     *
     * @return array|mixed|string
     */
    public function createLeadActivity(array $activity, $object)
    {
        $config = $this->integration->getIntegrationSettings()->getFeatureSettings();

        //1st : set_entries to return ids module_name : "Leads" or "Contacts" and name_value_lists (array of arrays of name/value)
        $module_name          = $object;
        $set_name_value_lists = [];
        // set relationship
        $module_names     = []; //Contacts or Leads
        $module_ids       = []; //Contacts or leads ids
        $link_field_names = []; //Array of mtc_webactivities_contacts or mtc_webactivities_leads
        $related_ids      = []; //Array of arrays of web activity array
        $name_value_lists = []; //array of empty arrays
        $delete_array     = []; //Array of 0
        //set_relationships

        //Send activities and get back sugar activities id

        if (!empty($activity)) {
            foreach ($activity as $sugarId => $records) {
                foreach ($records['records'] as $key => $record) {
                    $rec   = [];
                    $rec[] = ['name' => 'name', 'value' => $record['name']];
                    $rec[] = ['name' => 'description', 'value' => $record['description']];
                    $rec[] = ['name' => 'url', 'value' => $records['leadUrl']];
                    $rec[] = ['name' => 'date_entered', 'value' => $record['dateAdded']->format('c')];
                    $rec[] = ['name' => 'reference_id', 'value' => $record['id'].'-'.$sugarId];
                    if ($object == 'Contacts') {
                        $rec[] = ['name' => 'contact_id_c', 'value' => $sugarId];
                    } else {
                        $rec[] = ['name' => 'lead_id_c', 'value' => $sugarId];
                    }

                    $set_name_value_lists[] = $rec;
                }
            }

            $parameters = [
                    'name_value_lists' => $set_name_value_lists,
                ];

            $resp = $this->request('set_entries', $parameters, 'POST', 'mtc_WebActivities');

                //Send sugar relationsips
                if (!empty($resp)) {
                    $nbLeads = 0;
                    $nbAct   = 0;
                    $idList  = [];

                    foreach ($activity as $sugarId => $records) {
                        $related_ids_row = [];

                        $module_names[] = $object;
                        $module_ids[]   = $sugarId;
                        if ($object == 'Contacts') {
                            $link_field_names[] = 'mtc_webactivities_contacts';
                        } else {
                            $link_field_names[] = 'mtc_webactivities_leads';
                        }
                        ++$nbLeads;
                        foreach ($records['records'] as $key => $record) {
                            $name_value_lists[] = [];
                            $delete_array[]     = 0;
                            $rec                = [];
                            $rec[]              = ['name' => 'name', 'value' => $record['name']];
                            $rec[]              = ['name' => 'description', 'value' => $record['description']];
                            $rec[]              = ['name' => 'url', 'value' => $records['leadUrl']];
                            $rec[]              = ['name' => 'date_entered', 'value' => $record['dateAdded']->format('c')];
                            $rec[]              = ['name' => 'reference_id', 'value' => $record['id'].'-'.$sugarId];
                            if ($object == 'Contacts') {
                                $rec[] = ['name' => 'contact_id_c', 'value' => $sugarId];
                            } else {
                                $rec[] = ['name' => 'lead_id_c', 'value' => $sugarId];
                            }
                            $set_name_value_lists[] = $rec;
                            $idList[]               = $sugarId;
                            $related_ids_row[]      = $resp['ids'][$nbAct];
                            ++$nbAct;
                        }
                        $related_ids[] = $related_ids_row;
                    }
                    $parameters = [
                        'module_names'     => $module_names, //Contacts or Leads
                        'module_ids'       => $module_ids, //Contacts or leads ids
                        'link_field_names' => $link_field_names, //Array of mtc_webactivities_contacts or mtc_webactivities_leads
                        'related_ids'      => $related_ids, //Array of arrays of web activity array
                        'name_value_lists' => $name_value_lists, //array of empty arrays
                        'delete_array'     => $delete_array, //Array of 0
                    ];

                    $resp2 = $this->request('set_relationships', $parameters, 'POST', $object);
                }

            if (!empty($activityData)) {
                //TODO
                //Send activities and get activities id
                //Send relationships

                //todo: log posted activities so that they don't get sent over again
                $queryUrl = $this->integration->getQueryUrl();
                $results  = $this->request(
                    'composite/tree/'.$mActivityObjectName,
                    $activityData,
                    'POST',
                    false,
                    null,
                    $queryUrl
                );

                $newRecordData = [];
                if ($results['hasErrors']) {
                    foreach ($results['results'] as $result) {
                        if ($result['errors'][0]['statusCode'] == 'CANNOT_UPDATE_CONVERTED_LEAD') {
                            $references   = explode('-', $result['referenceId']);
                            $SF_leadIds[] = $references[1];

                            $leadIds = implode("','", $SF_leadIds);
                            $query   = 'select Id, ConvertedContactId from '.$object." where id in ('".$leadIds."')";

                            $contacts = $this->request('query', ['q' => $query], 'GET', false, null, $queryUrl);

                            foreach ($contacts['records'] as $contact) {
                                foreach ($activityData['records'] as $key => $record) {
                                    if ($record[$namespace.'WhoId__c'] == $contact['Id']) {
                                        unset($record[$namespace.'WhoId__c']);
                                        $record[$namespace.'contact_id__c'] = $contact['ConvertedContactId'];
                                        $newRecordData['records'][]         = $record;
                                        unset($activityData['records'][$key]);
                                    }
                                }
                            }
                        }
                    }
                    if (!empty($newRecordData)) {
                        $results = $this->request(
                            'composite/tree/'.$mActivityObjectName,
                            $newRecordData,
                            'POST',
                            false,
                            null,
                            $queryUrl
                        );
                    }
                }

                return $results;
            }

            return [];
        }
    }

    public function getEmailBySugarUserId($query = null)
    {
        $tokenData = $this->integration->getKeys();
        if ($tokenData['version'] == '6') {
            if (isset($query['emails'])) {
                $q = " users.id IN (SELECT bean_id FROM email_addr_bean_rel eabr JOIN email_addresses ea ON (eabr.email_address_id = ea.id) WHERE bean_module = 'Users' AND ea.email_address IN ('".implode("','", $query['emails'])."') AND eabr.deleted=0) ";
            }
            if (isset($query['ids'])) {
                $q = " users.id IN ('".implode("','", $query['ids'])."') ";
            }

            $data   = ['filter' => 'all'];
            $fields = ['id', 'email1'];

            $parameters = [
                     'query'                    => $q,
                     'order_by'                 => '',
                     'offset'                   => 0,
                    'select_fields'             => $fields,
                    'link_name_to_fields_array' => [/* TO BE MODIFIED */
                    ],
                    'max_results' => 1000,
                    'deleted'     => 0,
                    'favorites'   => false,
                ];
            $data = $this->request('get_entry_list', $parameters, 'GET', 'Users');

            if (isset($query['type']) && $query['type'] == 'BYEMAIL') {
                $type = 'BYEMAIL';
            } else {
                $type = 'BYID';
            }

            $res = [];
            if (isset($data['entry_list'])) {
                foreach ($data['entry_list'] as $key => $record) {
                    $fields = [];
                    foreach ($record['name_value_list'] as $item) {
                        $fields[$item['name']] = $item['value'];
                    }
                    if ($type == 'BYID') {
                        $res[$fields['id']] = $fields['email1'];
                    } elseif (isset($fields['email1'])) {
                        $res[$fields['email1']] = $fields['id'];
                    }
                }
            }

            return $res;
        } else {
            //TODO
            $parameters = [
                'module_filter' => $this->module,
                'type_filter'   => 'modules',
            ];

            $response = $this->request('metadata', $parameters);

            return $response['modules']['Leads'];
        }
    }

    public function getIdBySugarEmail($query = null)
    {
        if ($query == null) {
            $query = ['type' => 'BYEMAIL'];
        } else {
            $query['type'] = 'BYEMAIL';
        }

        return $this->getEmailBySugarUserId($query);
    }

    /**
     * Get SugarCRM leads.
     *
     * @param array  $query
     * @param string $object
     *
     * @return mixed
     */
    public function getLeads($query, $object)
    {
        $tokenData = $this->integration->getKeys();
        $data      = ['filter' => 'all'];
        $fields    = $this->integration->getIntegrationSettings()->getFeatureSettings();

        switch ($object) {
            case 'company':
            case 'Account':
            case 'Accounts':
                $fields = array_keys(array_filter($fields['companyFields']));
                break;
            default:
                $mixedFields = array_filter($fields['leadFields']);
                $fields      = [];
                foreach ($mixedFields as $sugarField => $mField) {
                    if (strpos($sugarField, '__'.$object) !== false) {
                        $fields[] = str_replace('__'.$object, '', $sugarField);
                    }
                    if (strpos($sugarField, '-'.$object) !== false) {
                        $fields[] = str_replace('-'.$object, '', $sugarField);
                    }
                }
        }

        if ($tokenData['version'] == '6') {
            $result = [];

            if (!empty($fields)) {
                $q   = '';
                $qry = [];
                if (isset($query['start'])) {
                    $qry[] = ' '.strtolower($object).".date_modified >= '".$query['start']."' ";
                }
                if (isset($query['end'])) {
                    $qry[] = ' '.strtolower($object).".date_modified <= '".$query['end']."' ";
                }
                if (isset($query['email'])) {
                    $qry[]    = " leads.id IN (SELECT bean_id FROM email_addr_bean_rel eabr JOIN email_addresses ea ON (eabr.email_address_id = ea.id) WHERE bean_module = 'Leads' AND ea.email_address = '".$query['email']."' AND eabr.deleted=0) ";
                    $fields[] = 'contact_id';
                }
                if (isset($query['checkemail'])) {
                    $qry[]    = ' leads.deleted=0 ';
                    $qry[]    = " leads.id IN (SELECT bean_id FROM email_addr_bean_rel eabr JOIN email_addresses ea ON (eabr.email_address_id = ea.id) WHERE bean_module = 'Leads' AND ea.email_address IN ('".implode("','", $query['checkemail'])."') AND eabr.deleted=0) ";
                    $fields   = []; //Do not need previous fields
                    $fields[] = 'contact_id';
                    $fields[] = 'deleted';
                }
                if (isset($query['checkemail_contacts'])) {
                    $qry[]    = ' contacts.deleted=0 ';
                    $qry[]    = " contacts.id IN (SELECT bean_id FROM email_addr_bean_rel eabr JOIN email_addresses ea ON (eabr.email_address_id = ea.id) WHERE bean_module = 'Contacts' AND ea.email_address IN ('".implode("','", $query['checkemail_contacts'])."') AND eabr.deleted=0) ";
                    $fields   = []; //Do not need previous fields
                    $fields[] = 'deleted';
                }

                $q        = implode('AND', $qry);
                $fields[] = 'id';
                $fields[] = 'date_modified';
                $fields[] = 'date_entered';
                $fields[] = 'assigned_user_id';
                $fields[] = 'email1';
                if ($object != 'Accounts') {
                    $fields[] = 'account_id';
                }
                $parameters = [
                     'query'                    => $q,
                     'order_by'                 => '',
                     'offset'                   => $query['offset'],
                    'select_fields'             => $fields,
                    'link_name_to_fields_array' => [/* TO BE MODIFIED */

                        [
                            'name'  => 'email_addresses',
                            'value' => [
                                'email_address',
                                'opt_out',
                                'primary_address',
                            ],
                        ],

                    ],
                    'max_results' => $query['max_results'],
                    'deleted'     => 0,
                    'favorites'   => false,
                ];
                $resp = $this->request('get_entry_list', $parameters, 'GET', $object);

                return $resp;
            }
        } else {
            if (!empty($fields)) {
                $q      = '';
                $qry    = [];
                $filter = [];
                if (isset($query['start'])) {
                    $filter[] = ['date_modified' => ['$gte' => $query['start']]];
                    //$qry[] = ' '.strtolower($object).".date_modified >= '".$query['start']."' ";
                }
                if (isset($query['end'])) {
                    $filter[] = ['date_modified' => ['$lte' => $query['end']]];
                    //$qry[] = ' '.strtolower($object).".date_modified <= '".$query['end']."' ";
                }
                if (isset($query['email'])) {
                    $filter[] = ['email' => ['$equals' => $query['email']]];
                    //$qry[]    = " leads.id IN (SELECT bean_id FROM email_addr_bean_rel eabr JOIN email_addresses ea ON (eabr.email_address_id = ea.id) WHERE bean_module = 'Leads' AND ea.email_address = '".$query['email']."' AND eabr.deleted=0) ";
                    $fields[] = 'contact_id';
                }
                if (isset($query['checkemail'])) {
                    $filter[] = ['email' => ['$in' => $query['checkemail']]];
                    $filter[] = ['deleted' => '0'];
                    $fields   = []; //Do not need previous fields
                    $fields[] = 'contact_id';
                    $fields[] = 'deleted';
                }
                if (isset($query['checkemail_contacts'])) {
                    $filter[] = ['email' => ['$in' => $query['checkemail_contacts']]];
                    $filter[] = ['deleted' => '0'];
                    $fields   = []; //Do not need previous fields
                    $fields[] = 'deleted';
                }
                $fields[] = 'id';
                $fields[] = 'date_modified';
                $fields[] = 'date_entered';
                $fields[] = 'assigned_user_id';
                $fields[] = 'email1';
                if ($object != 'Accounts') {
                    $fields[] = 'account_id';
                }
                $filter_args = ['filter' => [['$and' => $filter]]];
                $fields_arg  = implode(',', $fields);
                $parameters  = [
//                     'order_by'                 => '',
                     'filter' => ['filter' => [['$and' => $filter]]],
                     'offset' => $query['offset'],
                    'fields'  => implode(',', $fields),
                    'max_num' => $query['max_results'],
                    //'deleted'     => 0,
                    //'favorites'   => false,
                ];
                $resp = $this->request("$object/filter", $parameters, 'POST', $object);

                return $resp;
            }
        }
    }
}
