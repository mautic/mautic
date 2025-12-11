<?php

namespace Mautic\CampaignBundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Membership\MembershipBuilder;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CampaignTestAbstract extends TestCase
{
    protected static int $mockId      = 232;
    protected static string $mockName = 'Mock name';

    protected function initCampaignModel(): CampaignModel
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

        return new CampaignModel(
            $leadListModel,
            $formModel,
            $this->createMock(EventCollector::class),
            $this->createMock(MembershipBuilder::class),
            $this->createMock(ContactTracker::class),
            $this->createMock(GeneratedColumnsProviderInterface::class),
            $entityManager,
            $security,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(Translator::class),
            $userHelper,
            $this->createMock(LoggerInterface::class),
            $this->createMock(CoreParametersHelper::class),
        );
    }
}
