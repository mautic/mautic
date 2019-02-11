<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Tests\EventListener;

use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Helper\TokenHelper;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\SmsBundle\EventListener\SmsSubscriber;
use Mautic\SmsBundle\Helper\SmsHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SmsSubscriberTest extends WebTestCase
{
    private $messsageText = 'custom http://mautic.com text';

    private $messsageUrl = 'http://mautic.com';

    public function testOnTokenReplacementWithTrackableUrls()
    {
        $mockAuditLogModel = $this->createMock(AuditLogModel::class);

        $mockTrackableModel = $this->createMock(TrackableModel::class);
        $mockTrackableModel->expects($this->any())->method('parseContentForTrackables')->willReturn([
            $this->messsageUrl,
            new Trackable(),
        ]);
        $mockTrackableModel->expects($this->any())->method('generateTrackableUrl')->willReturn('custom');

        $mockPageTokenHelper = $this->createMock(TokenHelper::class);
        $mockPageTokenHelper->expects($this->any())->method('findPageTokens')->willReturn([]);

        $mockAssetTokenHelper = $this->createMock(\Mautic\AssetBundle\Helper\TokenHelper::class);
        $mockAssetTokenHelper->expects($this->any())->method('findAssetTokens')->willReturn([]);

        $mockSmsHelper = $this->createMock(SmsHelper::class);
        $mockSmsHelper->expects($this->any())->method('getDisableTrackableUrls')->willReturn(false);

        $lead                  = new Lead();
        $tokenReplacementEvent = new TokenReplacementEvent($this->messsageText, $lead, ['channel' => ['sms', 1]]);
        $subscriber            = new SmsSubscriber(
            $mockAuditLogModel,
            $mockTrackableModel,
            $mockPageTokenHelper,
            $mockAssetTokenHelper,
            $mockSmsHelper
        );
        $subscriber->onTokenReplacement($tokenReplacementEvent);
        $this->assertNotSame($this->messsageText, $tokenReplacementEvent->getContent());
    }

    public function testOnTokenReplacementWithDisableTrackableUrls()
    {
        $mockAuditLogModel = $this->createMock(AuditLogModel::class);

        $mockTrackableModel = $this->createMock(TrackableModel::class);
        $mockTrackableModel->expects($this->any())->method('parseContentForTrackables')->willReturn([
            $this->messsageUrl,
            new Trackable(),
        ]);
        $mockTrackableModel->expects($this->any())->method('generateTrackableUrl')->willReturn('custom');

        $mockPageTokenHelper = $this->createMock(TokenHelper::class);
        $mockPageTokenHelper->expects($this->any())->method('findPageTokens')->willReturn([]);

        $mockAssetTokenHelper = $this->createMock(\Mautic\AssetBundle\Helper\TokenHelper::class);
        $mockAssetTokenHelper->expects($this->any())->method('findAssetTokens')->willReturn([]);

        $mockSmsHelper = $this->createMock(SmsHelper::class);
        $mockSmsHelper->expects($this->any())->method('getDisableTrackableUrls')->willReturn(true);

        $lead                  = new Lead();
        $tokenReplacementEvent = new TokenReplacementEvent($this->messsageText, $lead, ['channel' => ['sms', 1]]);
        $subscriber            = new SmsSubscriber(
            $mockAuditLogModel,
            $mockTrackableModel,
            $mockPageTokenHelper,
            $mockAssetTokenHelper,
            $mockSmsHelper
        );
        $subscriber->onTokenReplacement($tokenReplacementEvent);
        $this->assertSame($this->messsageText, $tokenReplacementEvent->getContent());
    }
}
