<?php

namespace MauticAddon\MauticCrmBundle\Api;

use MauticAddon\MauticCrmBundle\Api\Auth\AuthInterface;
use MauticAddon\MauticCrmBundle\Api\Exception\ContextNotFoundException;

class CrmApi
{
    /**
     * @var ApiAuth
     */
    protected $auth;

    protected $version;

    public function __construct($auth, $version)
    {
        $this->auth = $auth;
        $this->version = $version;
    }

    /**
     * Get an API context object
     *
     * @param string        $apiContext     API context (leads, forms, etc)
     * @param AuthInterface $auth           API Auth object
     @ param  string|null   $apiVersion     API version if applicable
     */
    static function getContext($crm, $apiContext, AuthInterface $auth, $apiVersion = null)
    {
        $apiContext = ucfirst($apiContext);

        static $contexts = array();

        if (!isset($context[$apiContext])) {
            $class = 'MauticAddon\\MauticCrmBundle\\Crm\\'.$crm.'\\Api\\Object\\' . $apiContext;
            if (class_exists($class)) {
                $contexts[$apiContext] = new $class($auth, $apiVersion);
            } else {
                throw new ContextNotFoundException("A context of '$apiContext' was not found.");
            }
        }

        return $contexts[$apiContext];
    }
}