<?php

namespace Mautic\SmsBundle\Integration\Twilio;

use Mautic\SmsBundle\Callback\CallbackInterface;
use Mautic\SmsBundle\Exception\NumberNotFoundException;
use Mautic\SmsBundle\Helper\ContactHelper;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twilio\Exceptions\ConfigurationException;

class TwilioCallback implements CallbackInterface
{
    public function __construct(
        private ContactHelper $contactHelper,
        private Configuration $configuration,
    ) {
    }

    public function getTransportName(): string
    {
        return 'twilio';
    }

    /**
     * @throws NumberNotFoundException
     */
    public function getContacts(Request $request): \Doctrine\Common\Collections\ArrayCollection
    {
        $this->validateRequest($request->request);

        $number = $request->get('From');

        return $this->contactHelper->findContactsByNumber($number);
    }

    public function getMessage(Request $request): string
    {
        $this->validateRequest($request->request);

        return trim($request->get('Body'));
    }

    /**
     * @param InputBag<bool|float|int|string> $request
     */
    private function validateRequest(InputBag $request): void
    {
        try {
            $accountSid = $this->configuration->getAccountSid();
        } catch (ConfigurationException) {
            // Not published or not configured
            throw new NotFoundHttpException();
        }

        // Validate this is a request from Twilio
        if ($accountSid !== $request->get('AccountSid')) {
            throw new BadRequestHttpException();
        }

        // Who is the message from?
        $number = $request->get('From');
        if (empty($number)) {
            throw new BadRequestHttpException();
        }

        // What did they say?
        $message = trim($request->get('Body'));
        if (empty($message)) {
            throw new BadRequestHttpException();
        }
    }
}
