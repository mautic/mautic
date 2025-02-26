<?php

namespace Mautic\CoreBundle\Tests\Service;

use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\Service\FlashBag;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag as SymfonyFlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class FlashBagTest extends TestCase
{
    /**
     * @var MockObject&SymfonyFlashBag
     */
    private MockObject $symfonyFlashBag;

    /**
     * @var MockObject&Session
     */
    private MockObject $session;

    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translator;

    /**
     * @var MockObject&RequestStack
     */
    private MockObject $requestStack;

    /**
     * @var NotificationModel&MockObject
     */
    private MockObject $notificationModel;

    private FlashBag $flashBag;

    protected function setUp(): void
    {
        $this->symfonyFlashBag   = $this->createMock(SymfonyFlashBag::class);
        $this->session           = $this->createMock(Session::class);
        $this->translator        = $this->createMock(TranslatorInterface::class);
        $this->requestStack      = $this->createMock(RequestStack::class);
        $this->notificationModel = $this->createMock(NotificationModel::class);

        $this->session->method('getFlashBag')->willReturn($this->symfonyFlashBag);
        $this->requestStack->method('getSession')->willReturn($this->session);

        $this->flashBag = new FlashBag($this->translator, $this->requestStack, $this->notificationModel);

        parent::setUp();
    }

    public function testAddWithoutVars(): void
    {
        $message           = 'message';
        $messageVars       = [];
        $level             = FlashBag::LEVEL_NOTICE;
        $domain            = '';
        $addNotification   = false;
        $translatedMessage = 'translated';

        $this->translator->method('trans')
            ->with($message, $messageVars, $domain)
            ->willReturn($translatedMessage);

        $this->symfonyFlashBag
            ->expects($this->once())
            ->method('add')
            ->with($level, $translatedMessage);

        $this->flashBag->add($message, $messageVars, $level, $domain, $addNotification);
    }

    public function testAddWithChoices(): void
    {
        $message                      = 'message';
        $messageVars['%count%']       = 2;
        $translatedMessage            = 'translatedMessage';
        $level                        = FlashBag::LEVEL_NOTICE;
        $domain                       = 'flashes';
        $addNotification              = false;

        $this->symfonyFlashBag
            ->expects($this->once())
            ->method('add')
            ->with($level, $translatedMessage);

        $this->session
            ->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($this->symfonyFlashBag);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with($message, $messageVars, $domain)
            ->willReturn($translatedMessage);

        $this->flashBag->add($message, $messageVars, $level, $domain, $addNotification);
    }

    public function testAddWithTranslation(): void
    {
        $message            = 'message';
        $messageVars        = [];
        $translatedMessage  = 'translatedMessage';
        $level              = FlashBag::LEVEL_NOTICE;
        $domain             = 'flashes';
        $addNotification    = false;

        $this->symfonyFlashBag
            ->expects($this->once())
            ->method('add')
            ->with($level, $translatedMessage);

        $this->session
            ->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($this->symfonyFlashBag);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with($message, $messageVars, $domain)
            ->willReturn($translatedMessage);

        $this->flashBag->add($message, $messageVars, $level, $domain, $addNotification);
    }

    public function testReadStatusRead(): void
    {
        $this->assertReadStatus(1, true);
    }

    public function testReadStatusUnread(): void
    {
        $this->assertReadStatus(31, false);
    }

    public function testAddTypeError(): void
    {
        $this->assertAddTypeCases(FlashBag::LEVEL_ERROR, 'text-danger ri-error-warning-line-circle');
    }

    public function testAddTypeNotice(): void
    {
        $this->assertAddTypeCases(FlashBag::LEVEL_NOTICE, 'ri-information-2-line');
    }

    public function testAddTypeDefault(): void
    {
        $this->assertAddTypeCases('default', 'ri-information-2-line');
    }

    private function assertReadStatus(int $mauticUserLastActive, bool $isRead): void
    {
        $message            = 'message';
        $messageVars        = [];
        $level              = FlashBag::LEVEL_NOTICE;
        $translatedMessage  = 'translatedMessage';
        $domain             = 'flashes';
        $addNotification    = true;

        $this->symfonyFlashBag
            ->expects($this->once())
            ->method('add')
            ->with($level, $translatedMessage);

        $this->session
            ->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($this->symfonyFlashBag);

        $this->translator
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

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->notificationModel
            ->expects($this->once())
            ->method('addNotification')
            ->with($message, $level, $isRead, null, 'ri-information-2-line');

        $this->flashBag->add($message, $messageVars, $level, $domain, $addNotification);
    }

    private function assertAddTypeCases(string $level, string $expectedIcon): void
    {
        $message              = 'message';
        $messageVars          = [];
        $translatedMessage    = 'translatedMessage';
        $domain               = 'flashes';
        $addNotification      = true; // <---
        $mauticUserLastActive = 1; // <---

        $this->symfonyFlashBag
            ->expects($this->once())
            ->method('add')
            ->with($level, $translatedMessage);

        $this->session
            ->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($this->symfonyFlashBag);

        $this->translator
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

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->notificationModel
            ->expects($this->once())
            ->method('addNotification')
            ->with($message, $level, 1, null, $expectedIcon);

        $this->flashBag->add($message, $messageVars, $level, $domain, $addNotification);
    }
}
