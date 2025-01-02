<?php

namespace Mautic\EmailBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelBroadcastEvent;
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
        if (!$event->checkContext('email') && !$event->checkContext('email-abtest')) {
            return;
        }

        $limit      = $event->getLimit();
        $batch      = $event->getBatch();

        // Get list of published broadcasts or broadcast if there is only a single ID
        $emails = $this->model->getRepository()->getPublishedBroadcastsIterable($event->getId());

        foreach ($emails as $email) {
            $emailEntity                                        = $email;
            if ($emailEntity->isVariant(true)) {
                continue;
            }

            if ($emailEntity->isVariant(true)) {
                continue;
            }

            // AB tests send with separate channel
            if (!$emailEntity->isEnableAbTest() && $event->checkContext('email-abtest')) {
                continue;
            }

            // winner send with standard sending
            if ($emailEntity->isWinner() && $event->checkContext('email-abtest')) {
                continue;
            }

            // is a/b testings
            if ($emailEntity->isEnableAbTest()) {
                $totalPendingCount         = $this->model->getPendingLeads($emailEntity, null, true);
                $totalLeadCountForVariants = $emailEntity->getVariantsPendingCount($totalPendingCount);
                $emailEntity->setPendingCount($totalPendingCount);

                if ($emailEntity->waitingToDetermineWinner($totalLeadCountForVariants)) {
                    continue;
                }
                if ($emailEntity->waitingToSendTestsEmails($totalLeadCountForVariants)) {
                    if (!$event->checkContext('email-abtest')) {
                        continue;
                    }

                    // only 1 thread for AB
                    if ($threadId && $threadId > 1) {
                        continue;
                    }

                    // test sending without batch and threads
                    $batch      = null;
                    $maxThreads = null;
                    $threadId   = null;

                    // a/b test first sending without limit
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
                $event->getMaxThreads(),
                $event->getThreadId()
            );

            if ($this->shouldCheckForUnpublishEmail($emailEntity)) {
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

    private function shouldCheckForUnpublishEmail(Email $email): bool
    {
        if ($email->isContinueSending()) {
            return false;
        }

        if (empty($email->getSentCount(true))) {
            return false;
        }

        return true;
    }
}
