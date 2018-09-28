<?php


namespace MauticPlugin\IntegrationsBundle\Auth\Provider;


use GuzzleHttp\ClientInterface;

interface AuthProviderInterface
{
    /**
     * @return string
     */
    public function getAuthType(): string;

    /**
     * @param mixed $credentials
     *
     * @return ClientInterface
     */
    public function getClient($credentials): ClientInterface;
}