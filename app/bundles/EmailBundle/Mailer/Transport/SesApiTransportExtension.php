<?php

namespace Mautic\EmailBundle\Mailer\Transport;

use Mautic\EmailBundle\Mailer\Callback\AmazonCallback;
use Symfony\Component\HttpFoundation\Request;

class SesApiTransportExtension implements CallbackTransportInterface, TransportExtensionInterface
{
    private AmazonCallback $amazonCallback;

    public function __construct(AmazonCallback $amazonCallback)
    {
        $this->amazonCallback = $amazonCallback;
    }

    public function getSupportedSchemes(): array
    {
        return ['ses+api', 'amazon_api'];
    }

    public function processCallbackRequest(Request $request): void
    {
        $this->amazonCallback->processCallbackRequest($request);
    }
}
