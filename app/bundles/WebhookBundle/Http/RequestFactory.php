<?php

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace Mautic\WebhookBundle\Http;

use GuzzleHttp\Psr7\Request;

class RequestFactory
{
    /**
     * @param string $method
     * @param string $url
     * @param array  $headers
     * @param array  $payload
     *
     * @return Request
     */
    public function create($method, $url, array $headers, array $payload)
    {
        return new Request(
            $method,
            $url,
            $headers,
            json_encode($payload)
        );
    }
}
