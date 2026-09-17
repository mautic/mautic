<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\UserBundle\Entity\User;

final class ListModelFunctionalTest extends MauticMysqlTestCase
{
    public function testPublicSegmentsInContactPreferences(): void
    {
        $user           = $this->em->getRepository(User::class)->findBy([], [], 1)[0];
        $firstLeadList  = $this->createLeadList($user, 'First', true);
        $secondLeadList = $this->createLeadList($user, 'Second', false);
        $thirdLeadList  = $this->createLeadList($user, 'Third', true);
        $this->em->flush();

        /** @var LeadListRepository $repo */
        $repo  = $this->em->getRepository(LeadList::class);
        $lists = $repo->getGlobalLists();

        $this->assertCount(2, $lists);
        $this->assertArrayHasKey($firstLeadList->getId(), $lists);
        $this->assertArrayHasKey($thirdLeadList->getId(), $lists);
        $this->assertArrayNotHasKey($secondLeadList->getId(), $lists, 'Non-global lists should not be returned by the `getGlobalLists()` method.');
    }

    public function testSegmentLineChartData(): void
    {
        /** @var ListModel $segmentModel */
        $segmentModel = self::getContainer()->get(ListModel::class);

        /** @var LeadRepository $contactRepository */
        $contactRepository = $this->em->getRepository(Lead::class);

        $segment = new LeadList();
        $segment->setName('Segment A');

        $segmentModel->saveEntity($segment);

        $contacts = [new Lead(), new Lead(), new Lead(), new Lead()];

        $contactRepository->saveEntities($contacts);

        $segmentModel->addLead($contacts[0], $segment); // Emulating adding by a filter.
        $segmentModel->addLead($contacts[1], $segment); // Emulating adding by a filter.
        $segmentModel->addLead($contacts[2], $segment, true); // Manually added.
        $segmentModel->addLead($contacts[3], $segment, true); // Manually added.

        $data = $segmentModel->getSegmentContactsLineChartData(
            'd',
            new \DateTime('1 month ago', new \DateTimeZone('UTC')),
            new \DateTime('now', new \DateTimeZone('UTC')),
            null,
            ['leadlist_id' => ['value' => $segment->getId(), 'list_column_name' => 't.lead_id']]
        );

        $this->assertSame('added', strtolower($data['datasets'][0]['label']));
        $this->assertSame('removed', strtolower($data['datasets'][1]['label']));
        $this->assertSame('total', strtolower($data['datasets'][2]['label']));

        $this->assertSame(4, (int) end($data['datasets'][0]['data'])); // Added for today.
        $this->assertSame(0, (int) end($data['datasets'][1]['data'])); // Removed for today.
        $this->assertSame(4, (int) end($data['datasets'][2]['data'])); // Total for today.

        // To make this interesting, lets' remove some contacts to see what happens.
        $segmentModel->removeLead($contacts[1], $segment); // Emulating removing by a filter.
        $segmentModel->removeLead($contacts[2], $segment, true); // Manually removed.

        $data = $segmentModel->getSegmentContactsLineChartData(
            'd',
            new \DateTime('1 month ago', new \DateTimeZone('UTC')),
            new \DateTime('now', new \DateTimeZone('UTC')),
            null,
            ['leadlist_id' => ['value' => $segment->getId(), 'list_column_name' => 't.lead_id']]
        );

        $this->assertSame(4, (int) end($data['datasets'][0]['data'])); // Added for today.
        $this->assertSame(2, (int) end($data['datasets'][1]['data'])); // Removed for today.
        $this->assertSame(2, (int) end($data['datasets'][2]['data'])); // Total for today.
    }

