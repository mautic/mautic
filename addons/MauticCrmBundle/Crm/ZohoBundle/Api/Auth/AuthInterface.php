<?php

namespace MauticAddon\MauticCrmBundle\Crm\ZohoBundle\Api\Auth;

interface AuthInterface
{
    /**
     * Make a request to server using the supported auth method
     *
     * @param        $url
     * @param array  $parameters
     * @param string $method
     * @param array  $settings
     *
     * @return array
     */
    public function makeRequest ($url, array $parameters = array(), $method = 'GET', array $settings = array());

    /**
     * Check if current authorization is still valid
     *
     * @return bool
     */
    public function isAuthorized();
}