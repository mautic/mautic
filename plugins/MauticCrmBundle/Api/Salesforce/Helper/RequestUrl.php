<?php

namespace MauticPlugin\MauticCrmBundle\Api\Salesforce\Helper;

class RequestUrl
{
    /**
     * Correctly generate the URL based on given URL parts.
     *
     * @param null $operation
     * @param null $object
     *
     * @return string
     */
    public static function get($apiUrl, $queryUrl, $operation = null, $object = null)
    {
        if ($queryUrl) {
            return ($operation) ? sprintf($queryUrl.'/%s', $operation) : $queryUrl;
        }

        return sprintf($apiUrl.'/%s/%s', $object, $operation);
    }
}
