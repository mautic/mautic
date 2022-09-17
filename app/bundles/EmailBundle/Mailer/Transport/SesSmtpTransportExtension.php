<?php

namespace Mautic\EmailBundle\Mailer\Transport;

use Mautic\EmailBundle\Mailer\Callback\AmazonCallback;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Symfony\Component\HttpFoundation\Request;

class SesSmtpTransportExtension implements CallbackTransportInterface, TransportExtensionInterface, BounceProcessorInterface
{
    private AmazonCallback $amazonCallback;

    public function __construct(AmazonCallback $amazonCallback)
    {
        $this->amazonCallback = $amazonCallback;
    }

    public function getSupportedSchemes(): array
    {
        return ['amazon', 'ses'];
    }

    public function processCallbackRequest(Request $request): void
    {
        $this->amazonCallback->processCallbackRequest($request);
    }

    public function processBounce(Message $message)
    {
        $this->amazonCallback->processBounce($message);
    }

    public function processUnsubscription(Message $message)
    {
        $this->amazonCallback->processUnsubscription($message);
    }
}
