<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Entity;

use Mautic\SmsBundle\Entity\Sms;
use PHPUnit\Framework\TestCase;

final class SmsTest extends TestCase
{
    public function testCloneResetsScheduleAndPublicationState(): void
    {
        $sms = new Sms();
        $sms->setSmsType('list');
        $sms->setPublishUp(new \DateTime('-1 hour'));
        $sms->setContinueSending(true);
        $sms->setPublishDown(new \DateTime('+1 hour'));
        $sms->setIsPublished(true);

        $clone = clone $sms;

        self::assertNull($clone->getPublishUp());
        self::assertFalse($clone->isContinueSending());
        self::assertFalse($clone->getIsPublished());

        // Enable continuing mode so getPublishDown() exposes the stored value.
        $clone->setContinueSending(true);
        self::assertNull($clone->getPublishDown());
    }

    public function testDisablingContinuingModeClearsTheStopDateForSegmentSms(): void
    {
        $sms = new Sms();
        $sms->setSmsType('list');
        $sms->setContinueSending(true);
        $sms->setPublishDown(new \DateTime('+1 hour'));

        $sms->setContinueSending(false);
        $sms->setContinueSending(true);

        self::assertNull($sms->getPublishDown());
    }

    public function testActiveScheduledSmsIsBackgroundSending(): void
    {
        $sms = $this->createScheduledSms(new \DateTime('-1 hour'));

        self::assertTrue($sms->isBackgroundSending());
    }

    public function testFutureScheduledSmsIsNotBackgroundSendingAndIsPending(): void
    {
        $sms = $this->createScheduledSms(new \DateTime('+1 hour'));

        self::assertFalse($sms->isBackgroundSending());
        self::assertSame('pending', $sms->getSendingStatus());
    }

    public function testExpiredScheduledSmsIsNotBackgroundSendingAndIsExpired(): void
    {
        $sms = $this->createScheduledSms(new \DateTime('-2 hours'));
        $sms->setContinueSending(true);
        $sms->setPublishDown(new \DateTime('-1 hour'));

        self::assertFalse($sms->isBackgroundSending());
        self::assertSame('expired', $sms->getSendingStatus());
    }

    public function testUnpublishedScheduledSmsIsNotBackgroundSending(): void
    {
        $sms = $this->createScheduledSms(new \DateTime('-1 hour'));
        $sms->setIsPublished(false);

        self::assertFalse($sms->isBackgroundSending());
        self::assertSame('unpublished', $sms->getSendingStatus());
    }

    public function testActiveSmsWithPendingContactsHasSendingStatus(): void
    {
        $sms = $this->createScheduledSms(new \DateTime('-1 hour'));
        $sms->setPendingCount(2);

        self::assertSame('sending', $sms->getSendingStatus());
    }

    public function testDrainedOneTimeSmsWithPriorSendsHasSentStatus(): void
    {
        $sms = $this->createScheduledSms(new \DateTime('-1 hour'));
        $sms->setPendingCount(0);
        $sms->setSentCount(1);

        self::assertSame('sent', $sms->getSendingStatus());
    }

    public function testDrainedContinuingSmsRemainsPublished(): void
    {
        $sms = $this->createScheduledSms(new \DateTime('-1 hour'));
        $sms->setContinueSending(true);
        $sms->setPendingCount(0);
        $sms->setSentCount(1);

        self::assertSame('published', $sms->getSendingStatus());
    }

    private function createScheduledSms(\DateTimeInterface $publishUp): Sms
    {
        $sms = new Sms();
        $sms->setSmsType('list');
        $sms->setPublishUp($publishUp);
        $sms->setIsPublished(true);

        return $sms;
    }
}
