<?php
namespace vTigerCRM\Api;

use vTigerCRM\Auth\AuthInterface;
use vTigerCRM\Exception\ContextNotFoundException;

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