<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Event;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ReportGeneratorEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|Report
     */
    private MockObject $report;

    /**
     * @var MockObject|QueryBuilder
     */
    private MockObject $queryBuilder;

    private ChannelListHelper $channelListHelper;

    private ReportGeneratorEvent $reportGeneratorEvent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->report                = $this->createMock(Report::class);
        $this->queryBuilder          = $this->createMock(QueryBuilder::class);
        $this->channelListHelper     = new ChannelListHelper($this->createMock(EventDispatcher::class), $this->createMock(Translator::class));
        $this->reportGeneratorEvent  = new ReportGeneratorEvent(
            $this->report,
            [], // Use the setter if you need different options
            $this->queryBuilder,
            $this->channelListHelper
        );
    }

    public function testAddCategoryLeftJoinWhenColumnIsNotUsed(): void
    {
        $this->report->expects($this->once())
            ->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
            ->willReturn(['e.id', 'comp.name']);

        $this->queryBuilder->expects($this->never())
            ->method('leftJoin');

        $this->reportGeneratorEvent->addCategoryLeftJoin($this->queryBuilder, 'e');
    }

    public function testAddCategoryLeftJoinWhenColumnIsUsed(): void
    {
        $this->report->expects($this->once())
            ->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
            ->willReturn(
                ['e.id', ReportGeneratorEvent::CATEGORY_PREFIX.'.title', 'comp.name']
            );

        $this->queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with(
                'e',
                MAUTIC_TABLE_PREFIX.'categories',
                ReportGeneratorEvent::CATEGORY_PREFIX,
                ReportGeneratorEvent::CATEGORY_PREFIX.'.id = e.category_id'
            );

        $this->reportGeneratorEvent->addCategoryLeftJoin($this->queryBuilder, 'e');
    }

    public function testAddLeadLeftJoinWhenColumnIsNotUsed(): void
    {
        $this->report->expects($this->exactly(5))
            ->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
            ->willReturn(['e.id', 'h.name']);

        $this->queryBuilder->expects($this->never())
            ->method('leftJoin');

        $this->reportGeneratorEvent->addLeadLeftJoin($this->queryBuilder, 'e');
    }

    public function testAddLeadLeftJoinWhenColumnIsUsed(): void
    {
        $this->report->expects($this->once())
            ->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
            ->willReturn(
                ['e.id', ReportGeneratorEvent::CONTACT_PREFIX.'.email', 'comp.name']
            );

        $this->queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with(
                'e',
                MAUTIC_TABLE_PREFIX.'leads',
                ReportGeneratorEvent::CONTACT_PREFIX,
                ReportGeneratorEvent::CONTACT_PREFIX.'.id = e.lead_id'
            );

        $this->reportGeneratorEvent->addLeadLeftJoin($this->queryBuilder, 'e');
    }

    public function testAddLeadLeftJoinWhenCampaignIdColumnIsUsed(): void
    {
        $this->report->expects($this->exactly(5))
            ->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
            ->willReturn(
                ['e.id', 'clel.campaign_id', 'h.name']
            );

        $this->queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with(
                'e',
                MAUTIC_TABLE_PREFIX.'leads',
                ReportGeneratorEvent::CONTACT_PREFIX,
                ReportGeneratorEvent::CONTACT_PREFIX.'.id = e.lead_id'
            );

        $this->reportGeneratorEvent->addLeadLeftJoin($this->queryBuilder, 'e');
    }

    public function testAddIpAddressLeftJoinWhenColumnIsNotUsed(): void
    {
        $this->report->expects($this->once())
            ->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
            ->willReturn(['e.id', 't.name']);

        $this->queryBuilder->expects($this->never())
            ->method('leftJoin');

        $this->reportGeneratorEvent->addIpAddressLeftJoin($this->queryBuilder, 'e');
    }

    public function testAddIpAddressLeftJoinWhenColumnIsUsed(): void
    {
        $this->report->expects($this->once())
            ->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
            ->willReturn(
                ['e.id', ReportGeneratorEvent::IP_ADDRESS_PREFIX.'.address', 'comp.name']
            );

        $this->queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with(
                'e',
                MAUTIC_TABLE_PREFIX.'ip_addresses',
                ReportGeneratorEvent::IP_ADDRESS_PREFIX,
                ReportGeneratorEvent::IP_ADDRESS_PREFIX.'.id = e.ip_id'
            );

        $this->reportGeneratorEvent->addIpAddressLeftJoin($this->queryBuilder, 'e');
    }

    public function testHasColumnWithPrefix(): void
    {
        $this->report->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
            ->willReturn(['e.id', 'c.first_name', 'comp.name']);

        $this->assertTrue($this->reportGeneratorEvent->hasColumnWithPrefix('e'));
        $this->assertTrue($this->reportGeneratorEvent->hasColumnWithPrefix('c'));
        $this->assertTrue($this->reportGeneratorEvent->hasColumnWithPrefix('comp'));
        $this->assertFalse($this->reportGeneratorEvent->hasColumnWithPrefix('a'));
        $this->assertFalse($this->reportGeneratorEvent->hasColumnWithPrefix('lump'));
        $this->assertFalse($this->reportGeneratorEvent->hasColumnWithPrefix('c.'));
    }

    public function testUsesColumnWithPrefix(): void
    {
        $this->report->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
            ->willReturn(['e.id', 'c.first_name', 'comp.name']);

        $this->report->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
            ->willReturn(['e.id', 'c.first_name', 'comp.name']);

        $this->report->method('getFilters')
            ->willReturn(
                [
                    [
                        'column'    => 'foo.is_published',
                        'condition' => 'eq',
                        'value'     => '1',
                        'glue'      => 'and',
                        'dynamic'   => null,
                    ],
                ]
            );

        $this->assertTrue($this->reportGeneratorEvent->usesColumnWithPrefix('e'));
        $this->assertTrue($this->reportGeneratorEvent->usesColumnWithPrefix('c'));
        $this->assertTrue($this->reportGeneratorEvent->usesColumnWithPrefix('comp'));
        $this->assertTrue($this->reportGeneratorEvent->usesColumnWithPrefix('foo'));
        $this->assertFalse($this->reportGeneratorEvent->usesColumnWithPrefix('a'));
        $this->assertFalse($this->reportGeneratorEvent->usesColumnWithPrefix('lump'));
        $this->assertFalse($this->reportGeneratorEvent->usesColumnWithPrefix('c.'));
    }

    public function testUsesColumn(): void
    {
        $this->report->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
            ->willReturn(['e.id', 'c.first_name', 'comp.name']);

        $this->report->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
            ->willReturn(['e.id', 'c.first_name', 'comp.name']);

        $this->report->method('getFilters')
            ->willReturn(
                [
                    [
                        'column'    => 'foo.is_published',
                        'condition' => 'eq',
                        'value'     => '1',
                        'glue'      => 'and',
                        'dynamic'   => null,
                    ],
                ]
            );

        $this->assertTrue($this->reportGeneratorEvent->usesColumn('e.id'));
        $this->assertTrue($this->reportGeneratorEvent->usesColumn('c.first_name'));
        $this->assertTrue($this->reportGeneratorEvent->usesColumn('comp.name'));
        $this->assertTrue($this->reportGeneratorEvent->usesColumn('foo.is_published'));
        $this->assertFalse($this->reportGeneratorEvent->usesColumn('foo.bar'));
    }

    public function testAddCompanyLeftJoinWhenColumnIsNotUsed(): void
    {
        $this->report->expects($this->exactly(2))
      ->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
      ->willReturn(['e.id', 'e.title']);

        $this->queryBuilder->expects($this->never())
      ->method('leftJoin');

        $this->reportGeneratorEvent->addCompanyLeftJoin($this->queryBuilder, ReportGeneratorEvent::COMPANY_PREFIX);
    }

    public function testAddCompanyLeftJoinWhenColumnIsUsed(): void
    {
        $this->report->expects($this->once())
            ->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
            ->willReturn(['e.id', 'e.title', 'comp.name']);

        $this->queryBuilder->expects($this->exactly(2))
            ->method('leftJoin')
            ->withConsecutive(
                [
                    'l',
                    MAUTIC_TABLE_PREFIX.'companies_leads',
                    'companies_lead',
                    ReportGeneratorEvent::CONTACT_PREFIX.'.id =companies_lead.lead_id',
                ],
                [
                    'companies_lead',
                    MAUTIC_TABLE_PREFIX.'companies',
                    ReportGeneratorEvent::COMPANY_PREFIX,
                    'companies_lead.company_id = '.ReportGeneratorEvent::COMPANY_PREFIX.'.id',
                ]
            );
        $this->reportGeneratorEvent->addCompanyLeftJoin($this->queryBuilder, ReportGeneratorEvent::COMPANY_PREFIX);
    }

    public function testAddCompanyLeftJoinOnlyOnceWhenTableAlreadyJoined(): void
    {
        $this->report->expects($this->once())
            ->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
            ->willReturn(['e.id', 'e.title', 'comp.name']);

        $this->queryBuilder->expects($this->once())
      ->method('getQueryParts')
      ->willReturn([
          'join' => [
              'l' => [['joinTable' => MAUTIC_TABLE_PREFIX.'companies_leads', 'joinAlias' => ReportGeneratorEvent::COMPANY_LEAD_PREFIX]],
          ],
      ]);
        $this->queryBuilder->expects($this->never())
      ->method('leftJoin');

        $this->reportGeneratorEvent->addCompanyLeftJoin($this->queryBuilder, ReportGeneratorEvent::COMPANY_PREFIX);
    }
}
