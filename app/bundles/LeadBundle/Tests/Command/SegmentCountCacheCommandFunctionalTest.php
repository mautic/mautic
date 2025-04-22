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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SegmentCountCacheCommandFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @throws Exception
     */
    public function testSegmentCountCacheCommand(): void
    {
        $contacts  = $this->saveContacts();
        $segment   = $this->saveSegment();
        $segmentId = $segment->getId();

        // Run segments update command.
        $this->runCommand('mautic:segments:update', ['-i' => $segmentId]);

        // Run segment count cache command.
        $this->runCommand(SegmentCountCacheCommand::COMMAND_NAME);

        // Check segment cached contact count.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $content = $crawler->filter('a.col-count')->filter('a[data-id="'.$segmentId.'"]')->html();
        self::assertSame('View 5 Contacts', trim($content));

        // Delete 1 contact.
        $contact = $contacts[0];
        $this->client->request(Request::METHOD_POST, '/s/contacts/delete/'.$contact->getId());
        $clientResponse = $this->client->getResponse();
        self::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());

        // Run segment count cache command again.
        $this->runCommand(SegmentCountCacheCommand::COMMAND_NAME);

        // Check segment cached contact count.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $content = $crawler->filter('a.col-count')->filter('a[data-id="'.$segmentId.'"]')->html();
        self::assertSame('View 4 Contacts', trim($content));
    }

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
        /** @var LeadListRepository $contactRepo */
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
            ->setFilters($filters)
            ->setAlias('segment-a');
        $segmentRepo->saveEntity($segment);

        return $segment;
    }
}