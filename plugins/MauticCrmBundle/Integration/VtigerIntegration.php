<?php

namespace MauticPlugin\MauticCrmBundle\Integration;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class VtigerIntegration extends CrmAbstractIntegration
{
    private string $authorzationError = '';

    /**
     * Returns the name of the social integration that must match the name of the file.
     */
    public function getName(): string
    {
        return 'Vtiger';
    }

    public function getSupportedFeatures(): array
    {
        return ['push_lead'];
    }

    public function getDisplayName(): string
    {
        return 'vTiger';
    }

    /**
     * @return array<string, string>
     */
    public function getRequiredKeyFields(): array
    {
        return [
            'url'       => 'mautic.vtiger.form.url',
            'username'  => 'mautic.vtiger.form.username',
            'accessKey' => 'mautic.vtiger.form.password',
        ];
    }

    public function getClientIdKey(): string
    {
        return 'username';
    }

    public function getClientSecretKey(): string
    {
        return 'accessKey';
    }

    public function getAuthTokenKey(): string
    {
        return 'sessionName';
    }

    public function getApiUrl(): string
    {
        return sprintf('%s/webservice.php', $this->keys['url']);
    }

    /**
     * @return bool|array<mixed>|string
     */
    public function isAuthorized()
    {
        if (!isset($this->keys['url'])) {
            return false;
        }

        $url        = $this->getApiUrl();
        $parameters = [
            'operation' => 'getchallenge',
            'username'  => $this->keys['username'],
        ];

        $response = $this->makeRequest($url, $parameters, 'GET', ['authorize_session' => true]);

        if (empty($response['success'])) {
            return $this->getErrorsFromResponse($response);
        }

        $loginParameters = [
            'operation' => 'login',
            'username'  => $this->keys['username'],
            'accessKey' => md5($response['result']['token'].$this->keys['accessKey']),
        ];

        $response = $this->makeRequest($url, $loginParameters, 'POST', ['authorize_session' => true]);

        if (empty($response['success'])) {
            if (is_array($response) && array_key_exists('error', $response)) {
                $this->authorzationError = $response['error']['message'];
            }

            return false;
        } else {
            $error = $this->extractAuthKeys($response['result']);

            if (empty($error)) {
                return true;
            } else {
                $this->authorzationError = $error;

                return false;
            }
        }
    }

    public function getAuthLoginUrl(): string
    {
        return $this->router->generate('mautic_integration_auth_callback', ['integration' => $this->getName()]);
    }

    /**
     * Retrieves and stores tokens returned from oAuthLogin.
     *
     * @param array $settings
     * @param array $parameters
     */
    public function authCallback($settings = [], $parameters = []): string|bool
    {
        $success = $this->isAuthorized();
        if (!$success) {
            return $this->authorzationError;
        }

        return false;
    }

    /**
     * @return mixed[]
     */
    public function getAvailableLeadFields($settings = []): array
    {
        $vTigerFields      = [];
        $silenceExceptions = $settings['silence_exceptions'] ?? true;

        if (isset($settings['feature_settings']['objects'])) {
            $vTigerObjects = $settings['feature_settings']['objects'];
        } else {
            $settings      = $this->settings->getFeatureSettings();
            $vTigerObjects = $settings['objects'] ?? ['contacts'];
        }

        try {
            if ($this->isAuthorized()) {
                if (!empty($vTigerObjects) && is_array($vTigerObjects)) {
                    foreach ($vTigerObjects as $object) {
                        // The object key for contacts should be 0 for some BC reasons
                        if ('contacts' == $object) {
                            $object = 0;
                        }

                        // Check the cache first
                        $settings['cache_suffix'] = $cacheSuffix = '.'.$object;
                        if ($fields = parent::getAvailableLeadFields($settings)) {
                            $vTigerFields[$object] = $fields;
                            continue;
                        }

                        // Create the array if it doesn't exist to prevent PHP notices
                        if (!isset($vTigerFields[$object])) {
                            $vTigerFields[$object] = [];
                        }

                        $leadFields = $this->getApiHelper()->getLeadFields($object);
                        if (isset($leadFields['fields'])) {
                            foreach ($leadFields['fields'] as $fieldInfo) {
                                if (!isset($fieldInfo['name']) || !$fieldInfo['editable'] || in_array(
                                    $fieldInfo['type']['name'],
                                    ['owner', 'reference', 'boolean', 'autogenerated']
                                )
                                ) {
                                    continue;
                                }

                                $vTigerFields[$object][$fieldInfo['name']] = [
                                    'type'     => 'string',
                                    'label'    => $fieldInfo['label'],
                                    'required' => in_array($fieldInfo['name'], ['email', 'accountname']),
                                ];
                            }
                        }

                        $this->cache->set('leadFields'.$cacheSuffix, $vTigerFields[$object]);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            if (!$silenceExceptions) {
                throw $e;
            }
        }

        return $vTigerFields;
    }

    /**
     * @return array<mixed>
     */
    public function getFormNotes($section)
    {
        if ('leadfield_match' == $section) {
            return ['mautic.vtiger.form.field_match_notes', 'info'];
        }

        return parent::getFormNotes($section);
    }

    public function amendLeadDataBeforePush(&$mappedData): void
    {
        if (!empty($mappedData)) {
            // vtiger requires assigned_user_id so default to authenticated user
            $mappedData['assigned_user_id'] = $this->keys['userId'];
        }
    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea): void
    {
        if ('features' == $formArea) {
            $builder->add(
                'objects',
                ChoiceType::class,
                [
                    'choices' => [
                        'mautic.vtiger.object.contact' => 'contacts',
                        'mautic.vtiger.object.company' => 'company',
                    ],
                    'expanded'          => true,
                    'multiple'          => true,
                    'label'             => 'mautic.vtiger.form.objects_to_pull_from',
                    'label_attr'        => ['class' => ''],
                    'placeholder'       => false,
                    'required'          => false,
                ]
            );
        }
    }

    /**
     * Get available company fields for choices in the config UI.
     *
     * @param array $settings
     */
    public function getFormCompanyFields($settings = []): array
    {
        return parent::getAvailableLeadFields(['cache_suffix' => '.company']);
    }
}
