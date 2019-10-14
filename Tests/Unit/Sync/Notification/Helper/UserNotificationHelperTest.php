<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Unit\Sync\Notification\Helper;

use MauticPlugin\IntegrationsBundle\Sync\Notification\Helper\OwnerProvider;
use MauticPlugin\IntegrationsBundle\Sync\Notification\Helper\RouteHelper;
use MauticPlugin\IntegrationsBundle\Sync\Notification\Helper\UserHelper;
use MauticPlugin\IntegrationsBundle\Sync\Notification\Helper\UserNotificationHelper;
use MauticPlugin\IntegrationsBundle\Sync\Notification\Writer;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Symfony\Component\Translation\TranslatorInterface;

class UserNotificationHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Writer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $writer;

    /**
     * @var UserHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $userHelper;

    /**
     * @var OwnerProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $ownerProvider;

    /**
     * @var RouteHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $routeHelper;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $translator;

    /**
     * @var UserNotificationHelper
     */
    private $helper;

    protected function setUp(): void
    {
        $this->writer        = $this->createMock(Writer::class);
        $this->userHelper    = $this->createMock(UserHelper::class);
        $this->ownerProvider = $this->createMock(OwnerProvider::class);
        $this->routeHelper   = $this->createMock(RouteHelper::class);
        $this->translator    = $this->createMock(TranslatorInterface::class);
        $this->helper        = new UserNotificationHelper(
            $this->writer,
            $this->userHelper,
            $this->ownerProvider,
            $this->routeHelper,
            $this->translator
        );
    }

    public function testNotificationSentToOwner(): void
    {
        $this->ownerProvider->expects($this->once())
            ->method('getOwnersForObjectIds')
            ->with(Contact::NAME, [1])
            ->willReturn([['owner_id' => 1, 'id' => 1]]);

        $this->userHelper->expects($this->never())
            ->method('getAdminUsers');

        $this->translator->expects($this->at(0))
            ->method('trans')
            ->with('mautic.integration.sync.user_notification.header', $this->anything())
            ->willReturn('test');
        $this->translator->expects($this->at(1))
            ->method('trans')
            ->with('mautic.integration.sync.user_notification.sync_error', $this->anything())
            ->willReturn('test');

        $this->writer->expects($this->once())
            ->method('writeUserNotification');

        $this->routeHelper->expects($this->once())
            ->method('getLink');

        $this->helper->writeNotification('test', 'test', 'test', Contact::NAME, 1, 'foobar');
    }

    public function testNotificationSentToAdmins(): void
    {
        $this->ownerProvider->expects($this->once())
            ->method('getOwnersForObjectIds')
            ->with(Contact::NAME, [1])
            ->willReturn([]);

        $this->userHelper->expects($this->once())
            ->method('getAdminUsers')
            ->willReturn([1]);

        $this->translator->expects($this->at(0))
            ->method('trans')
            ->with('mautic.integration.sync.user_notification.header', $this->anything())
            ->willReturn('test');
        $this->translator->expects($this->at(1))
            ->method('trans')
            ->with('mautic.integration.sync.user_notification.sync_error', $this->anything())
            ->willReturn('test');

        $this->writer->expects($this->once())
            ->method('writeUserNotification');

        $this->routeHelper->expects($this->once())
            ->method('getLink');

        $this->helper->writeNotification('test', 'test', 'test', Contact::NAME, 1, 'foobar');
    }
}
