<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Event\CampaignEvent;
use Mautic\CampaignBundle\EventListener\CampaignSubscriber;
use Mautic\CampaignBundle\Service\CampaignAuditService;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use PHPUnit\Framework\TestCase;

class CampaignSubscriberTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $ipLookupHelper;

    private \PHPUnit\Framework\MockObject\MockObject $auditLogModel;

    private \PHPUnit\Framework\MockObject\MockObject $campaignAuditService;

    private CampaignSubscriber $subscriber;

    public function setUp(): void
    {
        parent::setUp();

        $this->ipLookupHelper       = $this->createMock(IpLookupHelper::class);
        $this->auditLogModel        = $this->createMock(AuditLogModel::class);
        $this->campaignAuditService = $this->createMock(CampaignAuditService::class);

        $this->subscriber = new CampaignSubscriber(
            $this->ipLookupHelper,
            $this->auditLogModel,
            $this->campaignAuditService
        );
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [
                CampaignEvents::CAMPAIGN_POST_SAVE     => ['onCampaignPostSave', 0],
                CampaignEvents::CAMPAIGN_POST_DELETE   => ['onCampaignDelete', 0],
            ],
            CampaignSubscriber::getSubscribedEvents()
        );
    }

    public function testOnCampaignPostSaveNothingHappened(): void
    {
        $campaign            = new Campaign();
        $event               = new CampaignEvent($campaign);

        $this->auditLogModel->expects($this->never())
            ->method('writeToLog');

        $this->subscriber->onCampaignPostSave($event);
    }

    public function testOnCampaignPostSaveUnpublished(): void
    {
        $ipAddress    = 'someIp';

        $dateTime = new \DateTime();
        $dateTime->setTimestamp(1_597_752_193);

        $campaign = new Campaign();
        $campaign->setPublishDown($dateTime);

        $event = new CampaignEvent($campaign);

        $this->ipLookupHelper->expects($this->once())
            ->method('getIpAddressFromRequest')
            ->willReturn($ipAddress);

        $expectedLog = [
            'bundle'    => 'campaign',
            'object'    => 'campaign',
            'objectId'  => $campaign->getId(),
            'action'    => 'update',
            'details'   => [
                'publishDown' => [
                    0 => null,
                    1 => '2020-08-18T12:03:13+00:00',
                ],
            ],
            'ipAddress' => $ipAddress,
        ];

        $this->auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with($expectedLog);

        $this->subscriber->onCampaignPostSave($event);
    }

    public function testOnCampaignPostSaveCreateFlash(): void
    {
        $ipAddress    = 'someIp';
        $campaignName = 'campaignName';

        $dateTime = new \DateTime();
        $dateTime->setTimestamp(1_597_752_193);

        $campaign = new Campaign();
        $campaign->setPublishUp($dateTime);
        $campaign->setName($campaignName);

        $event = new CampaignEvent($campaign);

        $this->campaignAuditService->expects($this->once())
            ->method('addWarningForUnpublishedEmails')
            ->with($campaign);

        $this->ipLookupHelper->expects($this->once())
            ->method('getIpAddressFromRequest')
            ->willReturn($ipAddress);

        $expectedLog = [
            'action'    => 'update',
            'bundle'    => 'campaign',
            'details'   => [
                'name' => [
                    0 => null,
                    1 => $campaignName,
                ],
                'publishUp' => [
                    0 => null,
                    1 => '2020-08-18T12:03:13+00:00',
                ],
            ],
            'ipAddress' => $ipAddress,
            'object'    => 'campaign',
            'objectId'  => $campaign->getId(),
        ];

        $this->auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with($expectedLog);

        $this->subscriber->onCampaignPostSave($event);
    }

    public function testOnCampaignDelete(): void
    {
        $deletedId    = 1;
        $campaignName = 'campaignName';
        $ipAddress    = 'someIp';

        $campaign            = new Campaign();
        $campaign->deletedId = $deletedId;
        $campaign->setName($campaignName);

        $event = new CampaignEvent($campaign);

        $this->ipLookupHelper->expects($this->once())
            ->method('getIpAddressFromRequest')
            ->willReturn($ipAddress);

        $expectedLog = [
            'bundle'    => 'campaign',
            'object'    => 'campaign',
            'objectId'  => $deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $campaignName],
            'ipAddress' => $ipAddress,
        ];

        $this->auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with($expectedLog);

        $this->subscriber->onCampaignDelete($event);
    }
}
