<?php

namespace Mautic\EmailBundle\Swiftmailer\SendGrid\Callback;

use Mautic\EmailBundle\Model\TransportCallback;
use Symfony\Component\HttpFoundation\Request;

class SendGridApiCallback
{
    public function __construct(private TransportCallback $transportCallback)
    {
    }

    public function processCallbackRequest(Request $request)
    {
        $responseItems = new ResponseItems($request);
        foreach ($responseItems as $item) {
            $this->transportCallback->addFailureByAddress($item->getEmail(), $item->getReason(), $item->getDncReason());
        }
    }
}
