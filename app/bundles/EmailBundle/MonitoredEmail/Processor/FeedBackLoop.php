<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\MonitoredEmail\Processor;

use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\FeedBackLoop\Parser;
use Mautic\EmailBundle\MonitoredEmail\Search\ContactFinder;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FeedBackLoop implements InterfaceProcessor
{
    /**
     * @var ContactFinder
     */
    protected $contactFinder;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Message
     */
    protected $message;

    /**
     * FeedBackLoop constructor.
     *
     * @param ContactFinder       $contactFinder
     * @param LeadModel           $leadModel
     * @param TranslatorInterface $translator
     * @param LoggerInterface     $logger
     */
    public function __construct(
        ContactFinder $contactFinder,
        LeadModel $leadModel,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->contactFinder = $contactFinder;
        $this->leadModel     = $leadModel;
        $this->translator    = $translator;
        $this->logger        = $logger;
    }

    /**
     * @return bool
     */
    public function process(Message $message)
    {
        $this->message = $message;
        $this->logger->debug('MONITORED EMAIL: Processing message ID '.$this->message->id.' for a feedback loop report');

        if (!$this->isApplicable()) {
            return false;
        }

        $parser = new Parser($this->message);
        if (!$contactEmail = $parser->parse()) {
            // A contact email was not found in the FBL report
            return false;
        }

        $this->logger->debug('MONITORED EMAIL: Found '.$contactEmail.' in feedback loop report');

        $searchResult = $this->contactFinder->find($contactEmail);
        if (!$contacts = $searchResult->getContacts()) {
            return false;
        }

        $comments = $this->translator->trans('mautic.email.bounce.reason.spam');
        foreach ($contacts as $contact) {
            $this->leadModel->addDncForLead($contact, 'email', $comments, DoNotContact::UNSUBSCRIBED);
        }

        return true;
    }

    /**
     * @return int
     */
    protected function isApplicable()
    {
        return preg_match('/.*feedback-type: abuse.*/is', $this->message->fblReport);
    }
}
