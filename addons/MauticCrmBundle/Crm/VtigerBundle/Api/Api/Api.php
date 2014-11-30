<?php
namespace MauticAddon\MauticCrmBundle\Crm\VtigerBundle\Api\Api;

use MauticAddon\MauticCrmBundle\Crm\VtigerBundle\Api\Auth\AuthInterface;
use MauticAddon\MauticCrmBundle\Crm\VtigerBundle\Api\Exception\ContextNotFoundException;

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