<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\EventListener;

use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Helper\TokenHelper;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\SmsBundle\EventListener\SmsSubscriber;
use Mautic\SmsBundle\Helper\SmsHelper;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
final class SmsSubscriberTest extends TestCase
{
    private string $messageText = 'custom http://mautic.com text';

    private string $messageUrl = 'http://mautic.com';

    public function testOnTokenReplacementWithTrackableUrls(): void
    {
        $mockAuditLogModel = $this->createStub(AuditLogModel::class);

        $mockTrackableModel = $this->createMock(TrackableModel::class);
        $mockTrackableModel->method('parseContentForTrackables')->willReturn([
            $this->messageUrl,
            new Trackable(),
        ]);
        $mockTrackableModel->method('generateTrackableUrl')->willReturn('custom');

        $mockPageTokenHelper = $this->createMock(TokenHelper::class);
        $mockPageTokenHelper->method('findPageTokens')->willReturn([]);

        $mockAssetTokenHelper = $this->createMock(\Mautic\AssetBundle\Helper\TokenHelper::class);
        $mockAssetTokenHelper->method('findAssetTokens')->willReturn([]);

        $mockSmsHelper = $this->createMock(SmsHelper::class);
        $mockSmsHelper->method('getDisableTrackableUrls')->willReturn(false);

        $lead                  = new Lead();
        $tokenReplacementEvent = new TokenReplacementEvent($this->messageText, $lead, ['channel' => [1 => 'sms']]);
        $subscriber            = new SmsSubscriber(
            $mockAuditLogModel,
            $mockTrackableModel,
            $mockPageTokenHelper,
            $mockAssetTokenHelper,
            $mockSmsHelper,
            $this->createStub(CoreParametersHelper::class)
        );
        $subscriber->onTokenReplacement($tokenReplacementEvent);
        $this->assertNotSame($this->messageText, $tokenReplacementEvent->getContent());
    }

    public function testOnTokenReplacementWithDisableTrackableUrls(): void
    {
        $mockAuditLogModel = $this->createStub(AuditLogModel::class);

        $mockTrackableModel = $this->createMock(TrackableModel::class);
        $mockTrackableModel->method('parseContentForTrackables')->willReturn([
            $this->messageUrl,
            new Trackable(),
        ]);
        $mockTrackableModel->method('generateTrackableUrl')->willReturn('custom');

        $mockPageTokenHelper = $this->createMock(TokenHelper::class);
        $mockPageTokenHelper->method('findPageTokens')->willReturn([]);

        $mockAssetTokenHelper = $this->createMock(\Mautic\AssetBundle\Helper\TokenHelper::class);
        $mockAssetTokenHelper->method('findAssetTokens')->willReturn([]);

        $mockSmsHelper = $this->createMock(SmsHelper::class);
        $mockSmsHelper->method('getDisableTrackableUrls')->willReturn(true);

        $lead                  = new Lead();
        $tokenReplacementEvent = new TokenReplacementEvent($this->messageText, $lead, ['channel' => ['sms', 1]]);
        $subscriber            = new SmsSubscriber(
            $mockAuditLogModel,
            $mockTrackableModel,
            $mockPageTokenHelper,
            $mockAssetTokenHelper,
            $mockSmsHelper,
            $this->createStub(CoreParametersHelper::class)
        );
        $subscriber->onTokenReplacement($tokenReplacementEvent);
        $this->assertSame($this->messageText, $tokenReplacementEvent->getContent());
    }
}
