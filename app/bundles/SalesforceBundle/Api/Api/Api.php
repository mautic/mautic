<?php
namespace Mautic\SalesforceBundle\Api\Api;

use Mautic\SalesforceBundle\Api\Auth\AuthInterface;
use Mautic\SalesforceBundle\Api\Exception\ContextNotFoundException;

class Api
{
    /**
     * @var AuthInterface $auth
     */
    protected $auth;

    /**
     * Api Version
     *
     * @var String $version
     */
    protected $version;

    /**
     * @param AuthInterface $auth
     * @param string        $baseUrl
     */
    public function __construct(AuthInterface $auth, $version)
    {
        $this->auth    = $auth;
        $this->version = $version;
    }

    /**
     * Define new version to api
     *
     * @param $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }
}