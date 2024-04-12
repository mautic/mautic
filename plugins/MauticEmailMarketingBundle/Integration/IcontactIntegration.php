<?php

namespace MauticPlugin\MauticEmailMarketingBundle\Integration;

use MauticPlugin\MauticEmailMarketingBundle\Form\Type\IcontactType;

class IcontactIntegration extends EmailAbstractIntegration
{
    public function getName(): string
    {
        return 'Icontact';
    }

    public function getDisplayName(): string
    {
        return 'iContact';
    }

    public function getAuthenticationType(): string
    {
        return 'rest';
    }

    /**
     * Get a list of keys required to make an API call.  Examples are key, clientId, clientSecret.
     *
     * @return array<string, string>
     */
    public function getRequiredKeyFields(): array
    {
        return [
            'API-AppId'    => 'mautic.icontact.keyfield.appid',
            'API-Username' => 'mautic.icontact.keyfield.username',
            'API-Password' => 'mautic.icontact.keyfield.password',
        ];
    }

    public function getSecretKeys(): array
    {
        return [
            'API-Password',
        ];
    }

    public function getApiUrl(): string
    {
        return 'https://app.icontact.com/icp/a';
    }

    /**
     * Get account ID and client folder ID.
     *
     * @param array $settings
     * @param array $parameters
     */
    public function authCallback($settings = [], $parameters = [])
    {
        $url = $this->getApiUrl();

        $response = $this->makeRequest($url, $parameters);

        // Validation kind of error
        if (isset($response['errors'][0])) {
            return $response['errors'][0];
        }

        // Timeout kind of error
        if (isset($response['errors']['message'])) {
            return $response['errors']['message'];
        }

        $keys = [];
        if (!empty($response['accounts'])) {
            $keys['accountId'] = $response['accounts'][0]['accountId'];

            $url .= '/'.$keys['accountId'].'/c';
            $response = $this->makeRequest($url, $parameters);

            if (!empty($response['clientfolders'])) {
                $keys['clientFolderId'] = $response['clientfolders'][0]['clientFolderId'];

                $this->extractAuthKeys($keys, 'clientFolderId');
            }
        }
    }

    /**
     * @param array  $parameters
     * @param string $method
     * @param array  $settings
     *
     * @return mixed|string
     */
    public function makeRequest($url, $parameters = [], $method = 'GET', $settings = [])
    {
        $settings['headers'] = [
            'Except:',
            'Accept: application/json',
            'Content-Type: application/json',
            'Api-Version: 2.2',
            'Api-AppId: '.$this->keys['API-AppId'],
            'Api-Username: '.$this->keys['API-Username'],
            'API-Password: '.$this->keys['API-Password'],
        ];

        return parent::makeRequest($url, $parameters, $method, $settings);
    }

    public function isAuthorized(): bool
    {
        $keys = $this->getRequiredKeyFields();
        foreach ($keys as $k => $l) {
            if (empty($this->keys[$k])) {
                return false;
            }
        }

        if (empty($this->keys['accountId']) || empty($this->keys['clientFolderId'])) {
            return false;
        }

        return true;
    }

    /**
     * @return mixed[]
     */
    public function getAvailableLeadFields($settings = []): array
    {
        if (!$this->isAuthorized()) {
            return [];
        }

        static $leadFields = [];

        if (empty($leadFields)) {
            $fields = [
                'email',
                'prefix',
                'firstName',
                'lastName',
                'suffix',
                'street',
                'street2',
                'city',
                'state',
                'postalCode',
                'phone',
                'fax',
                'business',
            ];

            $leadFields = [];
            foreach ($fields as $f) {
                $leadFields[$f] = [
                    'label'    => $this->translator->trans('mautic.icontact.field.'.$f),
                    'type'     => 'string',
                    'required' => ('email' == $f) ? true : false,
                ];
            }

            $customfields = $this->getApiHelper()->getCustomFields();

            if (!empty($customfields['customfields'])) {
                foreach ($customfields['customfields'] as $field) {
                    $leadFields['cf_'.$field['customFieldId']] = [
                        'label'    => $field['publicName'],
                        'type'     => 'string',
                        'required' => false,
                    ];
                }
            }
        }

        return $leadFields;
    }

    /**
     * @param \Mautic\LeadBundle\Entity\Lead $lead
     * @param array                          $config
     */
    public function pushLead($lead, $config = []): bool
    {
        $config = $this->mergeConfigToFeatureSettings($config);

        $mappedData = $this->populateLeadData($lead, $config);

        if (empty($mappedData)) {
            return false;
        } elseif (empty($mappedData['email'])) {
            return false;
        } elseif (!isset($config['list_settings'])) {
            return false;
        }

        try {
            if ($this->isAuthorized()) {
                $customfields = [];
                foreach ($mappedData as $k => &$v) {
                    if (str_starts_with($k, 'cf_')) {
                        $customfields[str_replace('cf_', '', $k)] = (string) $v;
                        unset($mappedData[$k]);
                    } else {
                        $v = (string) $v;
                    }
                }

                $listId = $config['list_settings']['list'];

                if (!empty($customfields)) {
                    $mappedData += $customfields;
                }

                $this->getApiHelper()->subscribeLead($listId, $mappedData);

                return true;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return false;
    }

    public function getFormType(): string
    {
        return IcontactType::class;
    }
}
