<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\UserBundle\Controller\Api\UserApiController;
use Mautic\UserBundle\Model\UserModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

final class UserApiControllerTest extends TestCase
{
    public function testGetRolesNotGranted(): void
    {
        [$security, $userModel, $container, $controller] = $this->controllerGetRoles();

        $security->expects($this->once())
            ->method('isGranted')
            ->with(
                ['user:users:create', 'user:users:edit'],
                'MATCH_ONE'
            )
            ->willReturn(false);

        $userModel->expects($this->never())
            ->method('getLookupResults');

        $viewResponse = new Response();
        $viewHandler = $this->createMock(ViewHandlerInterface::class);
        $viewHandler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(static function (View $view) use ($viewResponse): Response {
                self::assertSame(Response::HTTP_FORBIDDEN, $view->getStatusCode());
                self::assertSame(
                    [
                        'errors' => [
                            [
                                'code'    => Response::HTTP_FORBIDDEN,
                                'message' => 'mautic.core.error.accessdenied',
                                'details' => [],
                                'type'    => null,
                            ]
                        ]
                    ],
                    $view->getData()
                );

                return $viewResponse;
            });

        $container->expects($this->once())
            ->method('get')
            ->with('fos_rest.view_handler')
            ->willReturn($viewHandler);

        $request = new Request();
        $response = $controller->getRolesAction($request);

        self::assertSame($viewResponse, $response);
    }

    #[DataProvider('provideViewRolesLimits')]
    public function testGetRolesLimit(mixed $limit, int $code, array $data): void
    {
        $filterData = 'filter data';
        [$security, $leadModel, $container, $controller] = $this->controllerGetRoles();

        $security->expects($this->once())
            ->method('isGranted')
            ->with(
                ['user:users:create', 'user:users:edit'],
                'MATCH_ONE'
            )
            ->willReturn(true);

        if (Response::HTTP_OK !== $code) {
            $leadModel->expects($this->never())
                ->method('getLookupResults');
        } else {
            $leadModel->expects($this->once())
                ->method('getLookupResults')
                ->with('role', $filterData, $limit)
                ->willReturn($data);
        }

        $viewResponse = new Response();
        $viewHandler = $this->createMock(ViewHandlerInterface::class);
        $viewHandler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(static function (View $view) use ($data, $code, $viewResponse): Response {
                self::assertSame($code, $view->getStatusCode());
                self::assertSame(
                    $data,
                    $view->getData()
                );

                return $viewResponse;
            });

        $container->expects($this->once())
            ->method('get')
            ->with('fos_rest.view_handler')
            ->willReturn($viewHandler);

        $request = new Request();

        if (null !== $limit) {
            $request->query->set('limit', $limit);
        }

        $request->query->set('filter', $filterData);

        $response = $controller->getRolesAction($request);

        self::assertSame($viewResponse, $response);
    }

    public static function provideViewRolesLimits(): \Generator
    {
        $badRequest = [
            'errors' => [
                [
                    'code'    => Response::HTTP_BAD_REQUEST,
                    'message' => 'mautic.core.error.badrequest',
                    'details' => [],
                    'type'    => null,
                ]
            ]
        ];

        yield 'String limit' => ['a', Response::HTTP_BAD_REQUEST, $badRequest];
        yield 'Negative limit' => [-3, Response::HTTP_BAD_REQUEST, $badRequest];
        yield 'Zero limit' => [0, Response::HTTP_BAD_REQUEST, $badRequest];
        yield 'Ok' => [10, Response::HTTP_OK, ['good', 'data']];
    }

    /**
     * @return array{0: CorePermissions&MockObject, 1: UserModel&MockObject, ContainerInterface&MockObject, 2: UserApiController}
     */
    private function controllerGetRoles(): array
    {
        $security = $this->createMock(CorePermissions::class);
        $userModel = $this->createMock(UserModel::class);
        $container = $this->createMock(ContainerInterface::class);

        $controller = new UserApiController(
            $security,
            self::createStub(Translator::class),
            self::createStub(EntityResultHelper::class),
            self::createStub(RouterInterface::class),
            self::createStub(FormFactoryInterface::class),
            self::createStub(AppVersion::class),
            self::createStub(RequestStack::class),
            self::createStub(ManagerRegistry::class),
            self::createStub(ModelFactory::class),
            self::createStub(EventDispatcherInterface::class),
            self::createStub(CoreParametersHelper::class),
            $userModel,
        );

        $controller->setContainer($container);

        return [
            $security,
            $userModel,
            $container,
            $controller,
        ];
    }
}
