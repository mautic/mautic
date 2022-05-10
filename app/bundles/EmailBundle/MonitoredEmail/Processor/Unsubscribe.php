<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor;

use Mautic\EmailBundle\MonitoredEmail\Exception\UnsubscriptionNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription\Parser;
use Mautic\EmailBundle\MonitoredEmail\Search\ContactFinder;
use Mautic\EmailBundle\Swiftmailer\Transport\UnsubscriptionProcessorInterface;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class Unsubscribe implements ProcessorInterface
{
    /**
     * @var \Swift_Transport
     */
    private $transport;

    /**
     * @var ContactFinder
     */
    private $contactFinder;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Message
     */
    private $message;

    /**
     * @var DoNotContactModel
     */
    private $doNotContact;

    /**
     * Bounce constructor.
     */
    public function __construct(
        \Swift_Transport $transport,
        ContactFinder $contactFinder,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        DoNotContactModel $doNotContact
    ) {
        $this->transport     = $transport;
        $this->contactFinder = $contactFinder;
        $this->translator    = $translator;
        $this->logger        = $logger;
        $this->doNotContact  = $doNotContact;
    }

    /**
     * @return bool
     */
    public function process(Message $message)
    {
        $this->message = $message;
        $this->logger->debug('MONITORED EMAIL: Processing message ID '.$this->message->id.' for an unsubscription');

        $unsubscription = false;

        // Does the transport have special handling like Amazon SNS
        if ($this->transport instanceof UnsubscriptionProcessorInterface) {
            try {
                $unsubscription = $this->transport->processUnsubscription($this->message);
            } catch (UnsubscriptionNotFound $exception) {
                // Attempt to parse a unsubscription the standard way
            }
        }

        if (!$unsubscription) {
            try {
                $parser         = new Parser($message);
                $unsubscription = $parser->parse();
            } catch (UnsubscriptionNotFound $exception) {
                // No stat found so bail as we won't consider this a reply
                $this->logger->debug('MONITORED EMAIL: Unsubscription email was not found');

                return false;
            }
        }

        $searchResult = $this->contactFinder->find($unsubscription->getContactEmail(), $unsubscription->getUnsubscriptionAddress());
        if (!$contacts = $searchResult->getContacts()) {
            // No contacts found so bail
            return false;
        }

        $stat    = $searchResult->getStat();
        $channel = 'email';
        if ($stat && $email = $stat->getEmail()) {
            // We know the email ID so set it to append to the the DNC record
            $channel = ['email' => $email->getId()];
        }

        $comments = $this->translator->trans('mautic.email.bounce.reason.unsubscribed');
        foreach ($contacts as $contact) {
            $this->doNotContact->addDncForContact($contact->getId(), $channel, DoNotContact::UNSUBSCRIBED, $comments);
        }

        return true;
    }
}
