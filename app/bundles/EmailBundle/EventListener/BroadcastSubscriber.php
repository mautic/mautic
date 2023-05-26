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
    /**
     * @var EmailModel
     */
    private $model;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(EmailModel $emailModel, EntityManager $em, TranslatorInterface $translator)
    {
        $this->model      = $emailModel;
        $this->em         = $em;
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ChannelEvents::CHANNEL_BROADCAST => ['onBroadcast', 0],
        ];
    }

    public function onBroadcast(ChannelBroadcastEvent $event)
    {
        if (!$event->checkContext('email')) {
            return;
        }

        $limit      = $event->getLimit();
        $batch      = $event->getBatch();

        // Get list of published broadcasts or broadcast if there is only a single ID
        $emails = $this->model->getRepository()->getPublishedBroadcasts($event->getId());

        while (false !== ($email = $emails->next())) {
            /** @var Email $emailEntity */
            $emailEntity                                            = $email[0];
            if ($emailEntity->isVariant(true)) {
                continue;
            }

            // is a/b testings
            if ($emailEntity->isVariant()) {
                if ($emailEntity->getVariantSentCount(true)) {
                    continue;
                }
                // a/b test first sending without limit
                $limit = null;
                $batch = null;
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
