<?php

namespace Mautic\PointBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\Group;
use Mautic\PointBundle\Entity\GroupContactScore;
use Mautic\PointBundle\Entity\GroupContactScoreRepository;

class GroupScoreRepositoryFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected GroupContactScoreRepository $repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->em->getRepository(GroupContactScore::class);
    }

    public function testCompareScore(): void
    {
        $contact = $this->createContact('score@example.com');

        $group = $this->createGroup('A');
        $this->addGroupContactScore($contact, $group, 7);
        $this->em->flush();

        $this->assertTrue($this->repository->compareScore($contact->getId(), $group->getId(), 7, 'eq'));
        $this->assertFalse($this->repository->compareScore($contact->getId(), $group->getId(), 8, 'eq'));

        $this->assertTrue($this->repository->compareScore($contact->getId(), $group->getId(), 8, 'neq'));
        $this->assertFalse($this->repository->compareScore($contact->getId(), $group->getId(), 7, 'neq'));

        $this->assertTrue($this->repository->compareScore($contact->getId(), $group->getId(), 6, 'gt'));
        $this->assertFalse($this->repository->compareScore($contact->getId(), $group->getId(), 7, 'gt'));

        $this->assertTrue($this->repository->compareScore($contact->getId(), $group->getId(), 8, 'lt'));
        $this->assertFalse($this->repository->compareScore($contact->getId(), $group->getId(), 7, 'lt'));

        $this->assertTrue($this->repository->compareScore($contact->getId(), $group->getId(), 7, 'gte'));
        $this->assertTrue($this->repository->compareScore($contact->getId(), $group->getId(), 6, 'gte'));
        $this->assertFalse($this->repository->compareScore($contact->getId(), $group->getId(), 8, 'gte'));

        $this->assertTrue($this->repository->compareScore($contact->getId(), $group->getId(), 7, 'lte'));
        $this->assertTrue($this->repository->compareScore($contact->getId(), $group->getId(), 8, 'lte'));
        $this->assertFalse($this->repository->compareScore($contact->getId(), $group->getId(), 6, 'lte'));
    }

    public function testCompareScoreContactWithoutScoreInGroup(): void
    {
        $contactWithoutScore = $this->createContact('no-score@example.com');
        $group               = $this->createGroup('A');
        $this->em->flush();

        $this->assertFalse($this->repository->compareScore($contactWithoutScore->getId(), $group->getId(), 0, 'eq'));
        $this->assertFalse($this->repository->compareScore($contactWithoutScore->getId(), $group->getId(), 1, 'eq'));
    }

    private function createContact(
        string $email,
    ): Lead {
        $lead = new Lead();
        $lead->setEmail($email);
        $this->em->persist($lead);

        return $lead;
    }

    private function createGroup(
        string $name,
    ): Group {
        $group = new Group();
        $group->setName($name);
        $this->em->persist($group);

        return $group;
    }

    private function addGroupContactScore(
        Lead $lead,
        Group $group,
        int $score,
    ): void {
        $groupContactScore = new GroupContactScore();
        $groupContactScore->setContact($lead);
        $groupContactScore->setGroup($group);
        $groupContactScore->setScore($score);
        $lead->addGroupScore($groupContactScore);
    }
}
