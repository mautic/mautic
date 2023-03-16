<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Entity\League;
use Mautic\PointBundle\Entity\LeagueContactScore;
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

    public function testContactPointLogReportWithLeague(): void
    {
        $this->createTestContactWithLeaguePoints();
        $report = new Report();
        $report->setName('Contact point log');
        $report->setSource('lead.pointlog');
        $report->setColumns(['lp.type', 'lp.event_name', 'l.email', 'lp.delta', 'pl.name']);
        $this->em->persist($report);
        $this->em->flush();
        $this->em->clear();

        //-- test report table in mautic panel
        $crawler            = $this->client->request(Request::METHOD_GET, "/s/reports/view/{$report->getId()}");
        $crawlerReportTable = $crawler->filterXPath('//table[@id="reportTable"]')->first();

        // convert html table to php array
        $crawlerReportTable = $this->domTableToArray($crawlerReportTable);

        $this->assertSame([
            // no., event_type, event_name,      email,       points_delta, league_name
            ['1', 'test type', 'Adjust points', 'test1@example.com', '5', 'League A'],
            ['2', 'test type', 'Adjust points', 'test2@example.com', '10', 'League A'],
            ['3', 'test type', 'Adjust points', 'test2@example.com', '2', 'League B'],
            ['4', 'test type', 'Adjust points', 'test3@example.com', '10', 'League A'],
            ['5', 'test type', 'Adjust points', 'test3@example.com', '2', 'League B'],
        ], array_slice($crawlerReportTable, 1, 5));

        //-- test API report data
        $this->client->request(Request::METHOD_GET, "/api/reports/{$report->getId()}");
        $clientResponse = $this->client->getResponse();
        $result         = json_decode($clientResponse->getContent(), true);
        $this->assertSame([
            [
                'type'        => 'test type',
                'event_name'  => 'Adjust points',
                'email'       => 'test1@example.com',
                'delta'       => '5',
                'league_name' => 'League A',
            ],
            [
                'type'        => 'test type',
                'event_name'  => 'Adjust points',
                'email'       => 'test2@example.com',
                'delta'       => '10',
                'league_name' => 'League A',
            ],
            [
                'type'        => 'test type',
                'event_name'  => 'Adjust points',
                'email'       => 'test2@example.com',
                'delta'       => '2',
                'league_name' => 'League B',
            ],
            [
                'type'        => 'test type',
                'event_name'  => 'Adjust points',
                'email'       => 'test3@example.com',
                'delta'       => '10',
                'league_name' => 'League A',
            ],
            [
                'type'        => 'test type',
                'event_name'  => 'Adjust points',
                'email'       => 'test3@example.com',
                'delta'       => '2',
                'league_name' => 'League B',
            ],
        ], $result['data']);
    }

    public function testLeagueScoreReport(): void
    {
        $this->createTestContactWithLeaguePoints();
        $report = new Report();
        $report->setName('League score report');
        $report->setSource('league.score');
        $report->setColumns(['pl.name', 'ls.score', 'l.email']);
        $this->em->persist($report);
        $this->em->flush();
        $this->em->clear();

        //-- test report table in mautic panel
        $crawler            = $this->client->request(Request::METHOD_GET, "/s/reports/view/{$report->getId()}");
        $crawlerReportTable = $crawler->filterXPath('//table[@id="reportTable"]')->first();

        // convert html table to php array
        $crawlerReportTable = $this->domTableToArray($crawlerReportTable);

        $this->assertSame([
            // no., league_name, league_score, email
            ['1', 'League A', '5', 'test1@example.com'],
            ['2', 'League A', '10', 'test2@example.com'],
            ['3', 'League B', '2', 'test2@example.com'],
            ['4', 'League A', '10', 'test3@example.com'],
            ['5', 'League B', '2', 'test3@example.com'],
        ], array_slice($crawlerReportTable, 1, 5));

        //-- test API report data
        $this->client->request(Request::METHOD_GET, "/api/reports/{$report->getId()}");
        $clientResponse = $this->client->getResponse();
        $result         = json_decode($clientResponse->getContent(), true);

        $this->assertSame([
            [
                'league_name'  => 'League A',
                'league_score' => '5',
                'email'        => 'test1@example.com',
            ],
            [
                'league_name'  => 'League A',
                'league_score' => '10',
                'email'        => 'test2@example.com',
            ],
            [
                'league_name'  => 'League B',
                'league_score' => '2',
                'email'        => 'test2@example.com',
            ],
            [
                'league_name'  => 'League A',
                'league_score' => '10',
                'email'        => 'test3@example.com',
            ],
            [
                'league_name'  => 'League B',
                'league_score' => '2',
                'email'        => 'test3@example.com',
            ],
        ], $result['data']);
    }

    private function createTestContactWithLeaguePoints(): void
    {
        $contactModel = self::$container->get('mautic.lead.model.lead');
        \assert($contactModel instanceof LeadModel);

        $leagueA = $this->createLeague('League A');
        $leagueB = $this->createLeague('League B');
        $this->em->flush();

        $contacts = [
            $this->createContact('test1@example.com'),
            $this->createContact('test2@example.com'),
            $this->createContact('test3@example.com'),
        ];
        $contactModel->saveEntities($contacts);

        $this->adjustContactPoints($contacts[0], 5, $leagueA);
        $this->adjustContactPoints($contacts[1], 10, $leagueA);
        $this->adjustContactPoints($contacts[2], 10, $leagueA);
        $this->adjustContactPoints($contacts[2], 2, $leagueB);
        $this->adjustContactPoints($contacts[1], 2, $leagueB);

        $contactModel->saveEntities($contacts);
    }

    private function createContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $this->em->persist($contact);

        return $contact;
    }

    private function adjustContactPoints(Lead $contact, int $points, League $league): void
    {
        $ipAddress = new IpAddress();
        $ipAddress->setIpAddress('127.0.0.1');
        $contact->addPointsChangeLogEntry(
            'test type',
            'Adjust points',
            'test action',
            $points,
            $ipAddress,
            $league
        );
        $contact->adjustPoints($points);
        $leagueContactScore = new LeagueContactScore();
        $leagueContactScore->setContact($contact);
        $leagueContactScore->setLeague($league);
        $leagueContactScore->setScore($points);
        $contact->addLeagueScore($leagueContactScore);
    }

    private function createLeague(
        string $name
    ): League {
        $league = new League();
        $league->setName($name);
        $this->em->persist($league);

        return $league;
    }

    /**
     * @return array<int,array<int,mixed>>
     */
    private function domTableToArray(Crawler $crawler): array
    {
        return $crawler->filter('tr')->each(function ($tr) {
            return $tr->filter('td')->each(function ($td) {
                return trim($td->text());
            });
        });
    }
}
