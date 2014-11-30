<?php

namespace MauticAddon\MauticCrmBundle\Crm\SalesforceBundle\Api;

use MauticAddon\MauticCrmBundle\Crm\SalesforceBundle\Api\Auth\AuthInterface;
use MauticAddon\MauticCrmBundle\Crm\SalesforceBundle\Api\Exception\ContextNotFoundException;

class SalesforceApi
{
    /**
     * Get an API context object
     *
     * @param string        $apiContext     API context (leads, forms, etc)
     * @param AuthInterface $auth           API Auth object
     * @param string        $baseUrl        Base URL for API endpoints
     */
    static function getContext($apiContext, AuthInterface $auth, $apiVersion = 'v20.0')
    {
        $apiContext = ucfirst($apiContext);

        static $contexts = array();

        if (!isset($context[$apiContext])) {
            $class = 'Mautic\\SalesforceBundle\\Api\\Api\\' . $apiContext;
            if (class_exists($class)) {
                $contexts[$apiContext] = new $class($auth, $apiVersion);
            } else {
                throw new ContextNotFoundException("A context of '$apiContext' was not found.");
            }
        }

        return $contexts[$apiContext];
    }
}