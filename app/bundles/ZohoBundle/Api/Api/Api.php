<?php
namespace Mautic\ZohoBundle\Api\Api;

use Mautic\ZohoBundle\Api\Auth\AuthInterface;
use Mautic\ZohoBundle\Api\Exception\ContextNotFoundException;

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