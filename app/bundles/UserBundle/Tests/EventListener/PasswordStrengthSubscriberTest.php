<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\EventListener;

use Mautic\UserBundle\EventListener\PasswordStrengthSubscriber;
use Mautic\UserBundle\Security\Authenticator\Passport\Badge\PasswordStrengthBadge;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

class PasswordStrengthSubscriberTest extends TestCase
{
    public function testNoCheckPassportEvent(): void
    {
        $passport = $this->createMock(Passport::class);
        $passport->method('hasBadge')
            ->with(PasswordCredentials::class)
            ->willReturn(false);
        $passport->expects(self::never())
            ->method('getBadge');

        $event = $this->createMock(CheckPassportEvent::class);
        $event->method('getPassport')
            ->willReturn($passport);

        $subscriber = new PasswordStrengthSubscriber();
        $subscriber->checkPassport($event);
    }

    public function testCheckPassportEvent(): void
    {
        $password                 = 'Keilschrift';
        $passwordCredentialsBadge = $this->createMock(PasswordCredentials::class);
        $passwordCredentialsBadge->method('getPassword')
            ->willReturn($password);

        $passport = $this->createMock(Passport::class);
        $passport->method('hasBadge')
            ->with(PasswordCredentials::class)
            ->willReturn(true);
        $passport->expects(self::once())
            ->method('getBadge')
            ->with(PasswordCredentials::class)
            ->willReturn($passwordCredentialsBadge);
        $passport->expects(self::once())
            ->method('addBadge')
            ->willReturnCallback(static function (PasswordStrengthBadge $badge) use ($passport, $password): Passport {
                self::assertSame($password, $badge->getPresentedPassword());

                return $passport;
            });

        $event = $this->createMock(CheckPassportEvent::class);
        $event->method('getPassport')
            ->willReturn($passport);

        $subscriber = new PasswordStrengthSubscriber();
        $subscriber->checkPassport($event);
    }
}
