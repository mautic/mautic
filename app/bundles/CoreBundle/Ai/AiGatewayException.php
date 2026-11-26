<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Ai;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * @internal
 */
#[Exclude]
final class AiGatewayException extends \RuntimeException
{
    public const int NOT_CONFIGURED = 1;

    public const int CLOUD_DISABLED = 2;

    public const int INVALID_CREDENTIAL = 3;

    public const int PROVIDER_FAILED = 4;

    public const int UNKNOWN_FEATURE = 5;

    public const int ACCESS_DENIED = 6;

    public const int INVALID_CONTEXT = 7;

    public const int INVALID_RESPONSE = 8;

    public const int PROVIDER_LOCATION_DENIED = 9;

    public function __construct(
        string $message,
        int $code,
        public readonly ?int $providerId = null,
        public readonly ?string $model = null
    )
    {
        parent::__construct($message, $code);
    }
}
