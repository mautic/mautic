<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\ContactExportScheduler;
use Mautic\LeadBundle\Event\ContactExportEmailSentEvent;
use Mautic\LeadBundle\Event\ContactExportScheduledEvent;
use Mautic\LeadBundle\Event\ContactExportSchedulerEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class ContactExportSchedulerLoggerSubscriber
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    #[AsEventListener(event: ContactExportScheduledEvent::class)]
    public function onContactExportScheduled(ContactExportSchedulerEvent $event): void
    {
        $utcScheduledDateTimeStr = $this->getContactExportScheduledDateTimeStr($event->getContactExportScheduler());

        $this->logger->debug(
            'Contact export #ID '.$event->getContactExportScheduler()->getId()
            .' scheduled at '.$utcScheduledDateTimeStr.' UTC'
        );
    }

    #[AsEventListener(event: ContactExportEmailSentEvent::class)]
    public function onContactExportEmailSent(ContactExportSchedulerEvent $event): void
    {
        $utcScheduledDateTimeStr = $this->getContactExportScheduledDateTimeStr($event->getContactExportScheduler());

        $this->logger->debug(
            'Contact export #ID '.$event->getContactExportScheduler()->getId()
            .' scheduled at '.$utcScheduledDateTimeStr.' UTC has been processed at '
            .new DateTime()->setTimezone(new DateTimeZone('UTC'))->format(DateTimeHelper::FORMAT_DB)
            .' UTC'
        );
    }

    private function getContactExportScheduledDateTimeStr(ContactExportScheduler $contactExportScheduler): string
    {
        /** @var DateTimeImmutable $scheduledDateTime */
        $scheduledDateTime = $contactExportScheduler->getScheduledDateTime();

        return $scheduledDateTime->setTimezone(new DateTimeZone('UTC'))->format(DateTimeHelper::FORMAT_DB);
    }
}
