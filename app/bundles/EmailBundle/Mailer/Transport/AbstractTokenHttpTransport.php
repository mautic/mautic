<?php

namespace Mautic\EmailBundle\Mailer\Transport;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/***
 * This class is inspired of
 * https://github.com/symfony/mailer/blob/6.1/Transport/AbstractHttpTransport.php
 * The final implementation to send must inspire from
 * https://github.com/symfony/mailer/blob/6.1/Transport/AbstractApiTransport.php
 */

abstract class AbstractTokenHttpTransport extends AbstractTokenArrayTransport implements TokenTransportInterface
{
    /**
     * @var string|null
     */
    protected $host;

    /**
     * @var int|null
     */
    protected $port;

    /**
     * @var HttpClientInterface|null
     */
    protected $client;

    /**
     * @var string|null
     */
    private $username;

    /**
     * @var string|null
     */
    private $password;

    /**
     * @var string|null
     */
    private $apiKey;

    /**
     * @return $this
     */
    public function setHost(?string $host): static
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return $this
     */
    public function setPort(?int $port): static
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @param string|null $username
     */
    public function setUsername($username): void
    {
        $this->username = $username;
    }

    /**
     * @return string|null
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string|null $password
     */
    public function setPassword($password): void
    {
        $this->password = $password;
    }

    /**
     * @return string|null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string|null
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param string|null $apiKey
     *
     * @return AbstractTokenHttpTransport
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Return the URL for the API endpoint.
     *
     * @return string
     */
    abstract protected function getApiEndpoint();

    abstract protected function doSendHttp(SentMessage $message): ResponseInterface;

    protected function doSend(SentMessage $message): void
    {
        $response = null;
        try {
            $response = $this->doSendHttp($message);
            $message->appendDebug($response->getInfo('debug') ?? '');
        } catch (HttpTransportException $e) {
            $e->appendDebug($e->getResponse()->getInfo('debug') ?? '');

            throw $e;
        }
    }

    public function __construct(HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        $this->client = $client;
        if (null === $client) {
            if (!class_exists(HttpClient::class)) {
                throw new \LogicException(sprintf('You cannot use "%s" as the HttpClient component is not installed. Try running "composer require symfony/http-client".', __CLASS__));
            }

            $this->client = HttpClient::create();
        }

        parent::__construct($dispatcher, $logger);
    }
}
