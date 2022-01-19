<?php

namespace Mautic\UserBundle\Tests\Security\Firewall;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken;
use Mautic\ApiBundle\Entity\oAuth2\AccessToken;
use Mautic\ApiBundle\Entity\oAuth2\Client;
use Mautic\UserBundle\Entity\PermissionRepository;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Security\Authentication\AuthenticationHandler;
use Mautic\UserBundle\Security\Firewall\AuthenticationListener;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuthenticationListenerTest extends TestCase
{
    /** @var AuthenticationListener */
    private $authenticationListener;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ObjectRepository */
    private $objectRepository;

    /** @var OAuthToken */
    private $token;

    /** @var AccessToken */
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

    public function testHandle(): void
    {
        $token = 'test-token';

        $adminRole = new Role();
        $adminRole->setIsAdmin(true);

        $client = new Client();
        $client->setRole($adminRole);

        $this->accessToken = new AccessToken();
        $this->accessToken->setClient($client);

        $getResponseEvent = $this->createMock(GetResponseEvent::class);

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
            ->method('setUser');

        $this->tokenStorage->expects($this->any())
            ->method('setToken')
            ->with($this->token);

        $result = $this->authenticationListener->handle($getResponseEvent);

        $this->assertNull($result);
    }
}
