<?php

namespace Mautic\PointBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PointBundle\Entity\Group;
use Mautic\PointBundle\Entity\GroupContactScore;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

class SegmentFilterFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testGroupPointSegmentFilter(): void
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);
        $applicationTester = new ApplicationTester($application);

        $contactA = $this->createContact('contact-a@example.com');
        $contactB = $this->createContact('contact-b@example.com');
        $contactC = $this->createContact('contact-c@example.com');
        $groupA   = $this->createGroup('Group A');
        $this->em->flush();

        $this->addGroupContactScore($contactA, $groupA, 1);
        $this->addGroupContactScore($contactB, $groupA, 0);
        $this->em->persist($contactA);
        $this->em->persist($contactB);
        $this->em->flush();

        $segmentA = new LeadList();
        $segmentA->setName('Group A points >= 1');
        $segmentA->setPublicName('Group A points >= 1');
        $segmentA->setAlias('group-a-points-gte1');
        $segmentA->setIsPublished(true);
        $segmentA->setFilters([
            [
                'glue'       => 'and',
                'field'      => 'group_points_'.$groupA->getId(),
                'object'     => 'groups',
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

        $this->client->request('GET', '/api/contacts?search=segment:group-a-points-gte1');
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
