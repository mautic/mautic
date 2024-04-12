<?php

namespace Mautic\CoreBundle\Helper;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MaxMindDoNotSellDownloadHelper
{
    /**
     * @const REMOTE_DATA
     */
    public const REMOTE_DATA = 'https://api.maxmind.com/privacy/exclusions';

    /**
     * @var array<string>
     */
    private array $auth;

    /**
     * @var string
     */
    private $listPath;

    public function __construct(
        $auth,
        private LoggerInterface $logger,
        private HttpClientInterface $httpClient,
        CoreParametersHelper $coreParametersHelper
    ) {
        $this->auth       = explode(':', (string) $auth, 2);
        $this->listPath   = $coreParametersHelper->get('maxmind_do_not_sell_list_path') ?? '';
    }

    public function downloadRemoteDataStore(): bool
    {
        if (empty($this->getUser()) || empty($this->getPassword())) {
            $this->logger->error('Missing user ID or license key for MaxMind');

            return false;
        }

        if (empty($this->listPath)) {
            $this->logger->error('Missing file path');

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
        } catch (\Throwable $e) {
            $this->logger->error('Failed to get content from remote Do Not Sell data: '.$e->getMessage());

            return false;
        }

        return (bool) file_put_contents($this->getLocalDataStoreFilepath(), $responseContent);
    }

    /**
     * Service URL.
     */
    public function getRemoteDataStoreDownloadUrl(): string
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
        return $this->listPath;
    }

    private function getUser(): string
    {
        return $this->getAuthPart(0);
    }

    protected function getPassword(): string
    {
        return $this->getAuthPart(1);
    }

    /**
     * @param int $position
     */
    private function getAuthPart($position): string
    {
        if (array_key_exists($position, $this->auth)) {
            return $this->auth[$position];
        }

        return '';
    }
}
