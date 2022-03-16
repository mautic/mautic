<?php

namespace Mautic\SmsBundle\Integration\Twilio;

use Mautic\SmsBundle\Callback\CallbackInterface;
use Mautic\SmsBundle\Exception\NumberNotFoundException;
use Mautic\SmsBundle\Helper\ContactHelper;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twilio\Exceptions\ConfigurationException;

class TwilioCallback implements CallbackInterface
{
    /**
     * @var ContactHelper
     */
    private $contactHelper;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * TwilioCallback constructor.
     */
    public function __construct(ContactHelper $contactHelper, Configuration $configuration)
    {
        $this->contactHelper = $contactHelper;
        $this->configuration = $configuration;
    }

    /**
     * @return string
     */
    public function getTransportName()
    {
        return 'twilio';
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     *
     * @throws NumberNotFoundException
     */
    public function getContacts(Request $request)
    {
        $this->validateRequest($request->request);

        $number = $request->get('From');

        return $this->contactHelper->findContactsByNumber($number);
    }

    /**
     * @return string
     */
    public function getMessage(Request $request)
    {
        $this->validateRequest($request->request);

        return trim($request->get('Body'));
    }

    private function validateRequest(ParameterBag $request)
    {
        try {
            $accountSid = $this->configuration->getAccountSid();
        } catch (ConfigurationException $exception) {
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
