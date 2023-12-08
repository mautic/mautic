<?php

namespace Mautic\PluginBundle\Integration;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface UnifiedIntegrationInterface is used for type hinting.
 */
interface UnifiedIntegrationInterface
{
    /**
     * Make a basic call using cURL to get the data.
     *
     * @param string $url
     * @param array  $parameters
     * @param string $method
     * @param array  $settings   Set $settings['return_raw'] to receive a ResponseInterface
     *
     * @return mixed|string|ResponseInterface
     */
    public function makeRequest($url, $parameters = [], $method = 'GET', $settings = []);
}
