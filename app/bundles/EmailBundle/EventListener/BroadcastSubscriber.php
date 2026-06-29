<?php

namespace Mautic\EmailBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelBroadcastEvent;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BroadcastSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EmailModel $model,
        private EntityManager $em,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ChannelEvents::CHANNEL_BROADCAST => ['onBroadcast', 0],
        ];
    }

    public function onBroadcast(ChannelBroadcastEvent $event): void
    {
        if (!$event->checkContext('email')) {
            return;
        }

        // Get list of published broadcasts or broadcast if there is only a single ID
        $emails = $this->model->getRepository()->getPublishedBroadcastsIterable($event->getId());

        foreach ($emails as $email) {
            // Reset per-email variables from event defaults
            $maxThreads = $event->getMaxThreads();
            $threadId   = $event->getThreadId();
            $limit      = $event->getLimit();
            $batch      = $event->getBatch();

            $totalPendingCount = null;

            $emailEntity = $email;
            if ($emailEntity->isVariant(true)) {
                continue;
            }

            // A/B testing logic — test round and winner determination
            if ($emailEntity->isEnableAbTest() && !$emailEntity->isWinner()) {
                $totalPendingCount         = $this->model->getPendingLeads($emailEntity, null, true);
                $totalLeadCountForVariants = $emailEntity->getVariantsPendingCount($totalPendingCount);
                $emailEntity->setPendingCount($totalPendingCount);

                if ($emailEntity->waitingToDetermineWinner($totalLeadCountForVariants)) {
                    continue;
                }

                if ($emailEntity->waitingToSendTestsEmails($totalLeadCountForVariants)) {
                    // only 1 thread for AB test sends
                    if ($threadId && $threadId > 1) {
                        continue;
                    }

                    // test sending without batch and threads
                    $batch      = null;
                    $maxThreads = null;
                    $threadId   = null;

                    $limit = $this->getLimitForABTest($limit, $emailEntity, $totalLeadCountForVariants);
                    $this->setStartDateOfABTesting($emailEntity);
                }
            }

            [$sentCount, $failedCount, $failedRecipientsByList] = $this->model->sendEmailToLists(
                $emailEntity,
                null,
                $limit,
                $batch,
                $event->getOutput(),
                $event->getMinContactIdFilter(),
                $event->getMaxContactIdFilter(),
                $maxThreads,
                $threadId
            );

            if ($emailEntity->shouldCheckForUnpublishEmail()) {
                $isNotParallelSending = !$event->getThreadId() || 1 === $event->getThreadId();
                $totalPendingCount ??= $this->model->getPendingLeads($emailEntity, null, true);
                // only If no pending and nothing was sent
                if ($isNotParallelSending && !$totalPendingCount && !$sentCount) {
                    $emailEntity->setIsPublished(false);
                    $this->model->saveEntity($emailEntity);
                    $event->getOutput()->writeln('Email "'.$emailEntity->getName().'" has been unpublished as there are no more pending contacts to send to.');
                }
            }

            $event->setResults(
                $this->translator->trans('mautic.email.email').': '.$emailEntity->getName(),
                $sentCount,
                $failedCount,
                $failedRecipientsByList
            );
            $this->em->detach($emailEntity);
        }
    }

    private function setStartDateOfABTesting(Email $emailEntity): void
    {
        if (!$emailEntity->getVariantSentCount(true)) {
            $dateTimeHelper = new DateTimeHelper();
            $this->model->getRepository()->resetVariants(
                $emailEntity->getRelatedEntityIds(),
                $dateTimeHelper->toUtcString()
            );

            // Update in-memory entity so getEmailSettings sees variant_start_date
            $emailEntity->setVariantStartDate($dateTimeHelper->getDateTime());
        }
    }

    private function getLimitForABTest(int $limit, Email $emailEntity, int $totalLeadCountForVariants): int
    {
        $diff = ($emailEntity->getVariantSentCount(true) + $limit) - $totalLeadCountForVariants;
        if ($diff > 0) {
            $limit -= $diff;
        }

        return max(0, $limit);
    }
}
