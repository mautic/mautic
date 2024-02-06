<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor;

use Doctrine\ORM\EntityNotFoundException;
use Mautic\CoreBundle\Helper\EmailAddressHelper;
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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Reply implements ProcessorInterface
{
    public function __construct(
        private StatRepository $statRepo,
        private ContactFinder $contactFinder,
        private LeadModel $leadModel,
        private EventDispatcherInterface $dispatcher,
        private LoggerInterface $logger,
        private ContactTracker $contactTracker,
        private EmailAddressHelper $addressHelper
    ) {
    }

    public function process(Message $message): void
    {
        $this->logger->debug('MONITORED EMAIL: Processing message ID '.$message->id.' for a reply');

        try {
            $parser       = new Parser($message);
            $repliedEmail = $parser->parse();
        } catch (ReplyNotFound) {
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
        $possibleFromEmails = $this->addressHelper->getVariations($stat->getLead()->getEmail());
        $fromEmail          = $this->addressHelper->cleanEmail($repliedEmail->getFromAddress());

        if (!in_array($fromEmail, $possibleFromEmails)) {
            // We can't reliably assume this email was from the originating contact
            $this->logger->debug('MONITORED EMAIL: '.implode(', ', $possibleFromEmails).' != '.$fromEmail.' so cannot confirm match');

            return;
        }

        $this->createReply($stat, $message->id);
        $this->dispatchEvent($stat);

        if (null !== $stat->getLead()) {
            $this->leadModel->getRepository()->detachEntity($stat->getLead());
        }
        $this->statRepo->detachEntity($stat);
    }

    /**
     * @param string $trackingHash
     * @param string $messageId
     */
    public function createReplyByHash($trackingHash, $messageId): void
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
            fn (EmailReply $reply): bool => $reply->getMessageId() === $messageId
        );

        if (!$replies->count()) {
            $emailReply = new EmailReply($stat, $messageId);
            $stat->addReply($emailReply);
            $this->statRepo->saveEntity($stat);
        }
    }

    private function dispatchEvent(Stat $stat): void
    {
        if ($this->dispatcher->hasListeners(EmailEvents::EMAIL_ON_REPLY)) {
            $this->contactTracker->setTrackedContact($stat->getLead());

            $event = new EmailReplyEvent($stat);
            $this->dispatcher->dispatch($event, EmailEvents::EMAIL_ON_REPLY);
            unset($event);
        }
    }
}
