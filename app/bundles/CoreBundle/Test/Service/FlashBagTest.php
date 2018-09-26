<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Test\Service;

use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\Service\FlashBag;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag as SymfonyFlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

class FlashBagTest extends TestCase
{
    public function testAdd()
    {
        // define('MAUTIC_INSTALLER', true); - this is dangerous for further tests, not used

        $message          = 'message';
        $messageVars      = [];
        $level            = FlashBag::LEVEL_NOTICE;
        $domain           = false;
        $addNotification  = false;
        $symfonyFlashBag  = $this->createMock(SymfonyFlashBag::class);
        $symfonyFlashBag
            ->expects($this->once())
            ->method('add')
            ->with($level, $message);
        $session = $this->createMock(Session::class);
        $session
            ->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($symfonyFlashBag);
        $translator        = $this->createMock(TranslatorInterface::class);
        $requestStack      = $this->createMock(RequestStack::class);
        $notificationModel = $this->createMock(NotificationModel::class);
        $flashBag          = new FlashBag($session, $translator, $requestStack, $notificationModel);

        $flashBag->add($message, $messageVars, $level, $domain, $addNotification);

        $message                     = 'message';
        $messageVars['pluralCount']  = 2;
        $translatedMessage           = 'translatedMessage';
        $level                       = FlashBag::LEVEL_NOTICE;
        $domain                      = 'flashes';
        $addNotification             = false;
        $symfonyFlashBag             = $this->createMock(SymfonyFlashBag::class);
        $symfonyFlashBag
            ->expects($this->once())
            ->method('add')
            ->with($level, $translatedMessage);
        $session = $this->createMock(Session::class);
        $session
            ->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($symfonyFlashBag);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('transChoice')
            ->with($message, $messageVars['pluralCount'], $messageVars, $domain)
            ->willReturn($translatedMessage);
        $requestStack      = $this->createMock(RequestStack::class);
        $notificationModel = $this->createMock(NotificationModel::class);
        $flashBag          = new FlashBag($session, $translator, $requestStack, $notificationModel);

        $flashBag->add($message, $messageVars, $level, $domain, $addNotification);

        $message            = 'message';
        $messageVars        = [];
        $translatedMessage  = 'translatedMessage';
        $level              = FlashBag::LEVEL_NOTICE;
        $domain             = 'flashes';
        $addNotification    = false;
        $symfonyFlashBag    = $this->createMock(SymfonyFlashBag::class);
        $symfonyFlashBag
            ->expects($this->once())
            ->method('add')
            ->with($level, $translatedMessage);
        $session = $this->createMock(Session::class);
        $session
            ->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($symfonyFlashBag);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->with($message, $messageVars, $domain)
            ->willReturn($translatedMessage);
        $requestStack      = $this->createMock(RequestStack::class);
        $notificationModel = $this->createMock(NotificationModel::class);
        $flashBag          = new FlashBag($session, $translator, $requestStack, $notificationModel);

        $flashBag->add($message, $messageVars, $level, $domain, $addNotification);

        $this->testReadStatus(1, true);
        $this->testReadStatus(31, false);

        $this->testAddTypeCases(FlashBag::LEVEL_ERROR, 'text-danger fa-exclamation-circle');
        $this->testAddTypeCases(FlashBag::LEVEL_WARNING, 'text-warning fa-exclamation-triangle');
        $this->testAddTypeCases(FlashBag::LEVEL_NOTICE, 'fa-info-circle');
        $this->testAddTypeCases('default', 'fa-info-circle');
    }

    private function testReadStatus($mauticUserLastActive, $isRead)
    {
        $message            = 'message';
        $messageVars        = [];
        $level              = FlashBag::LEVEL_NOTICE;
        $translatedMessage  = 'translatedMessage';
        $domain             = 'flashes';
        $addNotification    = true;
        $isRead             = $mauticUserLastActive > 30 ? 0 : 1;
        $symfonyFlashBag    = $this->createMock(SymfonyFlashBag::class);
        $symfonyFlashBag
            ->expects($this->once())
            ->method('add')
            ->with($level, $translatedMessage);
        $session = $this->createMock(Session::class);
        $session
            ->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($symfonyFlashBag);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->with($message, $messageVars, $domain)
            ->willReturn($translatedMessage);
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('get')
            ->with('mauticUserLastActive', 0)
            ->willReturn($mauticUserLastActive);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $notificationModel = $this->createMock(NotificationModel::class);
        $notificationModel
            ->expects($this->once())
            ->method('addNotification')
            ->with($message, $level, $isRead, null, 'fa-info-circle');
        $flashBag = new FlashBag($session, $translator, $requestStack, $notificationModel);

        $flashBag->add($message, $messageVars, $level, $domain, $addNotification);
    }

    private function testAddTypeCases($level, $expectedIcon)
    {
        $message              = 'message';
        $messageVars          = [];
        $translatedMessage    = 'translatedMessage';
        $domain               = 'flashes';
        $addNotification      = true; // <---
        $mauticUserLastActive = 1; // <---
        $symfonyFlashBag      = $this->createMock(SymfonyFlashBag::class);
        $symfonyFlashBag
            ->expects($this->once())
            ->method('add')
            ->with($level, $translatedMessage);
        $session = $this->createMock(Session::class);
        $session
            ->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($symfonyFlashBag);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->with($message, $messageVars, $domain)
            ->willReturn($translatedMessage);
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('get')
            ->with('mauticUserLastActive', 0)
            ->willReturn($mauticUserLastActive);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $notificationModel = $this->createMock(NotificationModel::class);
        $notificationModel
            ->expects($this->once())
            ->method('addNotification')
            ->with($message, $level, 1, null, $expectedIcon);
        $flashBag = new FlashBag($session, $translator, $requestStack, $notificationModel);

        $flashBag->add($message, $messageVars, $level, $domain, $addNotification);
    }
}
