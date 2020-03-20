<?php

namespace Mautic\CoreBundle\Helper;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class MaxMindDoNotSellDownloadHelper
{
    protected $cacheDir;
    protected $logger;
    protected $auth;
    protected $httpClient;

    public function __construct($auth, $cacheDir, LoggerInterface $logger)
    {
        $this->cacheDir   = $cacheDir;
        $this->logger     = $logger;
        $this->auth       = $auth;
    }

    /**
     * @return bool
     */
    public function downloadRemoteDataStore()
    {
        $httpClient = HttpClient::create([
            'auth_basic' => [$this->getUser(), $this->getPassword()],
        ]);

        try {
            $response = $httpClient->request('GET',
                $this->getRemoteDateStoreDownloadUrl());
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
     * @return string
     */
    public function getAttribution()
    {
        return 'This API provides a simple way to retrieve privacy exclusion requests in an automated fashion, from <a href="https://api.maxmind.com/privacy/exclusions" target="_blank">maxmind.com</a>. Databases must be downloaded and periodically updated.';
    }

    /**
     * @return string
     */
    public function getRemoteDateStoreDownloadUrl()
    {
        return 'https://api.maxmind.com/privacy/exclusions';
    }

    /**
     * @return string
     */
    public function getLocalDataStoreFilepath()
    {
        return $this->getDataDir().'/MaxMindDoNotSell.json';
    }

    /**
     * @return string
     */
    public function getDataDir()
    {
        if (null !== $this->cacheDir) {
            if (!file_exists($this->cacheDir)) {
                mkdir($this->cacheDir);
            }

            $dataDir = $this->cacheDir.'/../ip_data';

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
    protected function getUser()
    {
        return $this->auth[0];
    }

    /**
     * @return string
     */
    protected function getPassword()
    {
        return $this->auth[1];
    }
}
