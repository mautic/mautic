<?php

namespace MauticPlugin\IntegrationsBundle\Exception;

class InvalidProviderException extends \Exception
{
    public function __construct($provider, $code = 0, \Exception $previous = null)
    {
        parent::__construct(sprintf('The requested auth provider (%s) has not been registered.', $provider), $code, $previous);
    }
}
