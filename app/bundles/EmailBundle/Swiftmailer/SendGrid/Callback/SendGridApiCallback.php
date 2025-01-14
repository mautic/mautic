<?php

namespace Mautic\EmailBundle\Swiftmailer\SendGrid\Callback;

use Mautic\EmailBundle\Model\TransportCallback;
use Symfony\Component\HttpFoundation\Request;

class SendGridApiCallback
{
    /**
     * @var TransportCallback
     */
    private $transportCallback;

    public function __construct(TransportCallback $transportCallback)
    {
        $this->transportCallback = $transportCallback;
    }

    public function processCallbackRequest(Request $request)
    {
        $responseItems = new ResponseItems($request);
        foreach ($responseItems as $item) {
            $this->transportCallback->addFailureByAddress($item->getEmail(), $item->getReason(), $item->getDncReason());
        }
    }
}
