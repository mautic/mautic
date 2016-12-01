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

/**
 * Class HubspotIntegration.
 */
class HubspotIntegration extends CrmAbstractIntegration
{
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
        $settings['feature_settings']['objects']['company'] = 'company';

        return ($this->isAuthorized()) ? $this->getAvailableLeadFields($settings) : [];
    }

    /**
     * @return array|mixed
     */
    public function getAvailableLeadFields($settings = [])
    {
        $hubsFields        = [];
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;

        $hubspotObjects = [];

        if (isset($settings['feature_settings']['objects'])) {
            $hubspotObjects = $settings['feature_settings']['objects'];
        }

        try {
            if ($this->isAuthorized()) {
                if (!empty($hubspotObjects) and is_array($hubspotObjects)) {
                    foreach ($hubspotObjects as $key => $object) {
                        $leadFields = $this->getApiHelper()->getLeadFields($object);
                        if (isset($leadFields)) {
                            foreach ($leadFields as $fieldInfo) {
                                if ($object != 'company') {
                                    $hubsFields[$fieldInfo['name']] = [
                                        'type'  => 'string',
                                        'label' => $fieldInfo['label'],
                                    ];
                                } else {
                                    $hubsFields[$object][$fieldInfo['name']] = [
                                        'type'  => 'string',
                                        'label' => $fieldInfo['label'],
                                    ];
                                }
                            }
                        }
                        // Email is Required for this kind of integration
                        $hubsFields['email']['required'] = true;
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
    public function formatLeadDataForCreateOrUpdate($leadData = [])
    {
        $formattedLeadData = [];

        foreach ($leadData as $field => $value) {
            $formattedLeadData['properties'][] = [
                'property' => $field,
                'value'    => $value,
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
        if ($object == 'Lead') {
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
                $data = $this->getApiHelper()->getContacts($params);
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

                return $executed;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $executed;
    }

    public function getCompanies($params = [])
    {
        $executed = null;
        try {
            if ($this->isAuthorized()) {
                $data = $this->getApiHelper()->getCompanies($params);
                foreach ($data['results'] as $company) {
                    if (is_array($company)) {
                        $companyData = $this->amendLeadDataBeforeMauticPopulate($company, null);
                        $company     = $this->getMauticCompany($companyData);
                        if ($company) {
                            ++$executed;
                        }
                    }
                }
                if ($data['hasMore']) {
                    $params['vidOffset']  = $data['vid-offset'];
                    $params['timeOffset'] = $data['time-offset'];
                    $this->getCompanies($params);
                }

                return $executed;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $executed;
    }
}
