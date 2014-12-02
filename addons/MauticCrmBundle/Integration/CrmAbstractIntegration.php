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
        parent::setIntegrationSettings($settings);

        //build auth object
        $this->createApiAuth();
    }

    /**
     * @return mixed
     */
    abstract public function checkApiAuth();

    /**
     * @param MauticFactory $factory
     * @param $data
     * @return mixed
     */
    abstract public function create(MauticFactory $factory, $data);

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
        return array('lead_push');
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

        $keys = $entity->getApiKeys();
        $keys[$this->getClientIdKey()] = $clientId;
        $keys[$this->getClientSecretKey()] = $clientSecret;
        $entity->setApiKeys($keys);

        $this->setIntegrationSettings($entity);

        $error  = '';
        try {
            $this->authorizeApi();
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
     * @return \MauticAddon\MauticCrmBundle\Api\Auth\AuthInterface
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
                $this->settings->setApiKeys($accessTokenData);
            }

            return true;
        }
        return false;
    }
}