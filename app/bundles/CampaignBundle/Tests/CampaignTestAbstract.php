<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;

class CampaignTestAbstract extends \PHPUnit_Framework_TestCase
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

        $coreParametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
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

        $leadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $campaignModel = new CampaignModel($coreParametersHelper, $leadModel, $leadListModel, $formModel);

        $leadModel->setEntityManager($entityManager);
        $leadListModel->setEntityManager($entityManager);
        $formModel->setEntityManager($entityManager);
        $campaignModel->setEntityManager($entityManager);
        $campaignModel->setSecurity($security);
        $campaignModel->setUserHelper($userHelper);

        return $campaignModel;
    }
}
