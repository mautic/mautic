<?php

namespace Mautic\CampaignBundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Membership\MembershipBuilder;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CampaignTestAbstract extends \PHPUnit\Framework\TestCase
{
    protected static $mockId   = 232;

    protected static $mockName = 'Mock name';

    /**
     * @return CampaignModel
     */
    protected function initCampaignModel()
    {
        $entityManager = $this->createMock(EntityManager::class);

        $security = $this->createMock(CorePermissions::class);

        $security->expects($this->any())
            ->method('isGranted')
            ->willReturn(true);

        $userHelper = $this->createMock(UserHelper::class);

        $formRepository = $this->createMock(FormRepository::class);

        $formRepository->expects($this->any())
            ->method('getFormList')
            ->willReturn([['id' => self::$mockId, 'name' => self::$mockName]]);

        $leadListModel = $this->getMockBuilder(ListModel::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs([6 => $entityManager])
            ->getMock();

        $leadListModel->expects($this->any())
            ->method('getUserLists')
            ->willReturn([['id' => self::$mockId, 'name' => self::$mockName]]);

        $formModel = $this->getMockBuilder(FormModel::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs([12 => $entityManager])
            ->getMock();

        $formModel->expects($this->any())
            ->method('getRepository')
            ->willReturn($formRepository);

        $eventCollector    = $this->createMock(EventCollector::class);
        $membershipBuilder = $this->createMock(MembershipBuilder::class);

        $contactTracker = $this->createMock(ContactTracker::class);

        $campaignModel = new CampaignModel(
            $leadListModel,
            $formModel,
            $eventCollector,
            $membershipBuilder,
            $contactTracker,
            $entityManager,
            $security,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(Translator::class),
            $userHelper,
            $this->createMock(LoggerInterface::class),
            $this->createMock(CoreParametersHelper::class)
        );

        return $campaignModel;
    }
}
