<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\ApiPlatform\EventListener;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\ApiPlatform\EventListener\MauticDenyAccessListener;
use Mautic\ApiBundle\Event\ApiPlatformPermissionContextEvent;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class MauticDenyAccessListenerTest extends TestCase
{
    private MockObject&CorePermissions $corePermissionsMock;

    private ApiResource $resourceMetadata;

    private ResourceMetadataCollectionFactoryInterface&MockObject $resourceMetadataFactoryMock;

    private Request $request;

    private RequestEvent $requestEvent;

    private MauticDenyAccessListener $mauticDenyAccessListener;

    protected function setUp(): void
    {
        $this->corePermissionsMock         = $this->createMock(CorePermissions::class);
        $this->resourceMetadataFactoryMock = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);

        $this->configureRequest($this->createFormEntityMock());

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

                if (!str_starts_with($permission, 'custom_objects:')) {
                    return;
                }

                if (!str_contains($permission, '[') && !str_contains($permission, '(')) {
                    return;
                }

                $requestObject = $event->getRequestObject();
                $match         = [];
                preg_match('#\((.*?)\)#', $permission, $match);
                $objectPath = $match[1] ?? null;

                if (is_string($objectPath) && '' !== $objectPath) {
                    $permission = substr($permission, 0, strpos($permission, '('));
                    foreach (explode('.', $objectPath) as $property) {
                        if (!is_object($requestObject) || !method_exists($requestObject, $property)) {
                            break;
                        }

                        $requestObject = $requestObject->{$property}();
                    }
                }

                if (preg_match('#\[(.*?)\]#', $permission, $match) && !empty($match[1]) && is_object($requestObject)) {
                    $getter = 'get'.ucfirst($match[1]);
                    if (method_exists($requestObject, $getter)) {
                        $objectId = $requestObject->{$getter}();

                        if (is_object($objectId) && method_exists($objectId, 'getId')) {
                            $objectId = $objectId->getId();
                        }

                        if (null !== $objectId && '' !== (string) $objectId) {
                            $permission = preg_replace('#\[(.*?)\]#', (string) $objectId, $permission, 1) ?? $permission;
                        }
                    }
                }

                $event->setPermission($permission);
                $event->setRequestObject($requestObject);
            }
        });

        $this->mauticDenyAccessListener = new MauticDenyAccessListener(
            $this->corePermissionsMock,
            $this->resourceMetadataFactoryMock,
            $dispatcher,
        );
    }

    public function testOnSecurityEntityAccessAllowed(): void
    {
        $operations = [new Get(security: '"test_item:edit"', name: 'Test')];

        $this->resourceMetadata     = new ApiResource(operations: $operations);
        $resourceMetadataCollection = new ResourceMetadataCollection('TestClass', [$this->resourceMetadata]);
        $this->resourceMetadataFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with('TestClass')
            ->willReturn($resourceMetadataCollection);
        $this->corePermissionsMock
            ->expects($this->once())
            ->method('hasEntityAccess')
            ->with('test_item:editown', 'test_item:editother', 0)
            ->willReturn(true);
        $this->mauticDenyAccessListener->onSecurity($this->requestEvent);
    }

    public function testOnSecurityIsGranted(): void
    {
        $operations = [new Get(security: '"test_item:write"', name: 'Test')];

        $this->resourceMetadata     = new ApiResource(operations: $operations);
        $resourceMetadataCollection = new ResourceMetadataCollection('TestClass', [$this->resourceMetadata]);
        $this->resourceMetadataFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with('TestClass')
            ->willReturn($resourceMetadataCollection);
        $this->corePermissionsMock
            ->expects($this->once())
            ->method('isGranted')
            ->with('test_item:write')
            ->willReturn(true);
        $this->mauticDenyAccessListener->onSecurity($this->requestEvent);
    }

    public function testOnSecurityAccessDenied(): void
    {
        $operations = [new Get(security: '"test_item:write"', name: 'Test')];

        $this->resourceMetadata     = new ApiResource(operations: $operations);
        $resourceMetadataCollection = new ResourceMetadataCollection('TestClass', [$this->resourceMetadata]);
        $this->resourceMetadataFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with('TestClass')
            ->willReturn($resourceMetadataCollection);
        $this->corePermissionsMock
            ->expects($this->once())
            ->method('isGranted')
            ->with('test_item:write')
            ->willReturn(false);
        $this->expectException(AccessDeniedException::class);
        $this->mauticDenyAccessListener->onSecurity($this->requestEvent);
    }

    public function testOnSecurityModernPermissionPlaceholderUsesResolvedId(): void
    {
        $customObjectMock = new class() {
            public function getId(): int
            {
                return 42;
            }
        };

        $requestObject = new class($customObjectMock) {
            public function __construct(
<<<<<<< HEAD
<<<<<<< HEAD
                private readonly object $customObject,
            ) {
=======
                private readonly object $customObject
            )
            {
>>>>>>> 2beec055b7 ([types] add known constant types)
=======
                private readonly object $customObject,
            ) {
>>>>>>> 7839f55c02 (cs)
            }

            public function getCustomObject(): object
            {
                return $this->customObject;
            }

            public function getCreatedBy(): int
            {
                return 0;
            }
        };

        $this->configureRequest($requestObject);

        $operations = [
            new Get(
                security: "is_granted('custom_objects:[customObject]:viewown', object)",
                name: 'Test',
            ),
        ];
        $this->resourceMetadata     = new ApiResource(operations: $operations);
        $resourceMetadataCollection = new ResourceMetadataCollection('TestClass', [$this->resourceMetadata]);
        $this->resourceMetadataFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with('TestClass')
            ->willReturn($resourceMetadataCollection);
        $this->corePermissionsMock
            ->expects($this->once())
            ->method('hasEntityAccess')
            ->with('custom_objects:42:viewown', 'custom_objects:42:viewother', 0)
            ->willReturn(true);

        $this->mauticDenyAccessListener->onSecurity($this->requestEvent);
    }

    public function testOnSecurityModernPermissionObjectPathUsesParentOwner(): void
    {
        $customFieldMock = new class() {
            public function getCreatedBy(): int
            {
                return 7;
            }
        };

        $requestObject = new class($customFieldMock) {
            public function __construct(
<<<<<<< HEAD
<<<<<<< HEAD
                private readonly object $customField,
            ) {
=======
                private readonly object $customField
            )
            {
>>>>>>> 2beec055b7 ([types] add known constant types)
=======
                private readonly object $customField,
            ) {
>>>>>>> 7839f55c02 (cs)
            }

            public function getCustomField(): object
            {
                return $this->customField;
            }
        };

        $this->configureRequest($requestObject);

        $operations = [
            new Get(
                security: "is_granted('custom_objects:custom_fields:viewown(getCustomField)', object)",
                name: 'Test',
            ),
        ];
        $this->resourceMetadata     = new ApiResource(operations: $operations);
        $resourceMetadataCollection = new ResourceMetadataCollection('TestClass', [$this->resourceMetadata]);
        $this->resourceMetadataFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with('TestClass')
            ->willReturn($resourceMetadataCollection);
        $this->corePermissionsMock
            ->expects($this->once())
            ->method('hasEntityAccess')
            ->with('custom_objects:custom_fields:viewown', 'custom_objects:custom_fields:viewother', 7)
            ->willReturn(true);

        $this->mauticDenyAccessListener->onSecurity($this->requestEvent);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function configureRequest(mixed $data, array $attributes = []): void
    {
        $this->request = new Request();
        $this->request->attributes->add($attributes + [
            '_api_resource_class'      => 'TestClass',
            '_api_operation_name'      => 'Test',
            '_api_item_operation_name' => 'Test',
            'item_operation_name'      => 'Test',
            'data'                     => $data,
        ]);

        $this->requestEvent = new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            $this->request,
            HttpKernelInterface::MAIN_REQUEST,
        );
    }

    private function createFormEntityMock(): FormEntity
    {
        return $this->createConfiguredMock(FormEntity::class, ['getCreatedBy' => 0]);
    }
}
