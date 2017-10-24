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

use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\MonitoredEmail\Exception\UnsubscriptionNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription\UnsubscribedEmail;
use Mautic\EmailBundle\MonitoredEmail\Search\ContactFinder;
use Mautic\EmailBundle\Swiftmailer\Transport\InterfaceUnsubscriptionProcessor;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class Unsubscribe implements InterfaceProcessor
{
    /**
     * @var \Swift_Transport
     */
    protected $transport;

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
     * @var string
     */
    protected $unsubscriptionAddress;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Message
     */
    protected $message;

    /**
     * Bounce constructor.
     *
     * @param \Swift_Transport $transport
     * @param ContactFinder    $contactFinder
     * @param StatRepository   $statRepository
     * @param LeadModel        $leadModel
     * @param LoggerInterface  $logger
     */
    public function __construct(
        \Swift_Transport $transport,
        ContactFinder $contactFinder,
        LeadModel $leadModel,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->transport     = $transport;
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
        $this->logger->debug('MONITORED EMAIL: Processing message ID '.$this->message->id.' for an unsubscription');

        $unsubscription = false;

        // Does the transport have special handling like Amazon SNS
        if ($this->transport instanceof InterfaceUnsubscriptionProcessor) {
            try {
                $unsubscription = $this->transport->processUnsubscription($this->message);
            } catch (UnsubscriptionNotFound $exception) {
                // Attempt to parse a unsubscription the standard way
            }
        }

        if (!$unsubscription) {
            if (!$this->isApplicable()) {
                return false;
            }

            $unsubscription = new UnsubscribedEmail($this->message->fromAddress, $this->unsubscriptionAddress);
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
            $this->leadModel->addDncForLead($contact, $channel, $comments, DoNotContact::UNSUBSCRIBED);
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function isApplicable()
    {
        foreach ($this->message->to as $to => $name) {
            if (strpos($to, '+unsubscribe') !== false) {
                $this->unsubscriptionAddress = $to;

                return true;
            }
        }

        return false;
    }
}
