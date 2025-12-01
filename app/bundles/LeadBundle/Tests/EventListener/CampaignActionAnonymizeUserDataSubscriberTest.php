<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\EventListener\CampaignActionAnonymizeUserDataSubscriber;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\TestCase;

final class CampaignActionAnonymizeUserDataSubscriberTest extends TestCase
{
    protected CampaignActionAnonymizeUserDataSubscriber $campaignActionAnonymizeUserDataSubscriber;

    public function setUp(): void
    {
        parent::setUp();
        $leadModelMock                                   = $this->createMock(LeadModel::class);
        $fieldModelMock                                  = $this->createMock(FieldModel::class);
        $companyModelMock                                = $this->createMock(CompanyModel::class);
        $anonymizeContactCompanyDataMock                 = $this->createMock(\Mautic\LeadBundle\Services\AnonymizeContactCompanyData::class);
        $auditLogModelMock                               = $this->createMock(AuditLogModel::class);

        $this->campaignActionAnonymizeUserDataSubscriber = new CampaignActionAnonymizeUserDataSubscriber(
            $leadModelMock,
            $fieldModelMock,
            $companyModelMock,
            $anonymizeContactCompanyDataMock,
            $auditLogModelMock
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(
            [
                CampaignEvents::CAMPAIGN_ON_BUILD                  => ['configureAction', 0],
                LeadEvents::ON_CAMPAIGN_ACTION_ANONYMIZE_USER_DATA => ['anonymizeUserData', 10],
            ],
            $this->campaignActionAnonymizeUserDataSubscriber::getSubscribedEvents()
        );
    }

    public function testConfigureAction(): void
    {
        $event = $this->createMock(CampaignBuilderEvent::class);
        $event->expects($this->exactly(1))->method('addAction');
        $this->campaignActionAnonymizeUserDataSubscriber->configureAction($event);
    }
}