    public function testSegmentLineChartDataWithoutFetchDataFromLeadListTable(): void
    {
        /** @var ListModel $segmentModel */
        $segmentModel = self::getContainer()->get(ListModel::class);

        /** @var LeadRepository $contactRepository */
        $contactRepository = $this->em->getRepository(Lead::class);

        $segment = new LeadList();
        $segment->setName('Segment A');

        $segmentModel->saveEntity($segment);

        $contacts = [new Lead()];

        $contactRepository->saveEntities($contacts);

        // Adding record in mautic_lead_lists_leads before 11 second from mautic_lead_event_log
        // using old code there should be double records means 2 but now it will show only 1 contact
        $segmentModel->addLead($contacts[0], $segment, true, false, 1, new \DateTime('-11 seconds', new \DateTimeZone('UTC'))); // Emulating adding by a filter.

        $data = $segmentModel->getSegmentContactsLineChartData(
            'd',
            new \DateTime('-2 days', new \DateTimeZone('UTC')),
            new \DateTime('now', new \DateTimeZone('UTC')),
            null,
            ['leadlist_id' => ['value' => $segment->getId(), 'list_column_name' => 't.lead_id']]
        );

        // using old code there should be only 1 label added but now there should be all 3 labels
        $this->assertSame('added', strtolower($data['datasets'][0]['label']));
        $this->assertSame('removed', strtolower($data['datasets'][1]['label']));
        $this->assertSame('total', strtolower($data['datasets'][2]['label']));

        $this->assertSame(1, (int) end($data['datasets'][0]['data'])); // Added for today.
        $this->assertSame(0, (int) end($data['datasets'][1]['data'])); // Removed for today.
        $this->assertSame(1, (int) end($data['datasets'][2]['data'])); // Total for today.

        // To make this interesting, lets' remove some contacts to see what happens.
        $segmentModel->removeLead($contacts[0], $segment, true);

        $data = $segmentModel->getSegmentContactsLineChartData(
            'd',
            new \DateTime('-2 days', new \DateTimeZone('UTC')),
            new \DateTime('now', new \DateTimeZone('UTC')),
            null,
            ['leadlist_id' => ['value' => $segment->getId(), 'list_column_name' => 't.lead_id']]
        );

        $this->assertSame(1, (int) end($data['datasets'][0]['data'])); // Added for today.
        $this->assertSame(1, (int) end($data['datasets'][1]['data'])); // Removed for today.
        $this->assertSame(0, (int) end($data['datasets'][2]['data'])); // Total for today.
    }

    /**
     * Manually removing a lead that was manually added must keep the lead_lists_leads row
     * (with manually_removed = 1) instead of deleting it outright. Otherwise a later segment
     * rebuild could silently re-add the lead if/when they start matching the segment's filters,
     * silently overriding the user's explicit removal.
     */
    public function testManuallyRemovingManuallyAddedLeadPersistsMembershipRecord(): void
    {
        /** @var ListModel $segmentModel */
        $segmentModel = self::getContainer()->get(ListModel::class);

        /** @var LeadRepository $contactRepository */
        $contactRepository = $this->em->getRepository(Lead::class);

        $segment = new LeadList();
        $segment->setName('Segment Manual Removal');

        $segmentModel->saveEntity($segment);

        $contact = new Lead();
        $contactRepository->saveEntities([$contact]);

        // Manually add the contact to the segment (i.e. not because it matched a filter).
        $segmentModel->addLead($contact, $segment, true);

        $this->assertSame(
            ['manually_added' => '1', 'manually_removed' => '0'],
            $this->fetchListLeadRow($contact->getId(), $segment->getId()),
            'Sanity check: the contact should be recorded as a current, manually-added member.'
        );

        // Manually remove the contact from the segment.
        $segmentModel->removeLead($contact, $segment, true);

        $this->assertSame(
            ['manually_added' => '1', 'manually_removed' => '1'],
            $this->fetchListLeadRow($contact->getId(), $segment->getId()),
            'A manually removed lead\'s membership row must be kept and flagged manually_removed = 1, not deleted, so a future segment rebuild cannot silently re-add the lead.'
        );
    }

    /**
     * @return array<string, string>|false
     */
    private function fetchListLeadRow(int $leadId, int $segmentId)
    {
        return $this->connection->fetchAssociative(
            'SELECT manually_added, manually_removed FROM '.MAUTIC_TABLE_PREFIX.'lead_lists_leads WHERE lead_id = :leadId AND leadlist_id = :segmentId',
            ['leadId' => $leadId, 'segmentId' => $segmentId]
        );
    }

    private function createLeadList(User $user, string $name, bool $isGlobal): LeadList
    {
        $leadList = new LeadList();
        $leadList->setName($name);
        $leadList->setPublicName('Public'.$name);
        $leadList->setAlias(mb_strtolower($name));
        $leadList->setCreatedBy($user);
        $leadList->setIsGlobal($isGlobal);
        $this->em->persist($leadList);

        return $leadList;
    }
}
