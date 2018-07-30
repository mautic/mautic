<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Tests\Model;

use Mautic\CampaignBundle\Entity\Lead;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Model\PointModel;
use Symfony\Component\HttpFoundation\Session\Session;

class PointModelTest extends \MauticMysqlTestCase
{
    public function testTriggerActionNotRepeat()
    {
        $sessionMock = $this->createMock(Session::class);
        $sessionMock->expects($this->once())->method('get')->willReturn('');
        $sessionMock->expects($this->once())->method('set')->willReturn('');

        $ipLookupHelperMock = $this->createMock(IpLookupHelper::class);
        $ipLookupHelperMock->expects($this->once())->method('getIpAddress')->willReturn('');

        $leadModelMock = $this->createMock(LeadModel::class);
        $leadModelMock->expects($this->once())->method('getCurrentLead')->willReturn(new Lead());

        $pointModel =    new PointModel($sessionMock, $ipLookupHelperMock, $leadModelMock);
        $pointModel->triggerAction('url.hit');
    }
}
