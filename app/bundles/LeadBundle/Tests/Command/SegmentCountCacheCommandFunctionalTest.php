<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Command\SegmentCountCacheCommand;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SegmentCountCacheCommandFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @throws \Exception
     */
    public function testSegmentCountCacheCommand(): void
    {
        $contacts  = $this->saveContacts();
        $segment   = $this->saveSegment();
        $segmentId = $segment->getId();

        // Run segments update command.
        $this->testSymfonyCommand('mautic:segments:update', ['-i' => $segmentId]);

        // Run segment count cache command.
        $this->testSymfonyCommand(SegmentCountCacheCommand::COMMAND_NAME);

        // Check segment cached contact count using the SegmentCountCacheHelper directly
        $segmentCountCacheHelper = static::getContainer()->get('mautic.helper.segment.count.cache');
        $count                   = $segmentCountCacheHelper->getSegmentContactCount($segmentId);
        self::assertEquals(5, $count, "Expected segment $segmentId to have 5 contacts");

        // Delete 1 contact.
        $contact = $contacts[0];
        $this->client->request(Request::METHOD_POST, '/s/contacts/delete/'.$contact->getId());
        $clientResponse = $this->client->getResponse();
        self::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());

        // Run segment count cache command again.
        $this->testSymfonyCommand(SegmentCountCacheCommand::COMMAND_NAME);

        // Check segment cached contact count using the SegmentCountCacheHelper directly
        $segmentCountCacheHelper = static::getContainer()->get('mautic.helper.segment.count.cache');
        $count                   = $segmentCountCacheHelper->getSegmentContactCount($segmentId);
        self::assertEquals(4, $count, "Expected segment $segmentId to have 4 contacts");
    }

    /**
     * @return array<int, Lead>
     */
    private function saveContacts(): array
    {
        // Add 5 contacts
        /** @var LeadRepository $contactRepo */
        $contactRepo = $this->em->getRepository(Lead::class);
        $contacts    = [];

        for ($i = 1; $i <= 5; ++$i) {
            $contact = new Lead();
            $contact->setFirstname('Contact '.$i);
            $contacts[] = $contact;
        }

        $contactRepo->saveEntities($contacts);

        return $contacts;
    }

    private function saveSegment(): LeadList
    {
        // Add 1 segment
        /** @var LeadListRepository $segmentRepo */
        $segmentRepo = $this->em->getRepository(LeadList::class);
        $segment     = new LeadList();
        $filters     = [
            [
                'glue'       => 'and',
                'field'      => 'firstname',
                'object'     => 'lead',
                'type'       => 'text',
                'operator'   => 'like',
                'properties' => ['filter' => 'Contact'],
            ],
        ];
        $segment->setName('Segment A')
            ->setPublicName('Segment A')
            ->setFilters($filters)
            ->setAlias('segment-a');
        $segmentRepo->saveEntity($segment);

        return $segment;
    }
}
