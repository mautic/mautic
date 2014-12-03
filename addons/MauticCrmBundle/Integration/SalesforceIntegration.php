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
        return 'clientKey';
    }

    /**
     * Get the array key for client secret
     *
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'clientSecret';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return array(
            'clientKey'     => 'mautic.integration.keyfield.consumerid',
            'clientSecret'  => 'mautic.integration.keyfield.consumersecret'
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
     * @return \MauticAddon\MauticCrmBundle\Api\Auth\AuthInterface|void
     */
    public function createApiAuth($parameters = array(), $authMethod = 'Auth')
    {
        $salesForceSettings                     = $this->settings->getApiKeys();
        $salesForceSettings['callback']         = $this->getOauthCallbackUrl();
        $salesForceSettings['accessTokenUrl']   = 'https://login.salesforce.com/services/oauth2/token';
        $salesForceSettings['authorizationUrl'] = 'https://login.salesforce.com/services/oauth2/authorize';

        parent::createApiAuth($salesForceSettings);
    }

    /**
     * Check API Authentication
     */
    public function checkApiAuth($silenceExceptions = true)
    {
        try {
            if (!$this->auth->isAuthorized()) {
                return false;
            } else {
                return true;
            }
        } catch (ErrorException $exception) {
            $this->logIntegrationError($exception);

            if (!$silenceExceptions) {
                throw $exception;
            }
            return false;
        }
    }

    /**
     * @return array|mixed
     */
    public function getAvailableFields($silenceExceptions = true)
    {
        $salesFields = array();

        try {
            if ($this->checkApiAuth($silenceExceptions)) {
                $leadObject = CrmApi::getContext($this->getName(), 'lead', $this->auth, 'v20.0')->getInfo('lead');

                if (isset($leadObject['fields'])) {
                    foreach ($leadObject['fields'] as $fieldInfo) {
                        if (!isset($fieldInfo['name']))
                            continue;
                        $salesFields[$fieldInfo['name']] = array("type" => "string");
                    }
                }
            }
        } catch (\Exception $e) {
            $logger = $this->factory->getLogger();
            $logger->addError('INTEGRATION CONNECT ERROR: ' . $this->getName() . ' - ' . $e->getMessage());
        }

        return $salesFields;
    }

    /**
     * @param $data
     * @return mixed|void
     */
    public function create(MauticFactory $factory, $data)
    {

    }
}
