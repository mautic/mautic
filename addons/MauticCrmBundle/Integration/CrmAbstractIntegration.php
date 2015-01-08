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

        $fields = $lead->getFields(true);

        $leadFields = $featureSettings['leadFields'];

        $matched = array();
        foreach ($leadFields as $crm => $mautic) {
            if (isset($fields[$mautic]) && !empty($fields[$mautic]['value'])) {
                $matched[$crm] = $fields[$mautic]['value'];
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
            if (!$silenceExceptions) {
                throw $exception;
            } else {
                $this->logIntegrationError($exception);
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
        try {
            if ($this->checkApiAuth(false)) {
                return CrmApi::getContext($this->getName(), "lead", $this->auth)->create($mappedData);
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }
        return false;
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
    public function getClientSecreteKey()
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

}