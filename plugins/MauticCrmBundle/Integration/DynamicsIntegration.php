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

use Mautic\PluginBundle\Exception\ApiErrorException;

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
        return ['refresh_token', ''];
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
     * @return string
     */
    public function populateLeadData($lead, $config = [])
    {
        $config['object'] = 'contacts';
        $mappedData       = parent::populateLeadData($lead, $config);

        return $mappedData;
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
     * @param array      $params
     * @param null|array $query
     *
     * @return int|null
     */
    public function getLeads($params = [], $query = null, &$executed = null, &$result = [], $object = 'contacts')
    {
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
                $oparams['request_settings']['headers'][] = 'Prefer: odata.maxpagesize='.$params['limit'] ?? 100;
                $oparams['$select']                       = implode(',', $mappedData);
                $data                                     = $this->getApiHelper()->getLeads($oparams);
                $result                                   = $this->amendLeadDataBeforeMauticPopulate($data, $object);
                $executed += count($result);
                // TODO: fetch more records using "@odata.nextLink" value
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
        }
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
                        if ('Accounts' === $object) {
                            $recordId = $entityData['ACCOUNTID'];
                            /** @var Company $entity */
                            $entity = $this->getMauticCompany($entityData);
                            if ($entity) {
                                $result[] = $entity->getName();
                            }
                            $mauticObjectReference = 'company';
                        } elseif ('Leads' === $object || 'Contacts' === $object) {
                            $recordId = ('Leads' === $object) ? $entityData['LEADID'] : $entityData['CONTACTID'];
                            /** @var Lead $entity */
                            $entity = $this->getMauticLead($entityData);
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
                            if ($integrationId == null) {
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
                }
            }
            unset($integrationEntities);
        }

        return $result;
    }
}
