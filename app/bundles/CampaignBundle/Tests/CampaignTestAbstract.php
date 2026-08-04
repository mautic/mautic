<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Membership\MembershipBuilder;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class CampaignTestAbstract extends TestCase
{
    protected static int $mockId       = 232;

    protected static string $mockName  = 'Mock name';

    protected static string $mockAlias = 'Mock alias';

    /**
     * @var EntityManager&MockObject
     */
    protected ?MockObject $entityManager = null;

    protected function initCampaignModel(): CampaignModel
    {
        $entityManager       = $this->createMock(EntityManager::class);
        $this->entityManager = $entityManager;

        $security = $this->createMock(CorePermissions::class);

        $security
            ->method('isGranted')
            ->willReturn(true);

        $formRepository = $this->createMock(FormRepository::class);

        $formRepository
            ->method('getFormList')
            ->willReturn([['id' => self::$mockId, 'name' => self::$mockName]]);

        $leadListModel = $this->getMockBuilder(ListModel::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs([6 => $entityManager])
            ->getMock();

        $leadListModel
            ->method('getUserLists')
            ->willReturn([['id' => self::$mockId, 'name' => self::$mockName, 'alias' => self::$mockAlias]]);

        $formModel = $this->getMockBuilder(FormModel::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs([12 => $entityManager])
            ->getMock();

        return new CampaignModel(
            $leadListModel,
            $formModel,
            $this->createStub(EventCollector::class),
            $this->createStub(MembershipBuilder::class),
            $this->createStub(ContactTracker::class),
            $this->createStub(GeneratedColumnsProviderInterface::class),
            $entityManager,
            $security,
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(Translator::class),
            $this->createStub(UserHelper::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(CoreParametersHelper::class),
            $this->createStub(CampaignRepository::class), // $campaignRepository
            $this->createStub(EventRepository::class), // $eventRepository
            $this->createStub(LeadRepository::class), // $leadRepository
            $this->createStub(LeadEventLogRepository::class), // $leadEventLogRepository
            $this->createStub(StatRepository::class), // $statRepository
            $formRepository, // $formRepository
        );
    }
}
