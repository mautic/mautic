<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\ApiPlatform\EventListener;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Mautic\ApiBundle\ApiPlatform\EventListener\MauticDenyAccessListener;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class MauticDenyAccessListenerTest extends TestCase
{
    private MockObject&CorePermissions $corePermissionsMock;

    private ApiResource $resourceMetadata;

    private ResourceMetadataCollectionFactoryInterface&MockObject $resourceMetadataFactoryMock;

    private RequestEvent $requestEvent;

    private MauticDenyAccessListener $mauticDenyAccessListener;

    protected function setUp(): void
    {
        $attributes = [
            '_api_resource_class'      => 'TestClass',
            '_api_operation_name'      => 'Test',
            'item_operation_name'      => 'Test',
        ];
        $parameterBagMock = $this->createMock(ParameterBag::class);
        $parameterBagMock
            ->expects($this->exactly(1))
            ->method('all')
            ->willReturn($attributes);
        $formEntityMock = $this->createMock(FormEntity::class);
        $formEntityMock
            ->expects($this->atMost(1))
            ->method('getCreatedBy')
            ->willReturn(0);
        $parameterBagMock
            ->expects($this->exactly(1))
            ->method('get')
            ->with('data')
            ->willReturn($formEntityMock);
        $requestMock                          = $this->createMock(Request::class);
        $requestMock->attributes              = $parameterBagMock;
        $this->corePermissionsMock            = $this->createMock(CorePermissions::class);
        $this->resourceMetadataFactoryMock    = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $this->requestEvent                   = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $requestMock,
            HttpKernelInterface::MAIN_REQUEST
        );
        $this->mauticDenyAccessListener = new MauticDenyAccessListener($this->corePermissionsMock, $this->resourceMetadataFactoryMock);
    }

    public function testOnSecurityEntityAccessAllowed(): void
    {
        $operations = [
            new Get(
                security: '"test_item:edit"',
                name: 'Test'
            ),
        ];

        $this->resourceMetadata     = new ApiResource(operations: $operations);
        $resourceMetadataCollection = new ResourceMetadataCollection('TestClass', [$this->resourceMetadata]);
        $this->resourceMetadataFactoryMock
            ->expects($this->exactly(1))
            ->method('create')
            ->with('TestClass')
            ->willReturn($resourceMetadataCollection);
        $this->corePermissionsMock
            ->expects($this->exactly(1))
            ->method('hasEntityAccess')
            ->with('test_item:editown', 'test_item:editother', 0)
            ->willReturn(true);
        $this->mauticDenyAccessListener->onSecurity($this->requestEvent);
    }

    public function testOnSecurityIsGranted(): void
    {
        $operations = [
            new Get(
                security: '"test_item:write"',
                name: 'Test'
            ),
        ];
        $this->resourceMetadata     = new ApiResource(operations: $operations);
        $resourceMetadataCollection = new ResourceMetadataCollection('TestClass', [$this->resourceMetadata]);
        $this->resourceMetadataFactoryMock
            ->expects($this->exactly(1))
            ->method('create')
            ->with('TestClass')
            ->willReturn($resourceMetadataCollection);
        $this->corePermissionsMock
            ->expects($this->exactly(1))
            ->method('isGranted')
            ->with('test_item:write')
            ->willReturn(true);
        $this->mauticDenyAccessListener->onSecurity($this->requestEvent);
    }

    public function testOnSecurityAccessDenied(): void
    {
        $operations = [
            new Get(
                security: '"test_item:write"',
                name: 'Test'
            ),
        ];

        $this->resourceMetadata     = new ApiResource(operations: $operations);
        $resourceMetadataCollection = new ResourceMetadataCollection('TestClass', [$this->resourceMetadata]);
        $this->resourceMetadataFactoryMock
            ->expects($this->exactly(1))
            ->method('create')
            ->with('TestClass')
            ->willReturn($resourceMetadataCollection);
        $this->corePermissionsMock
            ->expects($this->exactly(1))
            ->method('isGranted')
            ->with('test_item:write')
            ->willReturn(false);
        $this->expectException(AccessDeniedException::class);
        $this->mauticDenyAccessListener->onSecurity($this->requestEvent);
    }
}
