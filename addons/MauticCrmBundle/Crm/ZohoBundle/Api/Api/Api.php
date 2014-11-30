<?php
namespace MauticAddon\MauticCrmBundle\Crm\ZohoBundle\Api\Api;

use MauticAddon\MauticCrmBundle\Crm\ZohoBundle\Api\Auth\AuthInterface;
use MauticAddon\MauticCrmBundle\Crm\ZohoBundle\Api\Exception\ContextNotFoundException;

class Api
{
    /**
     * @var AuthInterface $auth
     */
    protected $auth;

    /**
     * @param AuthInterface $auth
     */
    public function __construct(AuthInterface $auth)
    {
        $this->auth    = $auth;
    }
}