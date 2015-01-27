<?php

namespace MauticAddon\MauticCrmBundle\Api;

use MauticAddon\MauticCrmBundle\Api\Auth\AbstractAuth;
use MauticAddon\MauticCrmBundle\Api\Exception\ContextNotFoundException;
use MauticAddon\MauticCrmBundle\Integration\CrmAbstractIntegration;

class CrmApi
{

    protected $integration;

    public function __construct(CrmAbstractIntegration $integration)
    {
        $this->integration = $integration;
    }

    /**
     * Get an API context object
     *
     * @param CrmAbstractIntegration  $crm
     * @param string                  $apiContext     API context (leads, forms, etc)
     * @param AbstractAuth            $auth           API Auth object
     */
    static function getContext($crm, $apiContext)
    {
        $apiContext = ucfirst($apiContext);

        static $contexts = array();

        if (!isset($context[$apiContext])) {
            $class = 'MauticAddon\\MauticCrmBundle\\Crm\\'.$crm->getName().'\\' . $apiContext;
            if (class_exists($class)) {
                $contexts[$apiContext] = new $class($crm);
            } else {
                throw new ContextNotFoundException("A context of '$apiContext' was not found.");
            }
        }

        return $contexts[$apiContext];
    }
}