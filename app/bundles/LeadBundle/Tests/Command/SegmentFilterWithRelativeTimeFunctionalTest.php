<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Entity\ListLead;

final class SegmentFilterWithRelativeTimeFunctionalTest extends MauticMysqlTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('getRelativeHours')]
    public function testSegmentFilterWithRelativeTime(int $hours): void
    {
        $this->saveContacts();
        $segment = $this->saveSegment($hours);

        $this->testSymfonyCommand('mautic:segments:update', ['-i' => $segment->getId()]);
        self::assertCount($hours, $this->em->getRepository(ListLead::class)->findBy(['list' => $segment->getId()]));
    }

    /**
     * @return Lead[]
     */
    private function saveContacts(): array
    {
        // Add 10 contacts
        /** @var LeadRepository $contactRepo */
        $contactRepo = $this->em->getRepository(Lead::class);
        /** @var Lead[] $contacts */
        $contacts    = [];

        for ($i = 1; $i <= 10; ++$i) {
            $contact = new Lead();
            $contact->setFirstname('fn'.$i);
            $contact->setLastname('ln'.$i);
            $contact->setLastActive(new \DateTime(sprintf('-%s hours +15 seconds', $i)));
            $contacts[] = $contact;
        }

        $contactRepo->saveEntities($contacts);

        return $contacts;
    }

    private function saveSegment(int $hours): LeadList
    {
        /** @var LeadListRepository $segmentRepo */
        $segmentRepo = $this->em->getRepository(LeadList::class);
        $segment     = new LeadList();
        $filters     = [
            [
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'last_active',
                'type'       => 'datetime',
                'operator'   => 'gte',
                'properties' => ['filter' => sprintf('-%s hours', $hours)],
            ],
        ];

        $segment->setName('Segment')
            ->setPublicName('Segment')
            ->setFilters($filters)
            ->setAlias('segment');
        $segmentRepo->saveEntity($segment);

        return $segment;
    }

    /**
     * @return iterable<int[]>
     */
    public static function getRelativeHours(): iterable
    {
        yield [1];
        yield [3];
        yield [5];
    }
}
