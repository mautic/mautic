<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Functional;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\Group;
use Mautic\PointBundle\Entity\GroupContactScore;
use Mautic\ReportBundle\Entity\Report;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

class ReportSubscriberFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->useCleanupRollback = false;

        parent::setUp();
    }

    public function testContactPointLogReportWithGroup(): void
    {
        $this->createTestContactWithGroupPoints();
        $report = new Report();
        $report->setName('Contact point log');
        $report->setSource('lead.pointlog');
        $report->setColumns(['lp.type', 'lp.event_name', 'l.email', 'lp.delta', 'pl.name']);
        $report->setTableOrder([[
            'column'    => 'lp.delta',
            'direction' => 'DESC',
        ]]);
        $this->em->persist($report);
        $this->em->flush();
        $this->em->clear();

        // -- test report table in mautic panel
        $crawler            = $this->client->request(Request::METHOD_GET, "/s/reports/view/{$report->getId()}");
        $crawlerReportTable = $crawler->filterXPath('//table[@id="reportTable"]')->first();

        // convert html table to php array
        $crawlerReportTable = $this->domTableToArray($crawlerReportTable);

        $this->assertSame([
            // no., event_type, event_name,      email,       points_delta, group_name
            ['1', 'test type', 'Adjust points', 'test2@example.com', '15', 'Group A'],
            ['2', 'test type', 'Adjust points', 'test3@example.com', '10', 'Group A'],
            ['3', 'test type', 'Adjust points', 'test1@example.com', '5', 'Group A'],
            ['4', 'test type', 'Adjust points', 'test3@example.com', '2', 'Group B'],
            ['5', 'test type', 'Adjust points', 'test2@example.com', '1', 'Group B'],
        ], array_slice($crawlerReportTable, 1, 5));

        // -- test API report data
        $this->client->request(Request::METHOD_GET, "/api/reports/{$report->getId()}");
        $clientResponse = $this->client->getResponse();
        $result         = json_decode($clientResponse->getContent(), true);
        $this->assertSame([
            [
                'type'        => 'test type',
                'event_name'  => 'Adjust points',
                'email'       => 'test2@example.com',
                'delta'       => '15',
                'group_name'  => 'Group A',
            ],
            [
                'type'        => 'test type',
                'event_name'  => 'Adjust points',
                'email'       => 'test3@example.com',
                'delta'       => '10',
                'group_name'  => 'Group A',
            ],
            [
                'type'        => 'test type',
                'event_name'  => 'Adjust points',
                'email'       => 'test1@example.com',
                'delta'       => '5',
                'group_name'  => 'Group A',
            ],
            [
                'type'        => 'test type',
                'event_name'  => 'Adjust points',
                'email'       => 'test3@example.com',
                'delta'       => '2',
                'group_name'  => 'Group B',
            ],
            [
                'type'        => 'test type',
                'event_name'  => 'Adjust points',
                'email'       => 'test2@example.com',
                'delta'       => '1',
                'group_name'  => 'Group B',
            ],
        ], $result['data']);
    }

    public function testGroupScoreReport(): void
    {
        $this->createTestContactWithGroupPoints();
        $report = new Report();
        $report->setName('Group score report');
        $report->setSource('group.score');
        $report->setColumns(['pl.name', 'ls.score', 'l.email']);
        $report->setTableOrder([[
            'column'    => 'ls.score',
            'direction' => 'DESC',
        ]]);
        $this->em->persist($report);
        $this->em->flush();
        $this->em->clear();

        // -- test report table in mautic panel
        $crawler            = $this->client->request(Request::METHOD_GET, "/s/reports/view/{$report->getId()}");
        $crawlerReportTable = $crawler->filterXPath('//table[@id="reportTable"]')->first();

        // convert html table to php array
        $crawlerReportTable = $this->domTableToArray($crawlerReportTable);

        $this->assertSame([
            // no., group_name, group_score, email
            ['1', 'Group A', '15', 'test2@example.com'],
            ['2', 'Group A', '10', 'test3@example.com'],
            ['3', 'Group A', '5', 'test1@example.com'],
            ['4', 'Group B', '2', 'test3@example.com'],
            ['5', 'Group B', '1', 'test2@example.com'],
        ], array_slice($crawlerReportTable, 1, 5));

        // -- test API report data
        $this->client->request(Request::METHOD_GET, "/api/reports/{$report->getId()}");
        $clientResponse = $this->client->getResponse();
        $result         = json_decode($clientResponse->getContent(), true);

        $this->assertSame([
            [
                'group_name'   => 'Group A',
                'group_score'  => '15',
                'email'        => 'test2@example.com',
            ],
            [
                'group_name'   => 'Group A',
                'group_score'  => '10',
                'email'        => 'test3@example.com',
            ],
            [
                'group_name'   => 'Group A',
                'group_score'  => '5',
                'email'        => 'test1@example.com',
            ],
            [
                'group_name'   => 'Group B',
                'group_score'  => '2',
                'email'        => 'test3@example.com',
            ],
            [
                'group_name'   => 'Group B',
                'group_score'  => '1',
                'email'        => 'test2@example.com',
            ],
        ], $result['data']);
    }

    private function createTestContactWithGroupPoints(): void
    {
        $contactModel = static::getContainer()->get('mautic.lead.model.lead');

        $groupA = $this->createGroup('Group A');
        $groupB = $this->createGroup('Group B');
        $this->em->flush();

        $contacts = [
            $this->createContact('test1@example.com'),
            $this->createContact('test2@example.com'),
            $this->createContact('test3@example.com'),
        ];
        $contactModel->saveEntities($contacts);

        $this->adjustContactPoints($contacts[0], 5, $groupA);
        $this->adjustContactPoints($contacts[1], 15, $groupA);
        $this->adjustContactPoints($contacts[2], 10, $groupA);
        $this->adjustContactPoints($contacts[2], 2, $groupB);
        $this->adjustContactPoints($contacts[1], 1, $groupB);

        $contactModel->saveEntities($contacts);
    }

    private function createContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $this->em->persist($contact);

        return $contact;
    }

    private function adjustContactPoints(Lead $contact, int $points, Group $group): void
    {
        $ipAddress = new IpAddress();
        $ipAddress->setIpAddress('127.0.0.1');
        $contact->addPointsChangeLogEntry(
            'test type',
            'Adjust points',
            'test action',
            $points,
            $ipAddress,
            $group
        );
        $contact->adjustPoints($points);
        $groupContactScore = new GroupContactScore();
        $groupContactScore->setContact($contact);
        $groupContactScore->setGroup($group);
        $groupContactScore->setScore($points);
        $contact->addGroupScore($groupContactScore);
    }

    private function createGroup(
        string $name,
    ): Group {
        $group = new Group();
        $group->setName($name);
        $this->em->persist($group);

        return $group;
    }

    /**
     * @return array<int,array<int,mixed>>
     */
    private function domTableToArray(Crawler $crawler): array
    {
        return $crawler->filter('tr')->each(fn ($tr) => $tr->filter('td')->each(fn ($td) => trim($td->text())));
    }
}
