<?php

namespace MauticAddon\MauticCrmBundle\Api;

use MauticAddon\MauticCrmBundle\Api\Auth\AbstractAuth;
use MauticAddon\MauticCrmBundle\Api\Exception\ContextNotFoundException;

class CrmApi
{
    /**
     * @var ApiAuth
     */
    protected $auth;

    public function __construct($auth)
    {
        $this->auth = $auth;
    }

    /**
     * Get an API context object
     *
     * @param string        $apiContext     API context (leads, forms, etc)
     * @param AbstractAuth $auth           API Auth object
     */
    static function getContext($crm, $apiContext, AbstractAuth $auth)
    {
        $apiContext = ucfirst($apiContext);

        static $contexts = array();

        if (!isset($context[$apiContext])) {
            $class = 'MauticAddon\\MauticCrmBundle\\Crm\\'.$crm.'\\Api\\Object\\' . $apiContext;
            if (class_exists($class)) {
                $contexts[$apiContext] = new $class($auth);
            } else {
                throw new ContextNotFoundException("A context of '$apiContext' was not found.");
            }
        }

        return $contexts[$apiContext];
    }
}