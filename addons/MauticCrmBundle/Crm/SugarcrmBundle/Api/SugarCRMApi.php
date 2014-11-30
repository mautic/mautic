<?php

namespace MauticAddon\MauticCrmBundle\Crm\SugarcrmBundle\Api;

use MauticAddon\MauticCrmBundle\Crm\SugarcrmBundle\Api\Auth\AuthInterface;
use MauticAddon\MauticCrmBundle\Crm\SugarcrmBundle\Api\Exception\ContextNotFoundException;

class SugarCRMApi
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
            $class = 'Mautic\\SugarcrmBundle\\Api\\Api\\' . $apiContext;
            if (class_exists($class)) {
                $contexts[$apiContext] = new $class($auth);
            } else {
                throw new ContextNotFoundException("A context of '$apiContext' was not found.");
            }
        }

        return $contexts[$apiContext];
    }
}