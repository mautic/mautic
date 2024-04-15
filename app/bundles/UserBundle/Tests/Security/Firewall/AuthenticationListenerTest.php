<?php

namespace Mautic\UserBundle\Tests\Security\Firewall;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken;
use Mautic\ApiBundle\Entity\oAuth2\AccessToken;
use Mautic\ApiBundle\Entity\oAuth2\Client;
use Mautic\UserBundle\Entity\PermissionRepository;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\Authentication\AuthenticationHandler;
use Mautic\UserBundle\Security\Firewall\AuthenticationListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuthenticationListenerTest extends TestCase
{
    private \Mautic\UserBundle\Security\Firewall\AuthenticationListener $authenticationListener;

    /**
     * @var TokenStorageInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $tokenStorage;

    /**
     * @var EntityManagerInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $entityManager;

    /**
     * @var ObjectRepository<User>&MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $objectRepository;

    /**
     * @var OAuthToken
     */
    private \PHPUnit\Framework\MockObject\MockObject $token;

    /**
     * @var AccessToken
     */
    private $accessToken;

    public function setUp(): void
    {
        $authenticationHandler  = $this->createMock(AuthenticationHandler::class);
        $this->tokenStorage     = $this->createMock(TokenStorageInterface::class);
        $authenticationManager  = $this->createMock(AuthenticationManagerInterface::class);
        $logger                 = $this->createMock(LoggerInterface::class);
        $dispatcher             = $this->createMock(EventDispatcherInterface::class);
        $permissionRepository   = $this->createMock(PermissionRepository::class);
        $this->entityManager    = $this->createMock(EntityManagerInterface::class);
        $this->token            = $this->createMock(OAuthToken::class);
        $this->objectRepository = $this->createMock(ObjectRepository::class);

        $this->authenticationListener = new AuthenticationListener(
            $authenticationHandler,
            $this->tokenStorage,
            $authenticationManager,
            $logger,
            $dispatcher,
            'api',
            $permissionRepository,
            $this->entityManager
        );
    }

    public function testInvoke(): void
    {
        $token     = 'test-token';
        $adminRole = new Role();
        $adminRole->setIsAdmin(true);

        $client = new class() extends Client {
            public function getId()
            {
                return 123;
            }
        };
        $client->setRole($adminRole);
        $client->setName('test-client');

        $this->accessToken = new AccessToken();
        $this->accessToken->setClient($client);

        $requestEvent = $this->createMock(RequestEvent::class);

        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($this->token);

        $this->token->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->entityManager
            ->method('getRepository')
            ->with(AccessToken::class)
            ->willReturn($this->objectRepository);

        $this->objectRepository
            ->method('findOneBy')
            ->with(['token' => $token])
            ->willReturn($this->accessToken);

        $this->token->expects($this->any())
            ->method('setUser')
            ->with($this->callback(function (User $user) use ($adminRole) {
                $this->assertSame('test-client', $user->getFirstName());
                $this->assertSame('[123]', $user->getLastName());
                $this->assertSame('test-client [123]', $user->getUserIdentifier());
                $this->assertSame($adminRole, $user->getRole());

                return true;
            }));

        $this->tokenStorage->expects($this->any())
            ->method('setToken')
            ->with($this->token);

        $invokableListener = $this->authenticationListener;
        $invokableListener($requestEvent);
    }
}
