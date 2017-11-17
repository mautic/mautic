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

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\EmailReply;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Event\EmailReplyEvent;
use Mautic\EmailBundle\MonitoredEmail\Exception\ReplyNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Reply\Parser;
use Mautic\EmailBundle\MonitoredEmail\Search\ContactFinder;
use Mautic\LeadBundle\Model\LeadModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Reply implements ProcessorInterface
{
    /**
     * @var StatRepository
     */
    protected $statRepo;

    /**
     * @var ContactFinder
     */
    protected $contactFinder;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Message
     */
    protected $message;

    /**
     * Reply constructor.
     *
     * @param StatRepository           $statRepository
     * @param ContactFinder            $contactFinder
     * @param LeadModel                $leadModel
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface          $logger
     */
    public function __construct(
        StatRepository $statRepository,
        ContactFinder $contactFinder,
        LeadModel $leadModel,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger
    ) {
        $this->statRepo      = $statRepository;
        $this->contactFinder = $contactFinder;
        $this->leadModel     = $leadModel;
        $this->dispatcher    = $dispatcher;
        $this->logger        = $logger;
    }

    /**
     * @param $mailId
     * @param $refid
     */
    public function process(Message $message)
    {
        $this->message = $message;

        $this->logger->debug('MONITORED EMAIL: Processing message ID '.$this->message->id.' for a reply');

        try {
            $parser       = new Parser($message);
            $repliedEmail = $parser->parse();
        } catch (ReplyNotFound $exception) {
            // No stat found so bail as we won't consider this a reply
            $this->logger->debug('MONITORED EMAIL: No hash ID found in the email body');

            return;
        }

        $hashId = $repliedEmail->getStatHash();
        $result = $this->contactFinder->findByHash($hashId);
        if (!$stat = $result->getStat()) {
            // No stat found so bail as we won't consider this a reply
            $this->logger->debug('MONITORED EMAIL: Stat not found');

            return;
        }

        // A stat has been found so let's compare to the From address for the contact to prevent false positives
        $contactEmail = $this->cleanEmail($stat->getLead()->getEmail());
        $fromEmail    = $this->cleanEmail($repliedEmail->getFromAddress());
        if ($contactEmail !== $fromEmail) {
            // We can't reliably assume this email was from the originating contact
            $this->logger->debug('MONITORED EMAIL: '.$contactEmail.' != '.$fromEmail.' so cannot confirm match');

            return;
        }

        $this->createReply($stat);

        if ($this->dispatcher->hasListeners(EmailEvents::EMAIL_ON_REPLY)) {
            $this->leadModel->setSystemCurrentLead($stat->getLead());

            $event = new EmailReplyEvent($stat);
            $this->dispatcher->dispatch(EmailEvents::EMAIL_ON_REPLY, $event);
            unset($event);
        }

        $this->statRepo->clear();
        $this->leadModel->clearEntities();
    }

    /**
     * @param $stat
     */
    protected function createReply(Stat $stat)
    {
        $replies = $stat->getReplies()->filter(
            function (EmailReply $reply) {
                return $reply->getMessageId() === $this->message->id;
            }
        );

        if (!$replies->count()) {
            $emailReply = new EmailReply($stat, $this->message->id);
            $stat->addReply($emailReply);
            $this->statRepo->saveEntity($stat);
        }
    }

    /**
     * Clean the email for comparison.
     *
     * @param $email
     *
     * @return mixed
     */
    protected function cleanEmail($email)
    {
        return strtolower(preg_replace("/[^a-z0-9\.@]/i", '', $email));
    }
}
