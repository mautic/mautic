<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Helper;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\Test\ReflectionHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Router;

#[AllowMockObjectsWithoutExpectations]
final class NotificationHelperTest extends TestCase
{
    /**
     * @var MockObject&UserModel
     */
    private MockObject $userModel;

    /**
     * @var MockObject&NotificationModel
     */
    private MockObject $notificationModel;

    /**
     * @var MockObject&Translator
     */
    private MockObject $translator;

    /**
     * @var MockObject&CoreParametersHelper
     */
    private MockObject $coreParametersHelper;

    protected function setUp(): void
    {
        $this->userModel = $this->createMock(UserModel::class);

        $this->notificationModel = $this->createMock(NotificationModel::class);

        $this->translator = $this->createMock(Translator::class);

        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
    }

    public function testContactOwnerIsNotified(): void
    {
        $event    = new Event();
        $campaign = new Campaign();
        $event->setCampaign($campaign);

        $user = $this->createUser(1);
        $lead = new Lead();
        $lead->setOwner($user);

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

    public function testCampaignCreatorIsNotified(): void
    {
        $event    = new Event();
        $campaign = new Campaign();
        $event->setCampaign($campaign);
        $campaign->setCreatedBy(1);

        $user = $this->createUser(1);
        $lead = new Lead();

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

    public function testSystemAdminIsNotified(): void
    {
        $event    = new Event();
        $campaign = new Campaign();
        $event->setCampaign($campaign);
        $campaign->setCreatedBy(2);

        $user = $this->createUser(1);
        $lead = new Lead();

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

    public function testNotificationIgnoredIfUserNotFound(): void
    {
        $event    = new Event();
        $campaign = new Campaign();
        $event->setCampaign($campaign);
        $campaign->setCreatedBy(2);

        $lead = new Lead();

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

    public function testNotificationOfUnpublishToAuthor(): void
    {
        $event    = new Event();
        $user     = $this->createUser(1);
        $this->prepareCommonMocks($event, $user);

        $this->coreParametersHelper
            ->expects($this->atLeastOnce())
            ->method('get')
            ->with('campaign_send_notification_to_author')
            ->willReturn(1);

        $this->userModel->expects($this->once())
            ->method('emailUser')
            ->with($user, 'test', 'test');

        $this->userModel->expects($this->never())
            ->method('sendMailToEmailAddresses');

        $this->getNotificationHelper()->notifyOfUnpublish($event);
    }

    public function testNotificationOfUnpublishToEmailAddress(): void
    {
        $event = new Event();
        $this->prepareCommonMocks($event, $this->createUser(1));

        $emails = 'a@test.co, b@test.co';
        $this->coreParametersHelper->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['campaign_send_notification_to_author', null, 0],
                ['campaign_notification_email_addresses', null, $emails],
            ]);

        $this->userModel->expects($this->once())
            ->method('sendMailToEmailAddresses')
            ->with(array_map(trim(...), explode(',', $emails)), 'test', 'test');

        $this->userModel->expects($this->never())
            ->method('emailUser');

        $this->getNotificationHelper()->notifyOfUnpublish($event);
    }

    private function prepareCommonMocks(Event $event, User $user): void
    {
        $campaign = new Campaign();
        $event->setCampaign($campaign);
        $campaign->setCreatedBy(2);

        $this->userModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($user);

        $this->translator
            ->method('trans')
            ->willReturn('test');
    }

    private function createUser(int $id): User
    {
        $user       = new User();
        ReflectionHelper::setEntityId($user, $id);

        return $user;
    }

    private function getNotificationHelper(): NotificationHelper
    {
        return new NotificationHelper(
            $this->userModel,
            $this->notificationModel,
            $this->translator,
            $this->createStub(Router::class),
            $this->coreParametersHelper
        );
    }
}
