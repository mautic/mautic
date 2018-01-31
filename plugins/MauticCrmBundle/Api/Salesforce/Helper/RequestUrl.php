<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Api\Salesforce\Helper;

/**
 * Class RequestUrl.
 */
class RequestUrl
{
    /**
     * Correctly generate the URL based on given URL parts.
     *
     * @param      $apiUrl
     * @param      $queryUrl
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
