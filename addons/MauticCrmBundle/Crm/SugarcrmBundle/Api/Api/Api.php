<?php
namespace MauticAddon\MauticCrmBundle\Crm\SugarcrmBundle\Api\Api;

use MauticAddon\MauticCrmBundle\Crm\SugarcrmBundle\Api\Auth\AuthInterface;

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