<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\SyncDataExchange;

use Mautic\IntegrationsBundle\Entity\FieldChangeRepository;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\Helper\MappingHelper;
use Mautic\IntegrationsBundle\Sync\Helper\SyncDateHelper;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Helper\FieldHelper;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner\OrderExecutioner;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\FullObjectReportBuilder;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\PartialObjectReportBuilder;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MauticSyncDataExchangeTest extends TestCase
{
    /**
     * @var MockObject|FieldChangeRepository
     */
    private MockObject $fieldChangeRepository;

    /**
     * @var MockObject|FieldHelper
     */
    private MockObject $fieldHelper;

    /**
     * @var MockObject|MappingHelper
     */
    private MockObject $mappingHelper;

    /**
     * @var MockObject|FullObjectReportBuilder
     */
    private MockObject $fullObjectReportBuilder;

    /**
     * @var MockObject|PartialObjectReportBuilder
     */
    private MockObject $partialObjectReportBuilder;

    /**
     * @var MockObject|OrderExecutioner
     */
    private MockObject $orderExecutioner;

    private MauticSyncDataExchange $mauticSyncDataExchange;

    /**
     * @var SyncDateHelper&MockObject
     */
    private MockObject $syncDateHelper;

    protected function setUp(): void
    {
        $this->fieldChangeRepository      = $this->createMock(FieldChangeRepository::class);
        $this->fieldHelper                = $this->createMock(FieldHelper::class);
        $this->mappingHelper              = $this->createMock(MappingHelper::class);
        $this->fullObjectReportBuilder    = $this->createMock(FullObjectReportBuilder::class);
        $this->partialObjectReportBuilder = $this->createMock(PartialObjectReportBuilder::class);
        $this->orderExecutioner           = $this->createMock(OrderExecutioner::class);
        $this->syncDateHelper             = $this->createMock(SyncDateHelper::class);

        $this->mauticSyncDataExchange = new MauticSyncDataExchange(
            $this->fieldChangeRepository,
            $this->fieldHelper,
            $this->mappingHelper,
            $this->fullObjectReportBuilder,
            $this->partialObjectReportBuilder,
            $this->orderExecutioner,
            $this->syncDateHelper
        );
    }

    public function testFirstTimeSyncUsesFullObjectBuilder(): void
    {
        $inputOptionsDAO = new InputOptionsDAO(
            [
                'integration'     => 'foobar',
                'first-time-sync' => true,
            ]
        );

        $requestDAO = new RequestDAO('foobar', 1, $inputOptionsDAO);

        $this->fullObjectReportBuilder->expects($this->once())
            ->method('buildReport')
            ->with($requestDAO);

        $this->partialObjectReportBuilder->expects($this->never())
            ->method('buildReport')
            ->with($requestDAO);

        $this->mauticSyncDataExchange->getSyncReport($requestDAO);
    }

    public function testSyncingSpecificMauticIdsUseFullObjectBuilder(): void
    {
        $inputOptionsDAO = new InputOptionsDAO(
            [
                'integration'      => 'foobar',
                'mautic-object-id' => [1, 2, 3],
            ]
        );

        $requestDAO = new RequestDAO('foobar', 1, $inputOptionsDAO);

        $this->fullObjectReportBuilder->expects($this->once())
            ->method('buildReport')
            ->with($requestDAO);

        $this->partialObjectReportBuilder->expects($this->never())
            ->method('buildReport')
            ->with($requestDAO);

        $this->mauticSyncDataExchange->getSyncReport($requestDAO);
    }

    public function testUseOfPartialObjectBuilder(): void
    {
        $inputOptionsDAO = new InputOptionsDAO(
            [
                'integration' => 'foobar',
            ]
        );

        $requestDAO = new RequestDAO('foobar', 1, $inputOptionsDAO);

        $this->fullObjectReportBuilder->expects($this->never())
            ->method('buildReport')
            ->with($requestDAO);

        $this->partialObjectReportBuilder->expects($this->once())
            ->method('buildReport')
            ->with($requestDAO);

        $this->mauticSyncDataExchange->getSyncReport($requestDAO);
    }

    public function testGetConflictedInternalObjectWithNoObjectId(): void
    {
        $mappingManualDao     = new MappingManualDAO('IntegrationA');
        $integrationObjectDao = new ObjectDAO('Lead', 'some-SF-ID');

        $this->mappingHelper->expects($this->once())
            ->method('findMauticObject')
            ->with($mappingManualDao, 'lead', $integrationObjectDao)
            ->willReturn(new ObjectDAO('lead', null));

        // No need to make the DB query when ID is null.
        $this->fieldChangeRepository->expects($this->never())
            ->method('findChangesForObject');

        $internalObjectDao = $this->mauticSyncDataExchange->getConflictedInternalObject($mappingManualDao, 'lead', $integrationObjectDao);

        Assert::assertSame('lead', $internalObjectDao->getObject());
        Assert::assertNull($internalObjectDao->getObjectId());
    }

    public function testGetConflictedInternalObjectWithObjectId(): void
    {
        $mappingManualDao     = new MappingManualDAO('IntegrationA');
        $integrationObjectDao = new ObjectDAO('Lead', 'some-SF-ID');
        $fieldChange          = [
            'modified_at'  => '2020-08-25 17:20:00',
            'column_type'  => 'text',
            'column_value' => 'some-field-value',
            'column_name'  => 'some-field-name',
        ];

        $this->mappingHelper->expects($this->once())
            ->method('findMauticObject')
            ->with($mappingManualDao, 'lead', $integrationObjectDao)
            ->willReturn(new ObjectDAO('lead', 123));

        $this->mappingHelper->method('getMauticEntityClassName')
            ->with('lead')
            ->willReturn(Lead::class);

        $this->fieldHelper->method('getFieldChangeObject')
            ->with($fieldChange)
            ->willReturn(new FieldDAO('some-field-name', new NormalizedValueDAO('type', 'some-field-value')));

        $this->fieldChangeRepository->expects($this->once())
            ->method('findChangesForObject')
            ->with('IntegrationA', Lead::class, 123)
            ->willReturn([$fieldChange]);

        $internalObjectDao = $this->mauticSyncDataExchange->getConflictedInternalObject($mappingManualDao, 'lead', $integrationObjectDao);

        Assert::assertSame('lead', $internalObjectDao->getObject());
        Assert::assertSame(123, $internalObjectDao->getObjectId());
        Assert::assertCount(1, $internalObjectDao->getFields());
    }
}
