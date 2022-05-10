<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\ContactExportScheduler;
use Mautic\LeadBundle\Event\ContactExportSchedulerEvent;
use Mautic\LeadBundle\LeadEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContactExportSchedulerLoggerSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::POST_CONTACT_EXPORT_SCHEDULED  => 'onContactExportScheduled',
            LeadEvents::POST_CONTACT_EXPORT_SEND_EMAIL => 'onContactExportEmailSent',
        ];
    }

    public function onContactExportScheduled(ContactExportSchedulerEvent $event): void
    {
        $utcScheduledDateTimeStr = $this->getContactExportScheduledDateTimeStr($event->getContactExportScheduler());

        $this->logger->debug(
            'Contact export #ID '.$event->getContactExportScheduler()->getId()
            .' scheduled at '.$utcScheduledDateTimeStr.' UTC'
        );
    }

    public function onContactExportEmailSent(ContactExportSchedulerEvent $event): void
    {
        $utcScheduledDateTimeStr = $this->getContactExportScheduledDateTimeStr($event->getContactExportScheduler());

        $this->logger->debug(
            'Contact export #ID '.$event->getContactExportScheduler()->getId()
            .' scheduled at '.$utcScheduledDateTimeStr.' UTC has been processed at '
            .(new DateTime())->setTimezone(new DateTimeZone('UTC'))->format(DateTimeHelper::FORMAT_DB)
            .' UTC'
        );
    }

    private function getContactExportScheduledDateTimeStr(ContactExportScheduler $contactExportScheduler): string
    {
        $scheduledDateTime = $contactExportScheduler->getScheduledDateTime();
        \assert($scheduledDateTime instanceof DateTimeImmutable);

        return $scheduledDateTime->setTimezone(new DateTimeZone('UTC'))->format(DateTimeHelper::FORMAT_DB);
    }
}
