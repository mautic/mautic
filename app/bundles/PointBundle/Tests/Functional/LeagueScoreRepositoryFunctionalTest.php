<?php

namespace Mautic\PointBundle\Tests\Functional\EmailTriggerTest;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\League;
use Mautic\PointBundle\Entity\LeagueContactScore;
use Mautic\PointBundle\Entity\LeagueContactScoreRepository;

class LeagueScoreRepositoryFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected LeagueContactScoreRepository $repository;

    public function setUp(): void
    {
        parent::setUp();

        $repository = $this->em->getRepository(LeagueContactScore::class);
        \assert($repository instanceof LeagueContactScoreRepository);
        $this->repository = $repository;
    }

    public function testCompareScore(): void
    {
        $contact = $this->createContact('score@example.com');

        $league = $this->createLeague('A');
        $this->addLeagueContactScore($contact, $league, 7);
        $this->em->flush();

        $this->assertTrue($this->repository->compareScore($contact->getId(), $league->getId(), 7, 'eq'));
        $this->assertFalse($this->repository->compareScore($contact->getId(), $league->getId(), 8, 'eq'));

        $this->assertTrue($this->repository->compareScore($contact->getId(), $league->getId(), 8, 'neq'));
        $this->assertFalse($this->repository->compareScore($contact->getId(), $league->getId(), 7, 'neq'));

        $this->assertTrue($this->repository->compareScore($contact->getId(), $league->getId(), 6, 'gt'));
        $this->assertFalse($this->repository->compareScore($contact->getId(), $league->getId(), 7, 'gt'));

        $this->assertTrue($this->repository->compareScore($contact->getId(), $league->getId(), 8, 'lt'));
        $this->assertFalse($this->repository->compareScore($contact->getId(), $league->getId(), 7, 'lt'));

        $this->assertTrue($this->repository->compareScore($contact->getId(), $league->getId(), 7, 'gte'));
        $this->assertTrue($this->repository->compareScore($contact->getId(), $league->getId(), 6, 'gte'));
        $this->assertFalse($this->repository->compareScore($contact->getId(), $league->getId(), 8, 'gte'));

        $this->assertTrue($this->repository->compareScore($contact->getId(), $league->getId(), 7, 'lte'));
        $this->assertTrue($this->repository->compareScore($contact->getId(), $league->getId(), 8, 'lte'));
        $this->assertFalse($this->repository->compareScore($contact->getId(), $league->getId(), 6, 'lte'));
    }

    public function testCompareScoreContactWithoutScoreInLeague(): void
    {
        $contactWithoutScore = $this->createContact('no-score@example.com');
        $league              = $this->createLeague('A');
        $this->em->flush();

        $this->assertFalse($this->repository->compareScore($contactWithoutScore->getId(), $league->getId(), 0, 'eq'));
        $this->assertFalse($this->repository->compareScore($contactWithoutScore->getId(), $league->getId(), 1, 'eq'));
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
