<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Integration;

use Mautic\PluginBundle\Model\IntegrationEntityModel;
use Mautic\PluginBundle\Tests\Integration\AbstractIntegrationTestCase;
use MauticPlugin\MauticCrmBundle\Api\ConnectwiseApi;
use MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;

#[CoversClass(ConnectwiseIntegration::class)]
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
final class ConnectwiseIntegrationTest extends AbstractIntegrationTestCase
{
    use DataGeneratorTrait;

    #[TestDox('Test that all records are fetched till last page of results are consumed')]
    public function testMultiplePagesOfRecordsAreFetched(): void
    {
        $this->reset();

        $apiHelper = $this->createMock(ConnectwiseApi::class);

        $apiHelper->expects($this->exactly(2))
            ->method('getContacts')
            ->willReturnCallback(
                fn (): array => $this->generateData(2)
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

    #[TestDox('Test that all records are fetched till last page of results are consumed')]
    public function testMultiplePagesOfCampaignMemberRecordsAreFetched(): void
    {
        $this->reset();

        $apiHelper = $this->createMock(ConnectwiseApi::class);

        $apiHelper->expects($this->exactly(2))
            ->method('getCampaignMembers')
            ->willReturnCallback(
                fn (): array => $this->generateData(2)
            );

        $integrationEntityModel = $this->createStub(IntegrationEntityModel::class);

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
