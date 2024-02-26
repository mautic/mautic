<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Entity\ListLead;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

final class SegmentFilterOnUpdateCommandFunctionalTest extends MauticMysqlTestCase
{
    public function testSegmentFilterOnUpdateCommand(): void
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);
        $applicationTester = new ApplicationTester($application);

        $this->saveContacts();
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

    /**
     * @return Lead[]
     */
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
        $segment = new LeadList();
        $filters = [
            [
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'address1',
                'type'       => 'text',
                'operator'   => '!empty',
                'properties' => ['filter' => null],
                // The filter key is deprecated but sometimes it contains rubbish values including a string.
                'filter'     => 'somestring',
            ],
            [
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'address1',
                'type'       => 'text',
                'operator'   => '!=',
                'properties' => ['filter' => null],
                // The filter key is deprecated but sometimes it contains rubbish values including an array.
                'filter'     => ['option A', 'option B'],
            ],
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
            ->setPublicName('Segment A')
            ->setFilters($filters)
            ->setAlias('segment-a');
        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }

    private function saveSegmentB(int $segmentAId): LeadList
    {
        $segment = new LeadList();
        $filters = [
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
            ->setPublicName('Segment B')
            ->setFilters($filters)
            ->setAlias('segment-b');
        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }
}
