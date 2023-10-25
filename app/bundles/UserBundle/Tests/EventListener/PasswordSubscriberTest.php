<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\EventListener;

use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\EventListener\PasswordSubscriber;
use Mautic\UserBundle\Exception\WeakPasswordException;
use Mautic\UserBundle\Model\PasswordStrengthEstimatorModel;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Mautic\UserBundle\UserEvents;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PasswordSubscriberTest extends TestCase
{
    private PasswordSubscriber $passwordSubscriber;

    private PasswordStrengthEstimatorModel $passwordStrengthEstimatorModel;

    /**
     * @var MockObject&AuthenticationEvent
     */
    private $authenticationEvent;

    /**
     * @var MockObject&PluginToken
     */
    private $pluginToken;

    protected function setUp(): void
    {
        $this->passwordStrengthEstimatorModel = new PasswordStrengthEstimatorModel();
        $this->passwordSubscriber             = new PasswordSubscriber($this->passwordStrengthEstimatorModel);
        $this->authenticationEvent            = $this->createMock(AuthenticationEvent::class);
        $this->pluginToken                    = $this->createMock(PluginToken::class);

        $this->authenticationEvent->expects($this->any())
            ->method('getToken')
            ->willReturn($this->pluginToken);
    }

    public function testThatItIsSubcribedToEvents(): void
    {
        Assert::assertArrayHasKey(UserEvents::USER_FORM_POST_LOCAL_PASSWORD_AUTHENTICATION, PasswordSubscriber::getSubscribedEvents());
    }

    public function testThatItThrowsExceptionIfPasswordIsWeak(): void
    {
        $this->expectException(WeakPasswordException::class);

        $this->pluginToken->expects($this->once())
            ->method('getCredentials')
            ->willReturn('11111111');

        $this->passwordSubscriber->onUserFormAuthentication($this->authenticationEvent);
    }

    public function testThatItDoesntThrowExceptionIfPasswordIsStrong(): void
    {
        $this->pluginToken->expects($this->once())
            ->method('getCredentials')
            ->willReturn(uniqid());

        $this->passwordSubscriber->onUserFormAuthentication($this->authenticationEvent);
        $this->addToAssertionCount(1); // Verify that no exception is thrown
    }
}
