<?php

namespace MauticAddon\MauticCrmBundle\Crm\VtigerBundle\Api;

use MauticAddon\MauticCrmBundle\Crm\VtigerBundle\Api\Auth\AuthInterface;
use MauticAddon\MauticCrmBundle\Crm\VtigerBundle\Api\Exception\ContextNotFoundException;

class vTigerCRMApi
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
            $class = '\\Mautic\\VtigerBundle\\Api\\Api\\' . $apiContext;
            if (class_exists($class)) {
                $contexts[$apiContext] = new $class($auth);
            } else {
                throw new ContextNotFoundException("A context of '$apiContext' was not found.");
            }
        }

        return $contexts[$apiContext];
    }
}