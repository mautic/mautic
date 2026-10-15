<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\ApiPlatform\EventListener;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use Mautic\ApiBundle\ApiPlatform\EventListener\MauticWriteSubscriber;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Test\ReflectionHelper;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[AllowMockObjectsWithoutExpectations]
final class MauticWriteSubscriberTest extends TestCase
{
    private MauticWriteSubscriber $mauticWriteSubscriber;

    private ViewEvent $event;

    private FormEntity $formEntity;

    private Request&MockObject $requestMock;

    private UserHelper&MockObject $userHelperMock;

    protected function setUp(): void
    {
        $this->userHelperMock        = $this->createMock(UserHelper::class);
        $this->mauticWriteSubscriber = new MauticWriteSubscriber($this->userHelperMock);
        $this->requestMock           = $this->createMock(Request::class);
        $this->formEntity            = new class() extends FormEntity {
            public function getId(): int
            {
                return 1;
            }
        };
        $this->event                 = new ViewEvent(
            $this->createStub(HttpKernelInterface::class),
            $this->requestMock,
            HttpKernelInterface::MAIN_REQUEST,
            $this->formEntity,
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $expected = [
            'kernel.view'=> ['addData', EventPriorities::PRE_WRITE],
        ];
        $this->assertSame($expected, MauticWriteSubscriber::getSubscribedEvents());
    }

    public function testAddDataWithWrongMethod(): void
    {
        $this->requestMock
            ->expects($this->exactly(1))
            ->method('getMethod')
            ->willReturn('GET');
        $this->mauticWriteSubscriber->addData($this->event);

        $this->assertNull($this->formEntity->getModifiedBy());
        $this->assertNotInstanceOf(\DateTimeInterface::class, $this->formEntity->getDateModified());
    }

    public function testAddData(): void
    {
        $this->requestMock
            ->expects($this->exactly(1))
            ->method('getMethod')
            ->willReturn('POST');
        $user = new User();
        $user->setFirstName('Pepa');
        $user->setLastName('Novak');
        ReflectionHelper::setValue($user, 'id', 5);

        $this->userHelperMock
            ->expects($this->exactly(1))
            ->method('getUser')
            ->willReturn($user);
        $this->mauticWriteSubscriber->addData($this->event);

        $this->assertSame(5, $this->formEntity->getModifiedBy());
        $this->assertSame('Pepa Novak', $this->formEntity->getModifiedByUser());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->formEntity->getDateModified());
    }
}
