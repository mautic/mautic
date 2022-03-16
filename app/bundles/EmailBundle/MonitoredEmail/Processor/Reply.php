<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor;

use Doctrine\ORM\EntityNotFoundException;
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
use Mautic\LeadBundle\Tracker\ContactTracker;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Reply implements ProcessorInterface
{
    /**
     * @var StatRepository
     */
    private $statRepo;

    /**
     * @var ContactFinder
     */
    private $contactFinder;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ContactTracker
     */
    private $contactTracker;

    public function __construct(
        StatRepository $statRepository,
        ContactFinder $contactFinder,
        LeadModel $leadModel,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        ContactTracker $contactTracker
    ) {
        $this->statRepo         = $statRepository;
        $this->contactFinder    = $contactFinder;
        $this->leadModel        = $leadModel;
        $this->dispatcher       = $dispatcher;
        $this->logger           = $logger;
        $this->contactTracker   = $contactTracker;
    }

    public function process(Message $message)
    {
        $this->logger->debug('MONITORED EMAIL: Processing message ID '.$message->id.' for a reply');

        try {
            $parser       = new Parser($message);
            $repliedEmail = $parser->parse();
        } catch (ReplyNotFound $exception) {
            // No hash found so bail as we won't consider this a reply
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

        $this->createReply($stat, $message->id);
        $this->dispatchEvent($stat);

        $this->statRepo->clear();
        $this->leadModel->clearEntities();
    }

    /**
     * @param string $trackingHash
     * @param string $messageId
     */
    public function createReplyByHash($trackingHash, $messageId)
    {
        /** @var Stat|null $stat */
        $stat = $this->statRepo->findOneBy(['trackingHash' => $trackingHash]);

        if (null === $stat) {
            throw new EntityNotFoundException("Email Stat with tracking hash {$trackingHash} was not found");
        }

        $stat->setIsRead(true);

        if (null === $stat->getDateRead()) {
            $stat->setDateRead(new \DateTime());
        }

        $this->createReply($stat, $messageId);

        $contact = $stat->getLead();

        if ($contact) {
            $this->dispatchEvent($stat);
        }
    }

    /**
     * @param string $messageId
     */
    protected function createReply(Stat $stat, $messageId)
    {
        $replies = $stat->getReplies()->filter(
            function (EmailReply $reply) use ($messageId) {
                return $reply->getMessageId() === $messageId;
            }
        );

        if (!$replies->count()) {
            $emailReply = new EmailReply($stat, $messageId);
            $stat->addReply($emailReply);
            $this->statRepo->saveEntity($stat);
        }
    }

    /**
     * Clean the email for comparison.
     *
     * @param string $email
     *
     * @return string
     */
    protected function cleanEmail($email)
    {
        return strtolower(preg_replace("/[^a-z0-9\.@]/i", '', $email));
    }

    private function dispatchEvent(Stat $stat)
    {
        if ($this->dispatcher->hasListeners(EmailEvents::EMAIL_ON_REPLY)) {
            $this->contactTracker->setTrackedContact($stat->getLead());

            $event = new EmailReplyEvent($stat);
            $this->dispatcher->dispatch(EmailEvents::EMAIL_ON_REPLY, $event);
            unset($event);
        }
    }
}
