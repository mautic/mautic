<?php

namespace Mautic\EmailBundle\Mailer\Transport;

interface TransportExtensionInterface
{
    public function getSupportedSchemes(): array;
}
