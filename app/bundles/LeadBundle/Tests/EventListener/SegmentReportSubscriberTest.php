<?php

declare(strict_types=1);

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\LeadBundle\EventListener\SegmentReportSubscriber;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Helper\ReportHelper;
use Symfony\Component\Translation\TranslatorInterface;

class SegmentReportSubscriberTest extends \PHPUnit\Framework\TestCase
{
    public function testNotRelevantContext(): void
    {
        $translatorMock          = $this->createMock(TranslatorInterface::class);
        $channelListHelperMock   = $this->createMock(ChannelListHelper::class);
        $reportHelperMock        = $this->createMock(ReportHelper::class);
        $fieldsBuilderMock       = $this->createMock(FieldsBuilder::class);
        $reportMock              = $this->createMock(Report::class);
        $queryBuilder            = $this->createMock(QueryBuilder::class);
        $reportBuilderEvent      = new ReportBuilderEvent($translatorMock, $channelListHelperMock, 'badContext', [], $reportHelperMock);
        $segmentReportSubscriber = new SegmentReportSubscriber($fieldsBuilderMock);
        $segmentReportSubscriber->onReportBuilder($reportBuilderEvent);

        $this->assertSame([], $reportBuilderEvent->getTables());

        $reportMock->expects($this->once())
            ->method('getSource')
            ->with()
            ->willReturn('badContext');

        $queryBuilder->expects($this->never())
            ->method('from');

        $reportGeneratorEvent = new ReportGeneratorEvent($reportMock, [], $queryBuilder, $channelListHelperMock);
        $segmentReportSubscriber->onReportGenerate($reportGeneratorEvent);
    }

    public function testReportBuilder(): void
    {
        $translatorMock        = $this->createMock(TranslatorInterface::class);
        $channelListHelperMock = $this->createMock(ChannelListHelper::class);
        $reportHelperMock      = $this->createMock(ReportHelper::class);
        $fieldsBuilderMock     = $this->createMock(FieldsBuilder::class);

        $leadColumns = [
            'xx.yyy' => [
                'label' => 'first',
                'type'  => 'bool',
            ],
        ];

        $filterColumns = [
            'filter' => [
                'label' => 'second',
                'type'  => 'text',
            ],
        ];

        $fieldsBuilderMock->expects($this->once())
            ->method('getLeadFieldsColumns')
            ->with('l.')
            ->willReturn($leadColumns);

        $fieldsBuilderMock->expects($this->once())
            ->method('getLeadFilter')
            ->with('l.', 'lll.')
            ->willReturn($filterColumns);

        $reportBuilderEvent = new ReportBuilderEvent($translatorMock, $channelListHelperMock, 'segment.membership', [], $reportHelperMock);

        $segmentReportSubscriber = new SegmentReportSubscriber($fieldsBuilderMock);
        $segmentReportSubscriber->onReportBuilder($reportBuilderEvent);

        $expected = [
            'segment.membership' => [
                'display_name' => 'mautic.lead.report.segment.membership',
                'columns'      => [
                    'xx.yyy' => [
                        'label' => null,
                        'type'  => 'bool',
                        'alias' => 'yyy',
                    ],
                    'lll.manually_removed' => [
                        'label' => null,
                        'type'  => 'bool',
                        'alias' => 'manually_removed',
                    ],
                    'lll.manually_added' => [
                        'label' => null,
                        'type'  => 'bool',
                        'alias' => 'manually_added',
                    ],
                    's.id' => [
                        'label' => null,
                        'type'  => 'int',
                        'alias' => 's_id',
                    ],
                    's.name' => [
                        'label' => null,
                        'type'  => 'string',
                        'alias' => 's_name',
                    ],
                    's.created_by_user' => [
                        'label' => null,
                        'type'  => 'string',
                        'alias' => 's_created_by_user',
                    ],
                    's.date_added' => [
                        'label' => null,
                        'type'  => 'datetime',
                        'alias' => 's_date_added',
                    ],
                    's.modified_by_user' => [
                        'label' => null,
                        'type'  => 'string',
                        'alias' => 's_modified_by_user',
                    ],
                    's.date_modified' => [
                        'label' => null,
                        'type'  => 'datetime',
                        'alias' => 's_date_modified',
                    ],
                    's.description' => [
                        'label' => null,
                        'type'  => 'string',
                        'alias' => 's_description',
                    ],
                    's.is_published' => [
                        'label' => null,
                        'type'  => 'bool',
                        'alias' => 's_is_published',
                    ],
                ],
                'filters' => [
                    'filter' => [
                        'label' => null,
                        'type'  => 'text',
                        'alias' => 'filter',
                    ],
                ],
                'group' => 'contacts',
            ],
        ];

        $this->assertSame($expected, $reportBuilderEvent->getTables());
    }

    public function testReportGenerate(): void
    {
        if (!defined('MAUTIC_TABLE_PREFIX')) {
            define('MAUTIC_TABLE_PREFIX', '');
        }

        $channelListHelperMock   = $this->createMock(ChannelListHelper::class);
        $fieldsBuilderMock       = $this->createMock(FieldsBuilder::class);
        $segmentReportSubscriber = new SegmentReportSubscriber($fieldsBuilderMock);
        $reportMock              = $this->createMock(Report::class);
        $queryBuilder            = $this->createMock(QueryBuilder::class);

        $reportMock->expects($this->once())
            ->method('getSource')
            ->with()
            ->willReturn('segment.membership');

        $reportMock->expects($this->exactly(2))
            ->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
            ->with()
            ->willReturn([]);

        $reportMock->expects($this->exactly(1))
            ->method('getFilters')
            ->with()
            ->willReturn([]);

        $queryBuilder->expects($this->once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'lll')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->exactly(2))
            ->method('leftJoin')
            ->withConsecutive(
                ['lll', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = lll.lead_id'],
                ['lll', MAUTIC_TABLE_PREFIX.'lead_lists', 's', 's.id = lll.leadlist_id']
            )
            ->willReturn($queryBuilder);

        $reportGeneratorEvent = new ReportGeneratorEvent($reportMock, [], $queryBuilder, $channelListHelperMock);
        $segmentReportSubscriber->onReportGenerate($reportGeneratorEvent);

        $this->assertSame($queryBuilder, $reportGeneratorEvent->getQueryBuilder());
    }
}
