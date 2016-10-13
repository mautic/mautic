<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\IpLookup;

/**
 * Class AbstractRemoteDataLookup.
 */
abstract class AbstractRemoteDataLookup extends AbstractLookup
{
    /**
     * Method to use when communicating with the service.
     *
     * @var string
     */
    protected $method = 'get';

    /**
     * Get the URL to fetch data from.
     *
     * @return mixed
     */
    abstract protected function getUrl();

    /**
     * @param $response
     *
     * @return mixed
     */
    abstract protected function parseResponse($response);

    /**
     * @return array
     */
    protected function getHeaders()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getParameters()
    {
        return [];
    }

    /**
     * Fetch data from lookup service.
     */
    protected function lookup()
    {
        $url = $this->getUrl();

        try {
            $response = ('post' == $this->method) ?
                $this->connector->post($url, $this->getParameters(), $this->getHeaders(), 10) :
                $this->connector->get($url, $this->getHeaders(), 10);

            $this->parseResponse($response->body);
        } catch (\Exception $exception) {
            if ($this->logger) {
                $this->logger->warning('IP LOOKUP: '.$exception->getMessage());
            }
        }
    }
}
