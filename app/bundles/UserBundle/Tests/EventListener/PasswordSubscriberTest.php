<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\EventListener;

use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\EventListener\PasswordSubscriber;
use Mautic\UserBundle\Exception\WeakPasswordException;
use Mautic\UserBundle\Model\PasswordStrengthEstimatorModel;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Mautic\UserBundle\Security\Authenticator\Passport\Badge\PasswordStrengthBadge;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CredentialsInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

final class PasswordSubscriberTest extends TestCase
{
    private PasswordSubscriber $passwordSubscriber;

    protected function setUp(): void
    {
        $dispatcher                           = $this->createMock(EventDispatcherInterface::class);
        $passwordStrengthEstimatorModel       = new PasswordStrengthEstimatorModel($dispatcher);
        $this->passwordSubscriber             = new PasswordSubscriber($passwordStrengthEstimatorModel);
        $authenticationEvent                  = $this->createMock(AuthenticationEvent::class);
        $pluginToken                          = $this->createMock(PluginToken::class);

        $authenticationEvent->expects($this->any())
            ->method('getToken')
            ->willReturn($pluginToken);
    }

    public function testThatItIsSubscribedToEvents(): void
    {
        $subscribedEvents = PasswordSubscriber::getSubscribedEvents();
        Assert::assertCount(1, $subscribedEvents);
        Assert::assertArrayHasKey(CheckPassportEvent::class, $subscribedEvents);
    }

    public function testThatItThrowsExceptionIfPasswordIsWeak(): void
    {
        $this->expectException(WeakPasswordException::class);

        $passwordStrengthBadge = new PasswordStrengthBadge('11111111');

        $this->passwordSubscriber->checkPassport(
            new CheckPassportEvent(
                $this->createStub(AuthenticatorInterface::class),
                new Passport(
                    $this->createStub(UserBadge::class),
                    $this->createStub(CredentialsInterface::class),
                    [$passwordStrengthBadge]
                )
            )
        );
    }

    public function testThatItDoesntThrowExceptionIfPasswordIsStrong(): void
    {
        $passwordStrengthBadge = new PasswordStrengthBadge(uniqid('password_strength', true));

        $this->passwordSubscriber->checkPassport(
            new CheckPassportEvent(
                $this->createStub(AuthenticatorInterface::class),
                new Passport(
                    $this->createStub(UserBadge::class),
                    $this->createStub(CredentialsInterface::class),
                    [$passwordStrengthBadge]
                )
            )
        );

        $this->addToAssertionCount(1); // Verify that no exception is thrown
    }
}
