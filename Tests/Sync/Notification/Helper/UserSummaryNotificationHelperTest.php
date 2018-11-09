<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Sync\Notification\Helper;


use MauticPlugin\IntegrationsBundle\Sync\Notification\Helper\RouteHelper;
use MauticPlugin\IntegrationsBundle\Sync\Notification\Helper\UserHelper;
use MauticPlugin\IntegrationsBundle\Sync\Notification\Helper\UserSummaryNotificationHelper;
use MauticPlugin\IntegrationsBundle\Sync\Notification\Writer;
use Symfony\Component\Translation\TranslatorInterface;

class UserSummaryNotificationHelperTest extends \PHPUnit_Framework_TestCase
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
     * @var RouteHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $routeHelper;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $translator;

    protected function setUp()
    {
        $this->writer      = $this->createMock(Writer::class);
        $this->userHelper  = $this->createMock(UserHelper::class);
        $this->routeHelper = $this->createMock(RouteHelper::class);
        $this->translator  = $this->createMock(TranslatorInterface::class);
    }

    public function testNotificationSentToOwner()
    {
        $helper = $this->getHelper();
        $helper->storeSummaryNotification('Foo', 'Bar', 1);
        $helper->storeSummaryNotification('Bar', 'Foo', 2);

        $this->userHelper->expects($this->exactly(2))
            ->method('getOwners')
            ->willReturnCallback(
                function (string $object, array $ids)
                {
                    return [1 => $ids];
                }
            );

        $this->userHelper->expects($this->never())
            ->method('getAdminUsers');

        $this->translator->expects($this->at(0))
            ->method('trans')
            ->with('mautic.integration.sync.user_notification.header', $this->anything())
            ->willReturn('test');
        $this->translator->expects($this->at(1))
            ->method('trans')
            ->with('test', $this->anything())
            ->willReturn('test');
        $this->translator->expects($this->at(2))
            ->method('trans')
            ->with('mautic.integration.sync.user_notification.header', $this->anything())
            ->willReturn('test');
        $this->translator->expects($this->at(3))
            ->method('trans')
            ->with('test', $this->anything())
            ->willReturn('test');

        $this->writer->expects($this->exactly(2))
            ->method('writeUserNotification');

        $this->routeHelper->expects($this->exactly(2))
            ->method('getLinkCsv');

        $helper->writeNotifications('test', 'test');
    }

    public function testNotificationSentToAdmins()
    {
        $helper = $this->getHelper();
        $helper->storeSummaryNotification('Foo', 'Bar', 1);
        $helper->storeSummaryNotification('Bar', 'Foo', 2);

        $this->userHelper->expects($this->exactly(2))
            ->method('getOwners')
            ->willReturn([]);

        $this->userHelper->expects($this->exactly(2))
            ->method('getAdminUsers')
            ->willReturn([1]);

        $this->translator->expects($this->at(0))
            ->method('trans')
            ->with('mautic.integration.sync.user_notification.header', $this->anything())
            ->willReturn('test');
        $this->translator->expects($this->at(1))
            ->method('trans')
            ->with('test', $this->anything())
            ->willReturn('test');
        $this->translator->expects($this->at(2))
            ->method('trans')
            ->with('mautic.integration.sync.user_notification.header', $this->anything())
            ->willReturn('test');
        $this->translator->expects($this->at(3))
            ->method('trans')
            ->with('test', $this->anything())
            ->willReturn('test');

        $this->writer->expects($this->exactly(2))
            ->method('writeUserNotification');

        $this->routeHelper->expects($this->exactly(2))
            ->method('getLinkCsv');

        $helper->writeNotifications('test', 'test');
    }

    public function testMoreThan25ObjectsResultInCountMessage()
    {
        $helper = $this->getHelper();

        $counter = 1;
        do {
            $helper->storeSummaryNotification('Foo', 'Bar', $counter);
            ++$counter;
        } while ($counter <= 26);

        $this->userHelper->expects($this->once())
            ->method('getOwners')
            ->willReturn([]);

        $this->userHelper->expects($this->once())
            ->method('getAdminUsers')
            ->willReturn([1]);

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnCallback(
                function ($string, $params)
                {
                    $expectedStrings = [
                        'mautic.integration.sync.user_notification.header',
                        'mautic.integration.sync.user_notification.count_message',
                    ];

                    if (!in_array($string, $expectedStrings)) {
                        $this->fail($string.' is not an expected translation key');
                    }

                    return $string;
                }
            );

        $this->writer->expects($this->exactly(1))
            ->method('writeUserNotification');

        $this->routeHelper->expects($this->never())
            ->method('getLinkCsv');

        $helper->writeNotifications('test', 'test');
    }

    /**
     * @return UserSummaryNotificationHelper
     */
    private function getHelper()
    {
        return new UserSummaryNotificationHelper(
            $this->writer,
            $this->userHelper,
            $this->routeHelper,
            $this->translator
        );
    }
}