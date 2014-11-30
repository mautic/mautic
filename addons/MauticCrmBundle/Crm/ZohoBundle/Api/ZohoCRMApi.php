<?php

namespace MauticAddon\MauticCrmBundle\Crm\ZohoBundle\Api;

use MauticAddon\MauticCrmBundle\Crm\ZohoBundle\Api\Auth\AuthInterface;
use MauticAddon\MauticCrmBundle\Crm\ZohoBundle\Api\Exception\ContextNotFoundException;

class ZohoCRMApi
{
    /**
     * Get an API context object
     *
     * @param string        $apiContext     API context (leads, forms, etc)
     * @param AuthInterface $auth           API Auth object
     */
    static function getContext($apiContext, AuthInterface $auth)
    {
        $apiContext = ucfirst($apiContext);

        static $contexts = array();

        if (!isset($context[$apiContext])) {
            $class = '\\Mautic\\ZohoBundle\\Api\\Api\\' . $apiContext;
            if (class_exists($class)) {
                $contexts[$apiContext] = new $class($auth);
            } else {
                throw new ContextNotFoundException("A context of '$apiContext' was not found.");
            }
        }

        return $contexts[$apiContext];
    }
}