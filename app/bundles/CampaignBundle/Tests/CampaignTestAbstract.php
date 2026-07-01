<?php

declare(strict_types=1);

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

    /** @var EntityManager&MockObject */
    protected ?MockObject $entityManager = null;

    protected function initCampaignModel(): CampaignModel
    {
        $entityManager       = $this->createMock(EntityManager::class);
        $this->entityManager = $entityManager;

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
            ->willReturn([['id' => self::$mockId, 'name' => self::$mockName, 'alias' => self::$mockAlias]]);

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
            $this->createStub(EventCollector::class),
            $this->createStub(MembershipBuilder::class),
            $this->createStub(ContactTracker::class),
            $this->createStub(GeneratedColumnsProviderInterface::class),
            $entityManager,
            $security,
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(Translator::class),
            $userHelper,
            $this->createStub(LoggerInterface::class),
            $this->createStub(CoreParametersHelper::class),
        );
    }
}
