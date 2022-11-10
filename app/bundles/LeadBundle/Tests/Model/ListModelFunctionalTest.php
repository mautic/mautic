<?php

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;

class ListModelFunctionalTest extends MauticMysqlTestCase
{
    public function testPublicSegmentsInContactPreferences()
    {
        $user           = $this->em->getRepository(User::class)->findBy([], [], 1)[0];
        $firstLeadList  = $this->createLeadList($user, 'First', true);
        $secondLeadList = $this->createLeadList($user, 'Second', false);
        $thirdLeadList  = $this->createLeadList($user, 'Third', true);
        $this->em->flush();

        /** @var LeadListRepository $repo */
        $repo  = $this->em->getRepository(LeadList::class);
        $lists = $repo->getGlobalLists();

        Assert::assertCount(2, $lists);
        Assert::assertArrayHasKey($firstLeadList->getId(), $lists);
        Assert::assertArrayHasKey($thirdLeadList->getId(), $lists);
        Assert::assertArrayNotHasKey(
            $secondLeadList->getId(),
            $lists,
            'Non-global lists should not be returned by the `getGlobalLists()` method.'
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

    public function testSegmentLineChartData(): void
    {
        /** @var ListModel $segmentModel */
        $segmentModel = self::$container->get('mautic.lead.model.list');

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

        Assert::assertSame(4, end($data['datasets'][0]['data'])); // Added for today.
        Assert::assertSame(0, end($data['datasets'][1]['data'])); // Removed for today.
        Assert::assertSame(4, end($data['datasets'][2]['data'])); // Total for today.

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

        Assert::assertSame(4, end($data['datasets'][0]['data'])); // Added for today.
        Assert::assertSame('2', end($data['datasets'][1]['data'])); // Removed for today.
        Assert::assertSame(2, end($data['datasets'][2]['data'])); // Total for today.
    }
}
