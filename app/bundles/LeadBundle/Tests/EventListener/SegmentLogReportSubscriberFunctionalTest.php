<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\LeadBundle\EventListener\SegmentLogReportSubscriber;
use Mautic\ReportBundle\Entity\Report;

class SegmentLogReportSubscriberFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    public function testOnReportGenerate(): void
    {
        $contactJohn = $this->createLead('John', 'Doe', 'john@doe.corp');
        $contactJane = $this->createLead('Jane', 'Alien', 'jane@alien.corp');
        $contactBob  = $this->createLead('Bob', 'Alien', 'bob@alien.corp');

        $segment = $this->createSegment('Segment A', []);
        $this->em->flush();

        $logJohn = $this->createLeadEventLogEntry($contactJohn, 'lead', 'segment', 'added', $segment->getId());
        $logJohn->setDateAdded(new \DateTime('-1 day'));

        $logJane = $this->createLeadEventLogEntry($contactJane, 'lead', 'segment', 'added', $segment->getId());
        $logJane->setDateAdded(new \DateTime('-3 hours'));

        $logBob  = $this->createLeadEventLogEntry($contactBob, 'lead', 'segment', 'added', $segment->getId());
        $logBob->setDateAdded(new \DateTime('-1 day'));

        $logBobRemoved = $this->createLeadEventLogEntry($contactBob, 'lead', 'segment', 'removed', $segment->getId());
        $logBobRemoved->setDateAdded(new \DateTime('-4 hours'));

        $report = $this->createReport();

        $this->em->flush();
        $this->em->clear();

        $this->client->request('GET', 'api/reports/'.$report->getId());
        $clientResponse = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(200, $clientResponse->getContent());

        $response = json_decode($clientResponse->getContent(), true);
        $this->assertSame(3, $response['totalResults']);
        $this->assertCount(3, $response['data']);

        $data = $response['data'];

        $this->assertSame('john@doe.corp', $data[0]['email']);
        $this->assertNotEmpty($data[0]['date_added1']);
        $this->assertEmpty($data[0]['date_added2']);

        $this->assertSame('jane@alien.corp', $data[1]['email']);
        $this->assertNotEmpty($data[1]['date_added1']);
        $this->assertEmpty($data[1]['date_added2']);

        $this->assertSame('bob@alien.corp', $data[2]['email']);
        $this->assertNotEmpty($data[2]['date_added1']);
        $this->assertNotEmpty($data[2]['date_added2']);
    }

    private function createReport(): Report
    {
        $report = new Report();
        $report->setName('Segment Log Report');
        $report->setSource(SegmentLogReportSubscriber::SEGMENT_LOG);
        $report->setColumns(['l.id', 'l.email', 'l.firstname', 'l.lastname', 'log_added.object_id', 'log_added.date_added', 'log_removed.date_added']);
        $report->setGroupBy(['l.id', 'log_added.object_id', 'log_added.date_added', 'log_removed.date_added']);

        $this->em->persist($report);

        return $report;
    }
}
