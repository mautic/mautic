<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Helper\Chart;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Segment\Stat\ChartQuery\SegmentContactsLineChartQuery;

class ChartQueryTest extends MauticMysqlTestCase
{
    public function testSegmentContactsLineChartQuery(): void
    {
        $lead = new Lead();
        $lead->setEmail('test@test.com');

        // Create a segment
        $segment = new LeadList();
        $segment->setName('Test Segment A');
        $segment->setPublicName('Test Segment A');
        $segment->setAlias('test-segment-a');

        $leadEventLogs = new LeadEventLog();
        $leadEventLogs->setLead($lead);
        $leadEventLogs->setBundle('lead');
        $leadEventLogs->setObject('segment');
        $leadEventLogs->setAction('added');
        $leadEventLogs->setObjectId($segment->getId());
        $leadEventLogs->setDateAdded(new \DateTime('2023-01-31 23:49:59'));

        $leadEventLogs = new LeadEventLog();
        $leadEventLogs->setLead($lead);
        $leadEventLogs->setBundle('lead');
        $leadEventLogs->setObject('segment');
        $leadEventLogs->setAction('added');
        $leadEventLogs->setObjectId($segment->getId());
        $leadEventLogs->setDateAdded(new \DateTime('2023-01-30 23:49:59'));

        $this->em->persist($lead);
        $this->em->persist($segment);
        $this->em->persist($leadEventLogs);

        $this->em->flush();

        $dateFrom = new \DateTime('2023-01-27');
        $dateTo   = new \DateTime('2023-01-30');

        $filter = '{"leadlist_id":{"value":"'.$segment->getId().'","list_column_name":"t.lead_id"},"t.leadlist_id":"'.$segment->getId().'"}';
        $query  = new SegmentContactsLineChartQuery($this->em->getConnection(), $dateFrom, $dateTo, json_decode($filter, true));

        // assume UTC 2023-01-30 23:49:59 is 2023-01-31 00:49:59 in UTC+1, then do not add it to graph
        $this->assertEmpty(array_filter($query->getAddedEventLogStats()));
    }
}
