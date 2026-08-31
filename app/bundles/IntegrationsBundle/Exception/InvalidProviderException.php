<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Exception;

final class InvalidProviderException extends \Exception
{
    public function __construct(string $provider, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('The requested auth provider (%s) has not been registered.', $provider), $code, $previous);
    }
}
