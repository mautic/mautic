<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCrmBundle\Integration;
use MauticAddon\MauticCrmBundle\Api\CrmApi;
use MauticAddon\MauticCrmBundle\Api\Exception\ErrorException;

/**
 * Class SalesforceIntegration
 */
class SalesforceIntegration extends CrmAbstractIntegration
{
    /**
     * @var \MauticAddon\MauticCrmBundle\Crm\Salesforce\Api\Auth\Auth
     */
    protected $auth;

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Salesforce';
    }

    /**
     * Get the array key for clientId
     *
     * @return string
     */
    public function getClientIdKey()
    {
        return 'client_key';
    }

    /**
     * Get the array key for client secret
     *
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'client_secret';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return array(
            'client_key'     => 'mautic.integration.keyfield.consumerid',
            'client_secret'  => 'mautic.integration.keyfield.consumersecret'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAccessTokenUrl()
    {
        return 'https://login.salesforce.com/services/oauth2/token';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationUrl()
    {
        return 'https://login.salesforce.com/services/oauth2/authorize';
    }

    /**
     * {@inheritdoc}
     */
    public function getOAuthLoginUrl()
    {
        return $this->getCrmLoginUrl(true);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'oauth2';
    }

    /**
     * @return \MauticAddon\MauticCrmBundle\Api\Auth\AbstractAuth|void
     */
    public function createApiAuth($parameters = array(), $authMethod = 'Auth')
    {
        $salesForceSettings                     = $this->getDecryptedApiKeys();
        $salesForceSettings['callback']         = $this->getOauthCallbackUrl();
        $salesForceSettings['accessTokenUrl']   = 'https://login.salesforce.com/services/oauth2/token';
        $salesForceSettings['authorizationUrl'] = 'https://login.salesforce.com/services/oauth2/authorize';

        parent::createApiAuth($salesForceSettings);
    }

    /**
     * @return array|mixed
     */
    public function getAvailableFields($silenceExceptions = true)
    {
        $salesFields = array();

        try {
            if ($this->checkApiAuth($silenceExceptions)) {
                $leadObject = CrmApi::getContext($this, 'lead', $this->auth)->getInfo();

                if ($leadObject != null && isset($leadObject['fields'])) {
                    foreach ($leadObject['fields'] as $fieldInfo) {
                        if (!$fieldInfo['updateable'] || !isset($fieldInfo['name']) || in_array($fieldInfo['type'], array('reference', 'boolean'))) {
                            continue;
                        }

                        $salesFields[$fieldInfo['name']] = array(
                            'type' => 'string',
                            'label' => $fieldInfo['label']
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            $logger = $this->factory->getLogger();
            $logger->addError('INTEGRATION CONNECT ERROR: ' . $this->getName() . ' - ' . $e->getMessage());

            if (!$silenceExceptions) {
                throw $e;
            }
        }

        return $salesFields;
    }

    /**
     * {@inheritdoc}
     *
     * @param $section
     *
     * @return string
     */
    public function getFormNotes($section)
    {
        if ($section == 'authorization') {
            return array('mautic.salesforce.form.oauth_requirements', 'warning');
        }

        return parent::getFormNotes($section);
    }
}
