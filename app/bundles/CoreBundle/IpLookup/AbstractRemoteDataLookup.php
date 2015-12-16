<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\IpLookup;

use Joomla\Http\HttpFactory;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractRemoteDataLookup
 */
abstract class AbstractRemoteDataLookup extends AbstractLookup
{
    /**
     * Connector to obtain data from IP service
     *
     * @var Http
     */
    protected $connector;

    /**
     * Method to use when communicating with the service
     *
     * @var string
     */
    protected $method = 'get';

    /**
     * Get the URL to fetch data from
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
     * AbstractRemoteDataLookup constructor.
     *
     * @param null                 $auth
     * @param null                 $ipLookupConfig
     * @param null                 $cacheDir
     * @param LoggerInterface|null $logger
     */
    public function __construct($auth = null, $ipLookupConfig = null, $cacheDir = null, LoggerInterface $logger = null)
    {
        $this->connector = HttpFactory::getHttp();

        parent::__construct($auth, $ipLookupConfig, $cacheDir, $logger);
    }

    /**
     * @return array
     */
    protected function getHeaders()
    {
        return array();
    }

    /**
     * @return array
     */
    protected function getParameters()
    {
        return array();
    }

    /**
     * Fetch data from lookup service
     */
    protected function lookup()
    {
        $url = $this->getUrl();

        try {
            $response = ('post' == $this->method) ?
                $this->connector->post($url, $this->getParameters(), $this->getHeaders()) :
                $this->connector->get($url, $this->getHeaders());

            $this->parseResponse($response->body);
        } catch (\Exception $exception) {
            if ($this->logger) {
                $this->logger->warning('IP LOOKUP: ' . $exception->getMessage());
            }
        }
    }
}