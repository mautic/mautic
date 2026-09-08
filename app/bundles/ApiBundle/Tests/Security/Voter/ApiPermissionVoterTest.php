<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\Security\Voter;

use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\Event\ApiPlatformPermissionContextEvent;
use Mautic\ApiBundle\Security\Voter\ApiPermissionVoter;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class ApiPermissionVoterTest extends TestCase
{
    private CorePermissions&MockObject $corePermissionsMock;

    private ApiPermissionVoter $voter;

    protected function setUp(): void
    {
        $this->corePermissionsMock = $this->createMock(CorePermissions::class);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new class() implements EventSubscriberInterface {
            public static function getSubscribedEvents(): array
            {
                return [
                    ApiEvents::API_PLATFORM_PERMISSION_CONTEXT => ['onApiPlatformPermissionContext', 0],
                ];
            }

            public function onApiPlatformPermissionContext(ApiPlatformPermissionContextEvent $event): void
            {
                $permission = $event->getPermission();

                if ('custom:bridge:view' === $permission) {
                    $event->setPermission('custom:bridge:viewown');
                    $event->setRequestObject(new class() {
                        public function getCreatedBy(): int
                        {
                            return 12;
                        }
                    });

                    return;
                }

                if ('custom:bridge:write' === $permission) {
                    $event->setPermission('custom:bridge:publish');
                }
            }
        });

        $this->voter = new ApiPermissionVoter($this->corePermissionsMock, $dispatcher);
    }

    public function testVoteUsesMutatedOwnershipPermissionAndSubject(): void
    {
        $token = $this->createTokenWithUser();

        $this->corePermissionsMock
            ->expects($this->once())
            ->method('hasEntityAccess')
            ->with('custom:bridge:viewown', 'custom:bridge:viewother', 12)
            ->willReturn(true);
        $this->corePermissionsMock
            ->expects($this->never())
            ->method('isGranted');

        $result = $this->voter->vote($token, new \stdClass(), ['custom:bridge:view']);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testVoteUsesMutatedNonOwnershipPermission(): void
    {
        $token = $this->createTokenWithUser();

        $this->corePermissionsMock
            ->expects($this->once())
            ->method('isGranted')
            ->with('custom:bridge:publish')
            ->willReturn(true);
        $this->corePermissionsMock
            ->expects($this->never())
            ->method('hasEntityAccess');

        $result = $this->voter->vote($token, null, ['custom:bridge:write']);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    private function createTokenWithUser(): TokenInterface&MockObject
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($this->createStub(UserInterface::class));

        return $token;
    }
}
