<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\EventListener\CampaignImportExportSubscriber;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\UserBundle\Model\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class CampaignImportExportSubscriberTest extends TestCase
{
    private CampaignImportExportSubscriber $subscriber;
    private MockObject&EntityManager $entityManager;
    private MockObject&CampaignModel $campaignModel;
    private MockObject&UserModel $userModel;
    private EventDispatcher $dispatcher;
    private MockObject&LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->campaignModel = $this->createMock(CampaignModel::class);
        $this->userModel     = $this->createMock(UserModel::class);
        $this->logger        = $this->createMock(LoggerInterface::class);

        $this->dispatcher = new EventDispatcher();

        $this->subscriber = new CampaignImportExportSubscriber(
            $this->campaignModel,
            $this->userModel,
            $this->entityManager,
            $this->dispatcher,
            $this->logger
        );

        $this->dispatcher->addSubscriber($this->subscriber);
    }

    public function testCampaignExport(): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')->willReturn(1);
        $campaign->method('getName')->willReturn('Test Campaign');
        $campaign->method('getDescription')->willReturn('Description');
        $campaign->method('getIsPublished')->willReturn(true);
        $campaign->method('getCanvasSettings')->willReturn([]);

        $this->campaignModel->method('getEntity')->willReturn($campaign);

        $event = new EntityExportEvent(Campaign::ENTITY_NAME, 1);
        $this->dispatcher->dispatch($event);

        $this->assertInstanceOf(EntityExportEvent::class, $event, 'Event must be an instance of EntityExportEvent');

        $exportedData = $event->getEntities();

        $this->assertArrayHasKey(Campaign::ENTITY_NAME, $exportedData, 'Exported data must contain the campaign entity.');
        $this->assertSame(1, $exportedData[Campaign::ENTITY_NAME][0]['id']);
        $this->assertSame('Test Campaign', $exportedData[Campaign::ENTITY_NAME][0]['name']);
    }
}
