<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Exception;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Command\SegmentCountCacheCommand;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Entity\ListLead;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SegmentFilterOnUpdateCommandFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @throws Exception
     */
    public function testSegmentFilterOnUpdateCommand(): void
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);
        $applicationTester = new ApplicationTester($application);

        $contacts   = $this->saveContacts();
        $segmentA   = $this->saveSegmentA();
        $segmentAId = $segmentA->getId();

        // Run segments update command.
        $exitCode = $applicationTester->run(['command' => 'mautic:segments:update', '-i' => $segmentAId]);
        self::assertSame(0, $exitCode, $applicationTester->getDisplay());
        self::assertCount(5, $this->em->getRepository(ListLead::class)->findBy(['list' => $segmentAId]));

        $segmentB   = $this->saveSegmentB($segmentAId);
        $segmentBId = $segmentB->getId();
        // Run segments update command.
        $exitCode = $applicationTester->run(['command' => 'mautic:segments:update', '-i' => $segmentBId]);
        self::assertSame(0, $exitCode, $applicationTester->getDisplay());
        self::assertCount(3, $this->em->getRepository(ListLead::class)->findBy(['list' => $segmentBId]));
    }

    private function saveContacts(): array
    {
        // Add 10 contacts
        /** @var LeadRepository $contactRepo */
        $contactRepo = $this->em->getRepository(Lead::class);
        $contacts    = [];

        for ($i = 0; $i <= 10; ++$i) {
            $contact = new Lead();
            $contact->setFirstname('fn'.$i);
            $contact->setLastname('ln'.$i);
            $contacts[] = $contact;
        }

        $contactRepo->saveEntities($contacts);

        return $contacts;
    }

    private function saveSegmentA(): LeadList
    {
        // Add 1 segment
        /** @var LeadListRepository $contactRepo */
        $segmentRepo = $this->em->getRepository(LeadList::class);
        $segment     = new LeadList();
        $filters     = [
            [
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'firstname',
                'type'       => 'text',
                'operator'   => '=',
                'properties' => ['filter' => 'fn1'],
            ],
            [
                'object'     => 'lead',
                'glue'       => 'or',
                'field'      => 'lastname',
                'type'       => 'text',
                'operator'   => '=',
                'properties' => ['filter' => 'ln1'],
            ],
            [
                'object'     => 'lead',
                'glue'       => 'or',
                'field'      => 'firstname',
                'type'       => 'text',
                'operator'   => '=',
                'properties' => ['filter' => 'fn2'],
            ],
            [
                'object'     => 'lead',
                'glue'       => 'or',
                'field'      => 'firstname',
                'type'       => 'text',
                'operator'   => '=',
                'properties' => ['filter' => 'fn3'],
            ],
            [
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'lastname',
                'type'       => 'text',
                'operator'   => '=',
                'properties' => ['filter' => 'ln3'],
            ],
            [
                'object'     => 'lead',
                'glue'       => 'or',
                'field'      => 'firstname',
                'type'       => 'text',
                'operator'   => '=',
                'properties' => ['filter' => 'fn4'],
            ],
            [
                'object'     => 'lead',
                'glue'       => 'or',
                'field'      => 'lastname',
                'type'       => 'text',
                'operator'   => '=',
                'properties' => ['filter' => 'ln5'],
            ],
        ];

        $segment->setName('Segment A')
            ->setFilters($filters)
            ->setAlias('segment-a');
        $segmentRepo->saveEntity($segment);

        return $segment;
    }

    private function saveSegmentB(int $segmentAId): LeadList
    {
        // Add 1 segment
        /** @var LeadListRepository $contactRepo */
        $segmentRepo = $this->em->getRepository(LeadList::class);
        $segment     = new LeadList();
        $filters     = [
            [
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'firstname',
                'type'       => 'text',
                'operator'   => '=',
                'properties' => ['filter' => 'fn6'],
            ],
            [
                'object'     => 'lead',
                'glue'       => 'or',
                'field'      => 'firstname',
                'type'       => 'text',
                'operator'   => '=',
                'properties' => ['filter' => 'fn2'],
            ],
            [
                'object'     => 'lead',
                'glue'       => 'or',
                'field'      => 'firstname',
                'type'       => 'text',
                'operator'   => '=',
                'properties' => ['filter' => 'fn3'],
            ],
            [
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'lastname',
                'type'       => 'text',
                'operator'   => '=',
                'properties' => ['filter' => 'ln3'],
            ],
            [
                'glue'       => 'and',
                'field'      => 'leadlist',
                'object'     => 'lead',
                'type'       => 'leadlist',
                'operator'   => 'in',
                'properties' => ['filter' => [$segmentAId]],
            ],
        ];

        $segment->setName('Segment B')
            ->setFilters($filters)
            ->setAlias('segment-b');
        $segmentRepo->saveEntity($segment);

        return $segment;
    }
}
