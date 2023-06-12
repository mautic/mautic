<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Mailer\Transport;

class SmtpTransportExtension implements TransportExtensionInterface
{
    public function getSupportedSchemes(): array
    {
        return ['smtp'];
    }
}
