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

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\MonitoredEmail\Exception\BounceNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\BouncedEmail;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Parser;
use Mautic\EmailBundle\MonitoredEmail\Search\ContactFinder;
use Mautic\EmailBundle\Swiftmailer\Transport\BounceProcessorInterface;
use Mautic\LeadBundle\Model\LeadModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class Bounce implements ProcessorInterface
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
     * @var StatRepository
     */
    protected $statRepository;

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
    protected $bouncerAddress;

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
     * @param \Swift_Transport    $transport
     * @param ContactFinder       $contactFinder
     * @param StatRepository      $statRepository
     * @param LeadModel           $leadModel
     * @param TranslatorInterface $translator
     * @param LoggerInterface     $logger
     */
    public function __construct(
        \Swift_Transport $transport,
        ContactFinder $contactFinder,
        StatRepository $statRepository,
        LeadModel $leadModel,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->transport      = $transport;
        $this->contactFinder  = $contactFinder;
        $this->statRepository = $statRepository;
        $this->leadModel      = $leadModel;
        $this->translator     = $translator;
        $this->logger         = $logger;
    }

    /**
     * @param Message $message
     *
     * @return bool
     */
    public function process(Message $message)
    {
        $this->message = $message;
        $bounce        = false;

        $this->logger->debug('MONITORED EMAIL: Processing message ID '.$this->message->id.' for a bounce');

        // Does the transport have special handling such as Amazon SNS?
        if ($this->transport instanceof BounceProcessorInterface) {
            try {
                $bounce = $this->transport->processBounce($this->message);
            } catch (BounceNotFound $exception) {
                // Attempt to parse a bounce the standard way
            }
        }

        if (!$bounce) {
            if (!$this->isApplicable()) {
                return false;
            }

            try {
                $bounce = (new Parser($this->message))->parse($this->bouncerAddress);
            } catch (BounceNotFound $exception) {
                return false;
            }
        }

        $searchResult = $this->contactFinder->find($bounce->getContactEmail(), $bounce->getBounceAddress());
        if (!$contacts = $searchResult->getContacts()) {
            // No contacts found so bail
            return false;
        }

        $stat    = $searchResult->getStat();
        $channel = 'email';
        if ($stat) {
            // Update stat entry
            $this->updateStat($stat, $bounce);

            // We know the email ID so set it to append to the the DNC record
            $channel = ['email' => $stat->getEmail()->getId()];
        }

        $comments = $this->translator->trans('mautic.email.bounce.reason.'.$bounce->getRuleCategory());
        foreach ($contacts as $contact) {
            $this->leadModel->addDncForLead($contact, $channel, $comments);
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function isApplicable()
    {
        foreach ($this->message->to as $to => $name) {
            if (strpos($to, '+bounce') !== false) {
                $this->bouncerAddress = $to;

                return true;
            }
        }

        return false;
    }

    /**
     * @param Stat         $stat
     * @param BouncedEmail $bouncedEmail
     */
    protected function updateStat(Stat $stat, BouncedEmail $bouncedEmail)
    {
        $dtHelper    = new DateTimeHelper();
        $openDetails = $stat->getOpenDetails();

        $openDetails['bounces'][] = [
            'datetime' => $dtHelper->toUtcString(),
            'reason'   => $bouncedEmail->getRuleCategory(),
            'code'     => $bouncedEmail->getRuleNumber(),
            'type'     => $bouncedEmail->getType(),
        ];

        $stat->setOpenDetails($openDetails);

        $retryCount = $stat->getRetryCount();
        ++$retryCount;
        $stat->setRetryCount($retryCount);

        if ($fail = $bouncedEmail->isFinal() || $retryCount >= 5) {
            $stat->setIsFailed(true);
        }

        $this->statRepository->saveEntity($stat);
    }
}
