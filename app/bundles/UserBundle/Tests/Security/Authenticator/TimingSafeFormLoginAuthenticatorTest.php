<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\Authenticator;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\TimingSafeFormLoginAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;

class TimingSafeFormLoginAuthenticatorTest extends TestCase
{
    /**
     * @return array<mixed>
     */
    private function getCredentials(TimingSafeFormLoginAuthenticator $authenticator, Request $request): array
    {
        $method = new \ReflectionMethod(TimingSafeFormLoginAuthenticator::class, 'getCredentials');
        $method->setAccessible(true);

        return $method->invoke($authenticator, $request);
    }

    public function testAuthenticateWithExistingUser(): void
    {
        $request = new Request([], ['username' => 'testuser', 'password' => 'password']);
        $request->setSession(new Session(new MockArraySessionStorage()));
        $user    = new User();
        $user->setUsername('testuser');

        /** @var UserProviderInterface|\PHPUnit\Framework\MockObject\MockObject $userProvider */
        $userProvider = $this->createMock(UserProviderInterface::class);
        $userProvider->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with('testuser')
            ->willReturn($user);

        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        /** @var PasswordHasherFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $passwordHasherFactory */
        $passwordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $passwordHasherFactory->expects($this->never())
            ->method('getPasswordHasher');

        /** @var FormLoginAuthenticator|\PHPUnit\Framework\MockObject\MockObject $formLoginAuthenticator */
        $formLoginAuthenticator = $this->createMock(FormLoginAuthenticator::class);

        $authenticator = new TimingSafeFormLoginAuthenticator(
            $formLoginAuthenticator,
            $userProvider,
            $passwordHasherFactory,
            [
                'enable_csrf'        => false,
                'username_parameter' => 'username',
                'password_parameter' => 'password',
                'csrf_parameter'     => '_csrf_token',
                'post_only'          => true,
            ]
        );

        $credentials = $this->getCredentials($authenticator, $request);
        $this->assertEquals('testuser', $credentials['username']);
        $this->assertEquals('password', $credentials['password']);

        $passport = $authenticator->authenticate($request);
        $passport->getUser();
    }

    public function testAuthenticateWithNonExistingUser(): void
    {
        $this->expectException(UserNotFoundException::class);

        $request = new Request([], ['username' => 'testuser', 'password' => 'password']);
        $request->setSession(new Session(new MockArraySessionStorage()));

        /** @var UserProviderInterface|\PHPUnit\Framework\MockObject\MockObject $userProvider */
        $userProvider = $this->createMock(UserProviderInterface::class);
        $userProvider->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with('testuser')
            ->willThrowException(new UserNotFoundException());

        /** @var PasswordHasherInterface|\PHPUnit\Framework\MockObject\MockObject $passwordHasher */
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $passwordHasher->expects($this->once())
            ->method('verify')
            ->with('$2y$13$aAwXNyqA87lcXQQuk8Cp6eo2amRywLct29oG2uWZ8lYBeamFZ8UhK', 'password');

        /** @var PasswordHasherFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $passwordHasherFactory */
        $passwordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $passwordHasherFactory->expects($this->once())
            ->method('getPasswordHasher')
            ->willReturn($passwordHasher);

        /** @var FormLoginAuthenticator|\PHPUnit\Framework\MockObject\MockObject $formLoginAuthenticator */
        $formLoginAuthenticator = $this->createMock(FormLoginAuthenticator::class);

        $authenticator = new TimingSafeFormLoginAuthenticator(
            $formLoginAuthenticator,
            $userProvider,
            $passwordHasherFactory,
            [
                'enable_csrf'        => false,
                'username_parameter' => 'username',
                'password_parameter' => 'password',
                'csrf_parameter'     => '_csrf_token',
                'post_only'          => true,
            ]
        );

        $passport = $authenticator->authenticate($request);
        $passport->getUser();
    }
}
