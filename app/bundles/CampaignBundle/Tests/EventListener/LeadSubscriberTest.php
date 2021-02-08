<?php

namespace Mautic\CampaignBundle\Tests\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\EventListener\LeadSubscriber;
use Mautic\CampaignBundle\Membership\MembershipManager;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Event\ListChangeEvent;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\Translator;

class LeadSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CampaignModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $campaignModelMock;

    private $segmentId = 123;

    private $leadId = 111;

    public function setUp(): void
    {
        parent::setUp();

        $campaignList = [
            [
                'id'    => '1',
                'name'  => 'first test campaign',
                'lists' => [
                    $this->segmentId => [
                        'id' => $this->leadId,
                    ],
                ],
            ],
            [
                'id'    => '2',
                'name'  => 'second test campaign',
                'lists' => [
                    $this->segmentId => [
                        'id' => $this->leadId,
                    ],
                ],
            ],
        ];

        $campaignRepositoryMock = $this->createMock(CampaignRepository::class);
        $campaignRepositoryMock->method('getPublishedCampaignsByLeadLists')->willReturn($campaignList);

        $this->campaignModelMock = $this->createMock(CampaignModel::class);
        $this->campaignModelMock->method('getRepository')->willReturn($campaignRepositoryMock);
    }

    public function testOnLeadListBatchChangeWithAddedContacts()
    {
        $addedLead = ['id' => $this->leadId];
        $leadList  = $this->createMock(LeadList::class);
        $leadList->method('getId')->willReturn($this->segmentId);
        $event = new ListChangeEvent([$addedLead], $leadList, true);

        $membershipManagerMock = $this->createMock(MembershipManager::class);
        /*
         * This asserts that the right types are being set in the function
         * and that contacts are actually being added (types would be null otherwise)
         * and that no contacts are being removed
         */
        $membershipManagerMock->expects($this->exactly(2))
            ->method('addContacts')
            ->with(
                $this->containsOnlyInstancesOf(Lead::class),
                $this->isInstanceOf(Campaign::class),
                $this->isFalse()
            );

        $membershipManagerMock->expects($this->never())->method('removeContacts');

        $leadSubscriber = new LeadSubscriber(
            $membershipManagerMock,
            $this->createMock(EventCollector::class),
            $this->campaignModelMock,
            $this->createMock(LeadModel::class),
            $this->createMock(Translator::class),
            $this->makeBatchEntityManagerMock(),
            $this->createMock(Router::class)
        );

        $leadSubscriber->onLeadListBatchChange($event);
        unset($leadSubscriber);
    }

    public function testOnLeadListBatchChangeWithRemovedContacts()
    {
        $removedLead = ['id' => $this->leadId];
        $leadList    = $this->createMock(LeadList::class);
        $leadList->method('getId')->willReturn($this->segmentId);
        $event = new ListChangeEvent([$removedLead], $leadList, false);

        $membershipManagerMock = $this->createMock(MembershipManager::class);
        $membershipManagerMock->expects($this->exactly(2))
            ->method('removeContacts')
            ->with(
                $this->containsOnlyInstancesOf(Lead::class),
                $this->isInstanceOf(Campaign::class),
                $this->isTrue()
            );

        $membershipManagerMock->expects($this->never())->method('addContacts');

        $leadSubscriber = new LeadSubscriber(
            $membershipManagerMock,
            $this->createMock(EventCollector::class),
            $this->campaignModelMock,
            $this->createMock(LeadModel::class),
            $this->createMock(Translator::class),
            $this->makeBatchEntityManagerMock(),
            $this->createMock(Router::class)
        );

        $leadSubscriber->onLeadListBatchChange($event);
        unset($leadSubscriber);
    }

    public function testOnLeadListChangeWithAddedContact()
    {
        $removedLead = $this->createMock(Lead::class);
        $leadList    = $this->createMock(LeadList::class);
        $leadList->method('getId')->willReturn($this->segmentId);
        $event = new ListChangeEvent($removedLead, $leadList, true);

        $leadModel = $this->createMock(LeadModel::class);
        $leadModel->expects($this->once())
            ->method('getLists')
            ->with($removedLead, $this->isTrue())
            ->willReturn(
                [
                    $this->segmentId => [],
                ]
            );

        $membershipManagerMock = $this->createMock(MembershipManager::class);
        $membershipManagerMock->expects($this->exactly(2))
            ->method('addContact')
            ->with(
                $this->isInstanceOf(Lead::class),
                $this->isInstanceOf(Campaign::class),
                $this->isFalse()
            );

        $membershipManagerMock->expects($this->never())->method('removeContact');

        $leadSubscriber = new LeadSubscriber(
            $membershipManagerMock,
            $this->createMock(EventCollector::class),
            $this->campaignModelMock,
            $leadModel,
            $this->createMock(Translator::class),
            $this->makeSingleEntityManagerMock(),
            $this->createMock(Router::class)
        );

        $leadSubscriber->onLeadListChange($event);
        unset($leadSubscriber);
    }

    public function testOnLeadListChangeWithRemovedContact()
    {
        $removedLead = $this->createMock(Lead::class);
        $leadList    = $this->createMock(LeadList::class);
        $leadList->method('getId')->willReturn($this->segmentId);
        $event = new ListChangeEvent($removedLead, $leadList, false);

        $leadModel = $this->createMock(LeadModel::class);
        $leadModel->expects($this->once())
            ->method('getLists')
            ->with($removedLead, $this->isTrue())
            ->willReturn(
                [
                    $this->segmentId => [],
                ]
            );

        $membershipManagerMock = $this->createMock(MembershipManager::class);
        $membershipManagerMock->expects($this->exactly(2))
            ->method('removeContact')
            ->with(
                $this->isInstanceOf(Lead::class),
                $this->isInstanceOf(Campaign::class),
                $this->isTrue()
            );

        $membershipManagerMock->expects($this->never())->method('addContact');

        $leadSubscriber = new LeadSubscriber(
            $membershipManagerMock,
            $this->createMock(EventCollector::class),
            $this->campaignModelMock,
            $leadModel,
            $this->createMock(Translator::class),
            $this->makeSingleEntityManagerMock(),
            $this->createMock(Router::class)
        );

        $leadSubscriber->onLeadListChange($event);
        unset($leadSubscriber);
    }

    private function makeBatchEntityManagerMock()
    {
        $leadListRepositoryMock = $this->createMock(LeadListRepository::class);
        $leadListRepositoryMock->method('getLeadLists')->willReturn([$this->leadId => [$this->segmentId]]);

        $contactEventLogRepositoryMock = $this->createMock(LeadEventLogRepository::class);

        $contactRepositoryMock = $this->createMock(LeadRepository::class);

        $entityManagerMock = $this->createMock(EntityManager::class);
        $entityManagerMock->method('getRepository')->withConsecutive([LeadList::class], [LeadEventLog::class], [CampaignLead::class])
            ->willReturnOnConsecutiveCalls($leadListRepositoryMock, $contactEventLogRepositoryMock, $contactRepositoryMock);

        $mockCampaign1 = $this->createMock(Campaign::class);
        $mockCampaign1->method('getId')->willReturn(1);

        $mockCampaign2 = $this->createMock(Campaign::class);
        $mockCampaign2->method('getId')->willReturn(2);

        $entityManagerMock->method('getReference')->withConsecutive([Lead::class, $this->anything()], [Campaign::class, 1], [Campaign::class, 2])
            ->willReturnOnConsecutiveCalls(new Lead(), $mockCampaign1, $mockCampaign2);

        return $entityManagerMock;
    }

    private function makeSingleEntityManagerMock()
    {
        $leadListRepositoryMock = $this->createMock(LeadListRepository::class);
        $leadListRepositoryMock->method('getLeadLists')->willReturn([$this->leadId => [$this->segmentId]]);

        $contactEventLogRepositoryMock = $this->createMock(LeadEventLogRepository::class);

        $contactRepositoryMock = $this->createMock(LeadRepository::class);

        $entityManagerMock = $this->createMock(EntityManager::class);
        $entityManagerMock->method('getRepository')->withConsecutive([LeadList::class], [LeadEventLog::class], [CampaignLead::class])
            ->willReturnOnConsecutiveCalls($leadListRepositoryMock, $contactEventLogRepositoryMock, $contactRepositoryMock);

        $mockCampaign1 = $this->createMock(Campaign::class);
        $mockCampaign1->method('getId')->willReturn(1);

        $mockCampaign2 = $this->createMock(Campaign::class);
        $mockCampaign2->method('getId')->willReturn(2);

        $entityManagerMock->method('getReference')->withConsecutive([Campaign::class, 1], [Campaign::class, 2])
            ->willReturnOnConsecutiveCalls($mockCampaign1, $mockCampaign2);

        return $entityManagerMock;
    }
}
