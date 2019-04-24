<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Test;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Helper\RemovedContactTracker;
use Mautic\CampaignBundle\Membership\MembershipBuilder;
use Mautic\CampaignBundle\Membership\MembershipManager;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;

class CampaignTestCase extends \PHPUnit_Framework_TestCase
{
    protected static $mockId   = 232;
    protected static $mockName = 'Mock name';

    /**
     * @return CampaignModel
     */
    protected function initCampaignModel()
    {
        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $security = $this->getMockBuilder(CorePermissions::class)
            ->disableOriginalConstructor()
            ->setMethods(['isGranted'])
            ->getMock();

        $security->expects($this->any())
            ->method('isGranted')
            ->willReturn(true);

        $userHelper = $this->getMockBuilder(UserHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formRepository = $this->getMockBuilder(FormRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFormList'])
            ->getMock();

        $formRepository->expects($this->any())
            ->method('getFormList')
            ->willReturn([['id' => static::$mockId, 'name' => static::$mockName]]);

        $leadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadListModel = $this->getMockBuilder(ListModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUserLists'])
            ->getMock();

        $leadListModel->expects($this->any())
            ->method('getUserLists')
            ->willReturn([['id' => static::$mockId, 'name' => static::$mockName]]);

        $formModel = $this->getMockBuilder(FormModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();

        $formModel->expects($this->any())
            ->method('getRepository')
            ->willReturn($formRepository);

        $eventCollector = $this->createMock(EventCollector::class);

        $removedContactTracker = $this->createMock(RemovedContactTracker::class);

        $membershipManager = $this->createMock(MembershipManager::class);
        $membershipBuilder = $this->createMock(MembershipBuilder::class);

        $campaignModel = new CampaignModel($leadModel, $leadListModel, $formModel, $eventCollector, $removedContactTracker, $membershipManager, $membershipBuilder);

        $leadModel->setEntityManager($entityManager);
        $leadListModel->setEntityManager($entityManager);
        $formModel->setEntityManager($entityManager);
        $campaignModel->setEntityManager($entityManager);
        $campaignModel->setSecurity($security);
        $campaignModel->setUserHelper($userHelper);

        return $campaignModel;
    }
}
