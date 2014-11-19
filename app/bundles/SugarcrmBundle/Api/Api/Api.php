<?php
namespace Mautic\SugarcrmBundle\Api\Api;

use Mautic\SugarcrmBundle\Api\Auth\AuthInterface;

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