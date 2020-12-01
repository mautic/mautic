<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\EventListener\ReportSubscriber;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Symfony\Component\Translation\TranslatorInterface;

class ReportSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private $connectionMock;
    private $companyReportDataMock;
    private $statRepository;
    private $generatedColumnsProvider;

    /**
     * @var ReportSubscriber
     */
    private $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connectionMock           = $this->createMock(Connection::class);
        $this->companyReportDataMock    = $this->createMock(CompanyReportData::class);
        $this->statRepository           = $this->createMock(StatRepository::class);
        $this->generatedColumnsProvider = $this->createMock(GeneratedColumnsProviderInterface::class);
        $this->subscriber               = new ReportSubscriber(
            $this->connectionMock,
            $this->companyReportDataMock,
            $this->statRepository,
            $this->generatedColumnsProvider
        );
    }

    public function testOnReportGraphGenerateForEmailContextWithEmailGraph()
    {
        $eventMock        = $this->createMock(ReportGraphEvent::class);
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $chartQueryMock   = $this->createMock(ChartQuery::class);
        $statementMock    = $this->createMock(Statement::class);
        $translatorMock   = $this->createMock(TranslatorInterface::class);

        $queryBuilderMock->method('execute')->willReturn($statementMock);

        $eventMock->expects($this->at(0))
            ->method('getRequestedGraphs')
            ->willReturn(['mautic.email.graph.pie.read.ingored.unsubscribed.bounced']);

        $eventMock->expects($this->at(1))
            ->method('checkContext')
            ->with(['email.stats', 'emails'])
            ->willReturn(true);

        $eventMock->expects($this->at(2))
            ->method('checkContext')
            ->with('emails')
            ->willReturn(true);

        $eventMock->expects($this->at(3))
            ->method('getQueryBuilder')
            ->willReturn($queryBuilderMock);

        $eventMock->expects($this->at(4))
            ->method('getOptions')
            ->willReturn(['chartQuery' => $chartQueryMock, 'translator' => $translatorMock]);

        $queryBuilderMock->expects($this->once())
            ->method('select')
            ->with('SUM(DISTINCT e.sent_count) as sent_count, SUM(DISTINCT e.read_count) as read_count, count(CASE WHEN dnc.id  and dnc.reason = '.DoNotContact::UNSUBSCRIBED.' THEN 1 ELSE null END) as unsubscribed, count(CASE WHEN dnc.id  and dnc.reason = '.DoNotContact::BOUNCED.' THEN 1 ELSE null END) as bounced');

        $this->subscriber->onReportGraphGenerate($eventMock);
    }
}
