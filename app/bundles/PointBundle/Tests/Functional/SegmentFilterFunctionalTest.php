<?php

namespace Mautic\PointBundle\Tests\Functional\EmailTriggerTest;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PointBundle\Entity\League;
use Mautic\PointBundle\Entity\LeagueContactScore;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

class SegmentFilterFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testLeaguePointSegmentFilter(): void
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);
        $applicationTester = new ApplicationTester($application);

        $contactA = $this->createContact('contact-a@example.com');
        $contactB = $this->createContact('contact-b@example.com');
        $contactC = $this->createContact('contact-c@example.com');
        $leagueA  = $this->createLeague('League A');
        $this->em->flush();

        $this->addLeagueContactScore($contactA, $leagueA, 1);
        $this->addLeagueContactScore($contactB, $leagueA, 0);
        $this->em->persist($contactA);
        $this->em->persist($contactB);
        $this->em->flush();

        $segmentA = new LeadList();
        $segmentA->setName('League A points >= 1');
        $segmentA->setPublicName('League A points >= 1');
        $segmentA->setAlias('league-a-points-gte1');
        $segmentA->setIsPublished(true);
        $segmentA->setFilters([
            [
                'glue'       => 'and',
                'field'      => 'league_points_'.$leagueA->getId(),
                'object'     => 'leagues',
                'type'       => 'number',
                'operator'   => 'gte',
                'properties' => [
                    'filter' => '1',
                ],
            ],
        ]);
        $this->em->persist($segmentA);
        $this->em->flush();

        // Force Doctrine to re-fetch the entities otherwise the campaign won't know about any events.
        $this->em->clear();

        // Execute segment update command.
        $exitCode = $applicationTester->run(
            [
                'command'       => 'mautic:segments:update',
                '-i'            => $segmentA->getId(),
            ]
        );

        $this->assertSame(0, $exitCode, $applicationTester->getDisplay());

        $this->client->request('GET', '/api/contacts?search=segment:league-a-points-gte1');
        $clientResponse = $this->client->getResponse();
        $this->assertTrue($this->client->getResponse()->isOk());
        $response = json_decode($clientResponse->getContent(), true);
        $this->assertEquals(1, (int) $response['total']);
        $contactIds = array_column($response['contacts'], 'id');
        $this->assertContains((int) $contactA->getId(), $contactIds);
        $this->assertNotContains((int) $contactB->getId(), $contactIds);
        $this->assertNotContains((int) $contactC->getId(), $contactIds);
    }

    private function createContact(
        string $email
    ): Lead {
        $lead = new Lead();
        $lead->setEmail($email);
        $this->em->persist($lead);

        return $lead;
    }

    private function createLeague(
        string $name
    ): League {
        $league = new League();
        $league->setName($name);
        $this->em->persist($league);

        return $league;
    }

    private function addLeagueContactScore(
        Lead $lead,
        League $league,
        int $score
    ): void {
        $leagueContactScore = new LeagueContactScore();
        $leagueContactScore->setContact($lead);
        $leagueContactScore->setLeague($league);
        $leagueContactScore->setScore($score);
        $lead->addLeagueScore($leagueContactScore);
    }
}
