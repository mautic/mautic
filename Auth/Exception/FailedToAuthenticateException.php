<?php


namespace MauticPlugin\IntegrationsBundle\Auth\Exception;

use Throwable;

class FailedToAuthenticateException extends \Exception
{
    /**
     * @var string
     */
    private $integration;

    /**
     * FailedToAuthorizeException constructor.
     *
     * @param string         $integration
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $integration, string $message = "", int $code = 0, Throwable $previous = null)
    {
        $this->integration = $integration;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getIntegration(): string
    {
        return $this->integration;
    }
}