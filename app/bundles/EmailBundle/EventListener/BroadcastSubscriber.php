<?php

namespace Mautic\EmailBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelBroadcastEvent;
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
            $emailEntity                                        = $email;
            [$sentCount, $failedCount, $failedRecipientsByList] = $this->model->sendEmailToLists(
                $emailEntity,
                null,
                $event->getLimit(),
                $event->getBatch(),
                $event->getOutput(),
                $event->getMinContactIdFilter(),
                $event->getMaxContactIdFilter(),
                $event->getMaxThreads(),
                $event->getThreadId()
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
}
