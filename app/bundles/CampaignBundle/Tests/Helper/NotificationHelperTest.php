<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Helper;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Routing\Router;

class NotificationHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|UserModel
     */
    private $userModel;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|NotificationModel
     */
    private $notificationModel;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Router
     */
    private $router;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Translator
     */
    private $translator;

    protected function setUp(): void
    {
        $this->userModel = $this->getMockBuilder(UserModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->notificationModel = $this->getMockBuilder(NotificationModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testContactOwnerIsNotified()
    {
        $event    = new Event();
        $campaign = new Campaign();
        $event->setCampaign($campaign);

        $user = $this->getMockBuilder(User::class)
            ->getMock();
        $user->method('getId')
            ->willReturn('1');
        $lead = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead->expects($this->once())
            ->method('getOwner')
            ->willReturn($user);

        $this->userModel->expects($this->never())
            ->method('getEntity');

        $this->userModel->expects($this->never())
            ->method('getSystemAdministrator');

        $this->notificationModel->expects($this->once())
            ->method('addNotification')
            ->with(
                ' / ',
                'error',
                false,
                $this->anything(),
                null,
                null,
                $user
            );

        $this->getNotificationHelper()->notifyOfFailure($lead, $event);
    }

    public function testCampaignCreatorIsNotified()
    {
        $event    = new Event();
        $campaign = new Campaign();
        $event->setCampaign($campaign);
        $campaign->setCreatedBy(1);

        $user = $this->getMockBuilder(User::class)
            ->getMock();
        $user->method('getId')
            ->willReturn('1');
        $lead = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead->expects($this->once())
            ->method('getOwner')
            ->willReturn(null);

        $this->userModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($user);

        $this->userModel->expects($this->never())
            ->method('getSystemAdministrator');

        $this->notificationModel->expects($this->once())
            ->method('addNotification')
            ->with(
                ' / ',
                'error',
                false,
                $this->anything(),
                null,
                null,
                $user
            );

        $this->getNotificationHelper()->notifyOfFailure($lead, $event);
    }

    public function testSystemAdminIsNotified()
    {
        $event    = new Event();
        $campaign = new Campaign();
        $event->setCampaign($campaign);
        $campaign->setCreatedBy(2);

        $user = $this->getMockBuilder(User::class)
            ->getMock();
        $user->method('getId')
            ->willReturn('1');
        $lead = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead->expects($this->once())
            ->method('getOwner')
            ->willReturn(null);

        $this->userModel->expects($this->once())
            ->method('getEntity')
            ->willReturn(null);

        $this->userModel->expects($this->once())
            ->method('getSystemAdministrator')
            ->willReturn($user);

        $this->notificationModel->expects($this->once())
            ->method('addNotification')
            ->with(
                ' / ',
                'error',
                false,
                $this->anything(),
                null,
                null,
                $user
            );

        $this->getNotificationHelper()->notifyOfFailure($lead, $event);
    }

    public function testNotificationIgnoredIfUserNotFound()
    {
        $event    = new Event();
        $campaign = new Campaign();
        $event->setCampaign($campaign);
        $campaign->setCreatedBy(2);

        $lead = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead->expects($this->once())
            ->method('getOwner')
            ->willReturn(null);

        $this->userModel->expects($this->once())
            ->method('getEntity')
            ->willReturn(null);

        $this->userModel->expects($this->once())
            ->method('getSystemAdministrator')
            ->willReturn(null);

        $this->notificationModel->expects($this->never())
            ->method('addNotification');

        $this->getNotificationHelper()->notifyOfFailure($lead, $event);
    }

    /**
     * @return NotificationHelper
     */
    private function getNotificationHelper()
    {
        return new NotificationHelper(
            $this->userModel,
            $this->notificationModel,
            $this->translator,
            $this->router
        );
    }
}
