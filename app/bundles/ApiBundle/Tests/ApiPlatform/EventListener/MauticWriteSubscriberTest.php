<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\ApiPlatform\EventListener;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use Mautic\ApiBundle\ApiPlatform\EventListener\MauticWriteSubscriber;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class MauticWriteSubscriberTest extends TestCase
{
    private MauticWriteSubscriber $mauticWriteSubscriber;

    private ViewEvent $event;

    private MockObject&FormEntity $formEntityMock;

    private Request&MockObject $requestMock;

    private UserHelper&MockObject $userHelperMock;

    protected function setUp(): void
    {
        $this->userHelperMock        = $this->createMock(UserHelper::class);
        $this->mauticWriteSubscriber = new MauticWriteSubscriber($this->userHelperMock);
        $this->requestMock           = $this->createMock(Request::class);
        $this->formEntityMock        = $this->createMock(FormEntity::class);
        $kernelMock                  = $this->createMock(HttpKernelInterface::class);
        $this->event                 = new ViewEvent(
            $kernelMock,
            $this->requestMock,
            HttpKernelInterface::MAIN_REQUEST,
            $this->formEntityMock,
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $expected = [
            'kernel.view'=> ['addData', EventPriorities::PRE_WRITE],
        ];
        $this->assertEquals($expected, MauticWriteSubscriber::getSubscribedEvents());
    }

    public function testAddDataWithWrongMethod(): void
    {
        $this->requestMock
            ->expects($this->exactly(1))
            ->method('getMethod')
            ->willReturn('GET');
        $this->formEntityMock
            ->expects($this->never())
            ->method('isNew');
        $this->mauticWriteSubscriber->addData($this->event);
    }

    public function testAddData(): void
    {
        $this->requestMock
            ->expects($this->exactly(1))
            ->method('getMethod')
            ->willReturn('POST');
        $this->formEntityMock
            ->expects($this->exactly(1))
            ->method('isNew')
            ->willReturn(false);
        $userMock = $this->createMock(User::class);
        $userMock
            ->expects($this->exactly(1))
            ->method('getName')
            ->willReturn('Pepa');
        $this->userHelperMock
            ->expects($this->exactly(1))
            ->method('getUser')
            ->willReturn($userMock);
        $this->formEntityMock
            ->expects($this->exactly(1))
            ->method('setModifiedBy')
            ->with($userMock);
        $this->formEntityMock
            ->expects($this->exactly(1))
            ->method('setModifiedByUser')
            ->with('Pepa');
        $this->formEntityMock
            ->expects($this->exactly(1))
            ->method('setDateModified')
            ->withAnyParameters();
        $this->mauticWriteSubscriber->addData($this->event);
    }
}
