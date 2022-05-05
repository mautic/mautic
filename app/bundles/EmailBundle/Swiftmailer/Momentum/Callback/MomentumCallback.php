<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Callback;

use Mautic\EmailBundle\Model\TransportCallback;
use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use Mautic\LeadBundle\Entity\DoNotContact;
use Symfony\Component\HttpFoundation\Request;

final class MomentumCallback implements MomentumCallbackInterface
{
    /**
     * @var TransportCallback
     */
    private $transportCallback;

    /**
     * MomentumCallback constructor.
     */
    public function __construct(TransportCallback $transportCallback)
    {
        $this->transportCallback = $transportCallback;
    }

    public function processCallbackRequest(Request $request)
    {
        $responseItems = new ResponseItems($request);

        foreach ($responseItems as $item) {
            if ($statHash = $item->getStatHash()) {
                $this->transportCallback->addFailureByHashId($statHash, $item->getReason(), $item->getDncReason());
            } else {
                $this->transportCallback->addFailureByAddress($item->getEmail(), $item->getReason(), $item->getDncReason());
            }
        }
    }

    /**
     * @return mixed|void
     */
    public function processImmediateFeedback(\Swift_Mime_SimpleMessage $message, array $response)
    {
        if (!empty($response['errors'][0]['code']) && 1902 == (int) $response['errors'][0]['code']) {
            $comments     = $response['errors'][0]['description'];
            $metadata     = ($message instanceof MauticMessage) ? $message->getMetadata() : [];
            $emailAddress = key($message->getTo());

            if (isset($metadata[$emailAddress]) && isset($metadata[$emailAddress]['leadId'])) {
                $emailId = (!empty($metadata[$emailAddress]['emailId'])) ? $metadata[$emailAddress]['emailId'] : null;
                $this->transportCallback->addFailureByContactId($metadata[$emailAddress]['leadId'], $comments, DoNotContact::BOUNCED, $emailId);
            }
        }
    }
}
