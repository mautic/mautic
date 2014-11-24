<?php
namespace Mautic\VtigerBundle\Api\Api;

use Mautic\VtigerBundle\Api\Auth\AuthInterface;
use Mautic\VtigerBundle\Api\Exception\ContextNotFoundException;

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