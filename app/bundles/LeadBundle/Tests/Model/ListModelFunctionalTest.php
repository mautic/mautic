<?php

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\LeadBundle\Model\ListModel;
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
        $segmentModel = $this->container->get('mautic.lead.model.list');

        /** @var LeadRepository $contactRepository */
        $contactRepository = $this->em->getRepository(Lead::class);

        $segment = new LeadList();
        $segment->setName('Segment A');

        $segmentModel->saveEntity($segment);

        $contactA = new Lead();
        $contactB = new Lead();
        $contactC = new Lead();

        $contactRepository->saveEntities([$contactA, $contactB, $contactC]);

        $segmentModel->addLead($contactA, $segment); // Emulating adding by a filter.
        $segmentModel->addLead($contactB, $segment); // Emulating adding by a filter.
        $segmentModel->addLead($contactC, $segment, true); // Manually added.

        $data = $segmentModel->getSegmentContactsLineChartData(
            'd',
            new \DateTime('1 month ago', new \DateTimeZone('UTC')),
            new \DateTime('now', new \DateTimeZone('UTC')),
            null,
            ['leadlist_id' => ['value' => $segment->getId(), 'list_column_name' => 't.lead_id']]
        );
        dump($data);
        Assert::assertSame(3, $data['datasets'][0]['data'][31]); // Total count for today.
    }
}
