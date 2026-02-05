<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Command\DeleteLeadListsCommand;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\ListModel;

class DeleteLeadListsCommandFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['delete_segment_in_background'] = true;
        parent::setUp();

        $this->saveContacts();
    }

    public function testSegmentDeleteCommand(): void
    {
        $segment   = $this->saveSegment('Segment A', 'segment-a');
        $segmentId = $segment->getId();

        // Run segments update command.
        $this->testSymfonyCommand('mautic:segments:update', ['-i' => $segmentId]);

        /** @var ListModel $listModel */
        $listModel = $this->getContainer()->get('mautic.lead.model.list');
        $leadCount = $listModel->getListLeadRepository()->getContactsCountBySegment($segmentId);
        self::assertSame(5, $leadCount);

        $listModel->deleteEntity($segment);
        $this->em->flush();
        $this->em->refresh($segment);

        self::assertNull($listModel->getEntity($segmentId));

        $deletedEntity = $listModel->getSoftDeletedEntity($segmentId);
        self::assertSame($segmentId, $deletedEntity->getId());

        $this->testSymfonyCommand(DeleteLeadListsCommand::COMMAND_NAME, ['list-id' => $segmentId]);

        $leadCount = $listModel->getListLeadRepository()->getContactsCountBySegment($segmentId);
        self::assertSame(0, $leadCount);

        $deletedEntity = $listModel->getSoftDeletedEntity($segmentId);
        self::assertNull($deletedEntity);
    }

    public function testSegmentDeleteCommandWithoutArgs(): void
    {
        $segmentB   = $this->saveSegment('Segment B', 'segment-b');
        $segmentC   = $this->saveSegment('Segment C', 'segment-c');

        $segmentBId = $segmentB->getId();
        $segmentCId = $segmentC->getId();

        // Run segments update command.
        $this->testSymfonyCommand('mautic:segments:update');

        /** @var ListModel $listModel */
        $listModel = $this->getContainer()->get('mautic.lead.model.list');
        $leadCount = $listModel->getListLeadRepository()->getContactsCountBySegment($segmentBId);
        self::assertSame(5, $leadCount);

        // Test segments delete command without ids
        $listModel->deleteEntities([$segmentBId, $segmentCId]);
        $this->em->flush();
        $this->em->refresh($segmentB);
        $this->em->refresh($segmentC);

        self::assertNull($listModel->getEntity($segmentBId));

        $deletedEntity = $listModel->getSoftDeletedEntity($segmentBId);
        self::assertSame($segmentBId, $deletedEntity->getId());
        // command without  args --list-id
        $this->testSymfonyCommand(DeleteLeadListsCommand::COMMAND_NAME);

        $leadCount = $listModel->getListLeadRepository()->getContactsCountBySegment($segmentBId);
        self::assertSame(0, $leadCount);

        $deletedEntity = $listModel->getSoftDeletedEntity($segmentBId);
        self::assertNull($deletedEntity);
    }

    /**
     * @return array<mixed>
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

    private function saveSegment(string $name, string $alias): LeadList
    {
        // Add 1 segment
        $segmentRepo = $this->em->getRepository(LeadList::class);
        \assert($segmentRepo instanceof LeadListRepository);

        $segment = new LeadList();
        $filters = [
            [
                'glue'       => 'and',
                'field'      => 'firstname',
                'object'     => 'lead',
                'type'       => 'text',
                'operator'   => 'like',
                'properties' => ['filter' => 'Contact'],
            ],
        ];
        $segment->setName($name)
            ->setFilters($filters)
            ->setPublicName($name)
            ->setAlias($alias);
        $segmentRepo->saveEntity($segment);

        return $segment;
    }
}
