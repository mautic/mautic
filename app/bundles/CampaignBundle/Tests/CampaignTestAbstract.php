<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Membership\MembershipBuilder;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\TestCase;

class CampaignTestAbstract extends TestCase
{
    /**
     * @var int
     */
    protected static $mockId   = 232;

    /**
     * @var string
     */
    protected static $mockName = 'Mock name';

    protected function initCampaignModel(): CampaignModel
    {
        $entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $security = $this->getMockBuilder(CorePermissions::class)
            ->disableOriginalConstructor()
            ->getMock();

        $security->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $userHelper = $this->getMockBuilder(UserHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formRepository = $this->getMockBuilder(FormRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formRepository->expects($this->any())
            ->method('getFormList')
            ->will($this->returnValue([['id' => self::$mockId, 'name' => self::$mockName]]));

        $leadListModel = $this->getMockBuilder(ListModel::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs([6 => $entityManager])
            ->getMock();

        $leadListModel->expects($this->any())
            ->method('getUserLists')
            ->will($this->returnValue([['id' => self::$mockId, 'name' => self::$mockName]]));

        $formModel = $this->getMockBuilder(FormModel::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs([12 => $entityManager])
            ->getMock();

        $formModel->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($formRepository));

        $leadModel                = $this->createMock(LeadModel::class);
        $eventCollector           = $this->createMock(EventCollector::class);
        $membershipBuilder        = $this->createMock(MembershipBuilder::class);
        $generatedColumnsProvider = $this->createMock(GeneratedColumnsProviderInterface::class);

        $campaignModel = new CampaignModel($leadModel, $leadListModel, $formModel, $eventCollector, $membershipBuilder, $generatedColumnsProvider);

        return $campaignModel;
    }
}
