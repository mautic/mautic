<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor;

use Mautic\EmailBundle\MonitoredEmail\Exception\FeedbackLoopNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop\Parser;
use Mautic\EmailBundle\MonitoredEmail\Search\ContactFinder;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackLoop implements ProcessorInterface
{
    private ?Message $message = null;

    public function __construct(
        private ContactFinder $contactFinder,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
        private DoNotContactModel $doNotContact,
    ) {
    }

    public function process(Message $message): bool
    {
        $this->message = $message;
        $this->logger->debug('MONITORED EMAIL: Processing message ID '.$this->message->id.' for a feedback loop report');

        if (!$this->isApplicable()) {
            return false;
        }

        try {
            $parser = new Parser($this->message);
            if (!$contactEmail = $parser->parse()) {
                // A contact email was not found in the FBL report
                return false;
            }
        } catch (FeedbackLoopNotFound) {
            return false;
        }

        $this->logger->debug('MONITORED EMAIL: Found '.$contactEmail.' in feedback loop report');

        $searchResult = $this->contactFinder->find($contactEmail);
        if (!$contacts = $searchResult->getContacts()) {
            return false;
        }

        $comments = $this->translator->trans('mautic.email.bounce.reason.spam');
        foreach ($contacts as $contact) {
            $this->doNotContact->addDncForContact($contact->getId(), 'email', DoNotContact::UNSUBSCRIBED, $comments);
        }

        return true;
    }

    protected function isApplicable(): int|bool
    {
        return preg_match('/.*feedback-type: abuse.*/is', $this->message->fblReport);
    }
}
