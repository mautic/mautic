<?php

namespace Mautic\CoreBundle\Helper;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MaxMindDoNotSellDownloadHelper
{
    /**
     * @const REMOTE_DATA
     */
    const REMOTE_DATA = 'https://api.maxmind.com/privacy/exclusions';

    /**
     * @const LOCAL_FILENAME
     */
    const LOCAL_FILENAME = 'MaxMindDoNotSell.json';

    /**
     * @const CACHE_DIR
     */
    const CACHE_DIR = 'ip_data';

    /**
     * @var string
     */
    private $auth;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $httpClient;

    public function __construct($auth, $cacheDir, LoggerInterface $logger, HttpClientInterface $httpClient)
    {
        $this->cacheDir   = $cacheDir;
        $this->logger     = $logger;
        $this->auth       = explode(':', $auth, 2);
        $this->httpClient = $httpClient;
    }

    /**
     * @return bool
     */
    public function downloadRemoteDataStore()
    {
        if (empty($this->getUser()) || empty($this->getPassword())) {
            $this->logger->error('Missing user ID or license key for MaxMind');

            return false;
        }

        $httpClientOptions = [
            'auth_basic' => [$this->getUser(), $this->getPassword()],
        ];

        try {
            $response = $this->httpClient->request('GET',
                $this->getRemoteDataStoreDownloadUrl(),
                $httpClientOptions);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to fetch remote Do Not Sell data: '.$e->getMessage());

            return false;
        }

        try {
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                $this->logger->error('Wrong status code for Do Not Sell data: '.$response->getStatusCode());

                return false;
            }
            $responseContent = $response->getContent();
        } catch (ExceptionInterface $e) {
            $this->logger->error('Failed to get content from remote Do Not Sell data: '.$e->getMessage());

            return false;
        }

        return (bool) file_put_contents($this->getLocalDataStoreFilepath(), $responseContent);
    }

    /**
     * Service URL.
     *
     * @return string
     */
    public function getRemoteDataStoreDownloadUrl()
    {
        return self::REMOTE_DATA;
    }

    /**
     * Filepath to the local file.
     *
     * @return string
     */
    public function getLocalDataStoreFilepath()
    {
        return $this->getDataDir().'/'.self::LOCAL_FILENAME;
    }

    /**
     * @return string
     */
    private function getDataDir()
    {
        if (null !== $this->cacheDir) {
            if (!file_exists($this->cacheDir)) {
                mkdir($this->cacheDir);
            }

            $dataDir = $this->cacheDir.'/../'.self::CACHE_DIR;

            if (!file_exists($dataDir)) {
                mkdir($dataDir);
            }

            return $dataDir;
        }

        return null;
    }

    /**
     * @return string
     */
    private function getUser()
    {
        return $this->getAuthPart(0);
    }

    /**
     * @return string
     */
    protected function getPassword()
    {
        return $this->getAuthPart(1);
    }

    /**
     * @param int $position
     *
     * @return string
     */
    private function getAuthPart($position)
    {
        if (array_key_exists($position, $this->auth)) {
            return $this->auth[$position];
        }

        return '';
    }
}
