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

        $this->assertNotInstanceOf(\DateTimeInterface::class, $clone->getPublishUp());
        $this->assertFalse($clone->isContinueSending());
        $this->assertFalse($clone->getIsPublished());

        // Enable continuing mode so getPublishDown() exposes the stored value.
        $clone->setContinueSending(true);
        $this->assertNotInstanceOf(\DateTimeInterface::class, $clone->getPublishDown());
    }

    public function testDisablingContinuingModeClearsTheStopDateForSegmentSms(): void
    {
        $sms = new Sms();
        $sms->setSmsType('list');
        $sms->setContinueSending(true);
        $sms->setPublishDown(new \DateTime('+1 hour'));

        $sms->setContinueSending(false);
        $sms->setContinueSending(true);

        $this->assertNotInstanceOf(\DateTimeInterface::class, $sms->getPublishDown());
    }

    public function testActiveScheduledSmsIsBackgroundSending(): void
    {
        $sms = $this->createScheduledSms(new \DateTime('-1 hour'));

        $this->assertTrue($sms->isBackgroundSending());
    }

    public function testFutureScheduledSmsIsNotBackgroundSendingAndIsPending(): void
    {
        $sms = $this->createScheduledSms(new \DateTime('+1 hour'));

        $this->assertFalse($sms->isBackgroundSending());
        $this->assertSame('pending', $sms->getSendingStatus());
    }

    public function testExpiredScheduledSmsIsNotBackgroundSendingAndIsExpired(): void
    {
        $sms = $this->createScheduledSms(new \DateTime('-2 hours'));
        $sms->setContinueSending(true);
        $sms->setPublishDown(new \DateTime('-1 hour'));

        $this->assertFalse($sms->isBackgroundSending());
        $this->assertSame('expired', $sms->getSendingStatus());
    }

    public function testUnpublishedScheduledSmsIsNotBackgroundSending(): void
    {
        $sms = $this->createScheduledSms(new \DateTime('-1 hour'));
        $sms->setIsPublished(false);

        $this->assertFalse($sms->isBackgroundSending());
        $this->assertSame('unpublished', $sms->getSendingStatus());
    }

    public function testActiveSmsWithPendingContactsHasSendingStatus(): void
    {
        $sms = $this->createScheduledSms(new \DateTime('-1 hour'));
        $sms->setPendingCount(2);

        $this->assertSame('sending', $sms->getSendingStatus());
    }

    public function testDrainedOneTimeSmsWithPriorSendsHasSentStatus(): void
    {
        $sms = $this->createScheduledSms(new \DateTime('-1 hour'));
        $sms->setPendingCount(0);
        $sms->setSentCount(1);

        $this->assertSame('sent', $sms->getSendingStatus());
    }

    public function testDrainedContinuingSmsRemainsPublished(): void
    {
        $sms = $this->createScheduledSms(new \DateTime('-1 hour'));
        $sms->setContinueSending(true);
        $sms->setPendingCount(0);
        $sms->setSentCount(1);

        $this->assertSame('published', $sms->getSendingStatus());
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
