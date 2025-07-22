<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Integration;

use Mautic\PluginBundle\Model\IntegrationEntityModel;
use Mautic\PluginBundle\Tests\Integration\AbstractIntegrationTestCase;
use MauticPlugin\MauticCrmBundle\Api\ConnectwiseApi;
use MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration;

class ConnectwiseIntegrationTest extends AbstractIntegrationTestCase
{
    use DataGeneratorTrait;

    /**
     * @testdox Test that all records are fetched till last page of results are consumed
     *
     * @covers \MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration::getRecords
     */
    public function testMultiplePagesOfRecordsAreFetched(): void
    {
        $this->reset();

        $apiHelper = $this->getMockBuilder(ConnectwiseApi::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiHelper->expects($this->exactly(2))
            ->method('getContacts')
            ->willReturnCallback(
                fn () => $this->generateData(2)
            );

        $integration = $this->getMockBuilder(ConnectwiseIntegration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isAuthorized', 'getApiHelper', 'getMauticLead'])
            ->getMock();

        $integration->expects($this->once())
            ->method('isAuthorized')
            ->willReturn(true);

        $integration
            ->method('getApiHelper')
            ->willReturn($apiHelper);

        $integration->getRecords([], 'Contact');
    }

    /**
     * @testdox Test that all records are fetched till last page of results are consumed
     *
     * @covers \MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration::getCampaignMembers
     */
    public function testMultiplePagesOfCampaignMemberRecordsAreFetched(): void
    {
        $this->reset();

        $apiHelper = $this->getMockBuilder(ConnectwiseApi::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiHelper->expects($this->exactly(2))
            ->method('getCampaignMembers')
            ->willReturnCallback(
                fn () => $this->generateData(2)
            );

        $integrationEntityModel = $this->getMockBuilder(IntegrationEntityModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $integration = $this->getMockBuilder(ConnectwiseIntegration::class)
            ->setConstructorArgs([
                $this->dispatcher,
                $this->cache,
                $this->em,
                $this->request,
                $this->router,
                $this->translator,
                $this->logger,
                $this->encryptionHelper,
                $this->leadModel,
                $this->companyModel,
                $this->pathsHelper,
                $this->notificationModel,
                $this->fieldModel,
                $integrationEntityModel,
                $this->doNotContact,
                $this->fieldsWithUniqueIdentifier,
            ])
            ->onlyMethods(['isAuthorized', 'getApiHelper', 'getRecords', 'saveCampaignMembers'])
            ->getMock();

        $integration->expects($this->once())
            ->method('isAuthorized')
            ->willReturn(true);

        $integration
            ->method('getApiHelper')
            ->willReturn($apiHelper);

        $integration->getCampaignMembers(1);
    }
}
