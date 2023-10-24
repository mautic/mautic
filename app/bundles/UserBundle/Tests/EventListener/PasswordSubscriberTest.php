<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\EventListener;

use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\EventListener\PasswordSubscriber;
use Mautic\UserBundle\Exception\WeakPasswordException;
use Mautic\UserBundle\Model\PasswordStrengthEstimatorModel;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Mautic\UserBundle\UserEvents;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Router;
use ZxcvbnPhp\Zxcvbn;

class PasswordSubscriberTest extends TestCase
{
    /**
     * @var PasswordSubscriber
     */
    private $passwordSubscriber;

    /**
     * @var PasswordStrengthEstimatorModel
     */
    private $passwordStrengthEstimatorModel;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var AuthenticationEvent
     */
    private $authenticationEvent;

    /**
     * @var PluginToken
     */
    private $pluginToken;

    protected function setUp(): void
    {
        $this->passwordStrengthEstimatorModel = new PasswordStrengthEstimatorModel(new Zxcvbn());
        $this->userRepository                 = $this->createMock(UserRepository::class);
        $this->router                         = $this->createMock(Router::class);
        $this->passwordSubscriber             = new PasswordSubscriber($this->passwordStrengthEstimatorModel, $this->userRepository, $this->router);
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
