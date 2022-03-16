<?php

namespace Mautic\CampaignBundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Membership\MembershipBuilder;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Tracker\ContactTracker;

class CampaignTestAbstract extends \PHPUnit\Framework\TestCase
{
    protected static $mockId   = 232;
    protected static $mockName = 'Mock name';

    /**
     * @return CampaignModel
     */
    protected function initCampaignModel()
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
            ->getMock();

        $leadListModel->expects($this->any())
            ->method('getUserLists')
            ->will($this->returnValue([['id' => self::$mockId, 'name' => self::$mockName]]));

        $formModel = $this->getMockBuilder(FormModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formModel->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($formRepository));

        $eventCollector    = $this->createMock(EventCollector::class);
        $membershipBuilder = $this->createMock(MembershipBuilder::class);

        $contactTracker = $this->createMock(ContactTracker::class);

        $campaignModel = new CampaignModel($leadListModel, $formModel, $eventCollector, $membershipBuilder, $contactTracker);

        $leadListModel->setEntityManager($entityManager);
        $formModel->setEntityManager($entityManager);
        $campaignModel->setEntityManager($entityManager);
        $campaignModel->setSecurity($security);
        $campaignModel->setUserHelper($userHelper);

        return $campaignModel;
    }
}
