<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 12/1/14
 * Time: 7:12 PM
 */

namespace MauticAddon\MauticCrmBundle\Integration;


use Mautic\AddonBundle\Entity\Integration;
use Mautic\AddonBundle\Integration\AbstractIntegration;
use MauticAddon\MauticCrmBundle\Api\CrmApi;

/**
 * Class CrmAbstractIntegration
 *
 * @package MauticAddon\MauticCrmBundle\Integration
 */
abstract class CrmAbstractIntegration extends AbstractIntegration
{

    protected $auth;

    /**
     * @param Integration $settings
     */
    public function setIntegrationSettings(Integration $settings)
    {
        //make sure URL does not have ending /
        $keys = $settings->getApiKeys();
        if (isset($keys['url']) && substr($keys['url'], -1) == '/') {
            $keys['url'] = substr($keys['url'], 0, -1);
            $this->encryptAndSetApiKeys($keys, $settings);
        }

        parent::setIntegrationSettings($settings);

        //build auth object
        $this->createApiAuth();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'callback';
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return array('push_lead');
    }

    /**
     * {@inheritdoc}
     */
    public function getOAuthLoginUrl ()
    {
        return $this->getCrmLoginUrl();
    }

    /**
     * @param bool $oauth
     *
     * @return string
     */
    public function getCrmLoginUrl($oauth = false)
    {
        if ($oauth) {
            return parent::getOAuthLoginUrl();
        } else {
            return $this->factory->getRouter()->generate('mautic_integration_oauth_callback', array('integration' => $this->getName()));
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param string $clientId
     * @param string $clientSecret
     *
     * @return array
     */
    public function oAuthCallback($clientId = '', $clientSecret = '')
    {
        $entity = $this->getIntegrationSettings();

        if ($entity == null) {
            $entity = new Integration();
            $entity->setName($this->getName());
        }

        if (!empty($clientId)) {
            $keys = $this->getDecryptedApiKeys($entity);

            $keys[$this->getClientIdKey()]     = $clientId;
            $keys[$this->getClientSecretKey()] = $clientSecret;

            $this->encryptAndSetApiKeys($keys, $entity);
            $this->setIntegrationSettings($entity);
        }

        $error  = '';
        try {
            if (!$this->authorizeApi()) {
                $error = 'authorization failed';
            }
        } catch (\Exception $e) {
            $error = $e->getMessage() . " (" . $e->getCode() . ")";
        }

        //save the data
        $em = $this->factory->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return array($entity, $error);
    }

    /**
     * @param array  $parameters
     * @param string $authMethod
     *
     * @return \MauticAddon\MauticCrmBundle\Api\Auth\AbstractAuth
     */
    public function createApiAuth($parameters = array(), $authMethod = 'Auth')
    {
        $class = sprintf('\\MauticAddon\\MauticCrmBundle\\Crm\\%s\\Api\\Auth\\%s', $this->getName(), $authMethod);
        $this->auth = new $class();

        $reflection = new \ReflectionMethod($class, 'setup');
        $pass       = array();
        foreach ($reflection->getParameters() as $param) {
            if (isset($parameters[$param->getName()])) {
                $pass[] = $parameters[$param->getName()];
            } else {
                $pass[] = null;
            }
        }

        $reflection->invokeArgs($this->auth, $pass);
    }

    /**
     * Authorizes the API
     */
    public function authorizeApi()
    {
        if ($this->auth->validateAccessToken()) {
            if ($this->auth->accessTokenUpdated()) {
                $accessTokenData = $this->auth->getAccessTokenData();
                $this->mergeApiKeys($accessTokenData);
            }

            return true;
        }
        return false;
    }

    /**
     * Return key recognized by CRM
     *
     * @param $key
     * @param $field
     *
     * @return mixed
     */
    public function convertLeadFieldKey($key, $field)
    {
        return $key;
    }

    /**
     * Match lead data with CRM fields
     *
     * @param $lead
     *
     * @return array
     */
    public function populateLeadData($lead)
    {
        $featureSettings = $this->settings->getFeatureSettings();

        if (empty($featureSettings['leadFields'])) {
            return false;
        }

        $fields          = $lead->getFields(true);
        $leadFields      = $featureSettings['leadFields'];
        $availableFields = $this->getAvailableFields();

        $unknown = $this->factory->getTranslator()->trans('mautic.crm.form.lead.unknown');

        foreach ($availableFields as $key => $field) {
            $crmKey = $this->convertLeadFieldKey($key, $field);

            if (isset($leadFields[$key])) {
                $mauticKey = $leadFields[$key];
                if (isset($fields[$mauticKey]) && !empty($fields[$mauticKey]['value'])) {
                    $matched[$crmKey] = $fields[$mauticKey]['value'];
                }
            }

            if (!empty($field['required']) && empty($matched[$crmKey])) {
                $matched[$crmKey] = $unknown;
            }
        }

        return $matched;
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
     * @param $lead
     */
    public function pushLead($lead)
    {
        $mappedData = $this->populateLeadData($lead);

        $this->amendLeadDataBeforePush($mappedData);

        if (empty($mappedData)) {
            return false;
        }

        try {
            if ($this->checkApiAuth(false)) {
                CrmApi::getContext($this->getName(), "lead", $this->auth)->create($mappedData);
                return true;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }
        return false;
    }

    /**
     * Amend mapped lead data before pushing to CRM
     *
     * @param $mappedData
     */
    public function amendLeadDataBeforePush(&$mappedData)
    {

    }

    /**
     * @return string
     */
    public function getClientIdKey()
    {
        return 'client_key';
    }

    /**
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'client_secret';
    }


    /**
     * {@inheritdoc}
     */
    public function sortFieldsAlphabetically()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @param $section
     *
     * @return string
     */
    public function getFormNotes ($section)
    {
        if ($section == 'field_match') {
            return array('mautic.crm.form.field_match_notes', 'info');
        }

        return parent::getFormNotes($section);
    }
}