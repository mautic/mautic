<?php

namespace Mautic\EmailBundle\Mailer\Transport;

use Mautic\EmailBundle\Mailer\Callback\AmazonCallback;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\BouncedEmail;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription\UnsubscribedEmail;
use Symfony\Component\HttpFoundation\Request;

class SesSmtpTransportExtension implements CallbackTransportInterface, TransportExtensionInterface, BounceProcessorInterface, UnsubscriptionProcessorInterface
{
    private AmazonCallback $amazonCallback;

    public function __construct(AmazonCallback $amazonCallback)
    {
        $this->amazonCallback = $amazonCallback;
    }

    /** @return string[] */
    public function getSupportedSchemes(): array
    {
        return ['amazon', 'ses'];
    }

    public function processCallbackRequest(Request $request): void
    {
        $this->amazonCallback->processCallbackRequest($request);
    }

    public function processBounce(Message $message): BouncedEmail
    {
        return $this->amazonCallback->processBounce($message);
    }

    public function processUnsubscription(Message $message): UnsubscribedEmail
    {
        return $this->amazonCallback->processUnsubscription($message);
    }
}
