<?php

namespace Mautic\EmailBundle\Mailer\Transport;

interface TransportExtensionInterface
{
    /**
     * @return array<int, string>
     */
    public function getSupportedSchemes(): array;
}
