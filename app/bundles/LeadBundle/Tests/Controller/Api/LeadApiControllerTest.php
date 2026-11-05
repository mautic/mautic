<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;


use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Controller\Api\LeadApiController;
use Mautic\LeadBundle\Deduplicate\ContactMerger;
use Mautic\LeadBundle\Model\DeviceModel;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\NoteModel;
use Mautic\StageBundle\Model\StageModel;
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

final class LeadApiControllerTest extends TestCase
{
    public function testGetOwnersNotGranted(): void
    {
        [$security, $leadModel, $container, $controller] = $this->controllerGetOwners();

        $security->expects($this->once())
            ->method('isGranted')
            ->with(
                ['lead:leads:create', 'lead:leads:editown', 'lead:leads:editother'],
                'MATCH_ONE'
            )
            ->willReturn(false);

        $leadModel->expects($this->never())
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
        $response = $controller->getOwnersAction($request);

        self::assertSame($viewResponse, $response);
    }

    #[DataProvider('provideViewOwnerLimits')]
    public function testGetOwnersLimit(mixed $limit, mixed $start, int $code, array $data): void
    {
        $filterData = 'filter data';
        [$security, $leadModel, $container, $controller] = $this->controllerGetOwners();

        $security->expects($this->once())
            ->method('isGranted')
            ->with(
                ['lead:leads:create', 'lead:leads:editown', 'lead:leads:editother'],
                'MATCH_ONE'
            )
            ->willReturn(true);

        if (Response::HTTP_OK !== $code) {
            $leadModel->expects($this->never())
                ->method('getLookupResults');
        } else {
            $leadModel->expects($this->once())
                ->method('getLookupResults')
                ->with('user', $filterData, $limit, $start)
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

        $request->query->set('start', $start);
        $request->query->set('filter', $filterData);

        $response = $controller->getOwnersAction($request);

        self::assertSame($viewResponse, $response);
    }

    public static function provideViewOwnerLimits(): \Generator
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

        yield 'Null start' => [null, null, Response::HTTP_BAD_REQUEST, $badRequest];
        yield 'String start' => [null, 'a', Response::HTTP_BAD_REQUEST, $badRequest];
        yield 'Negative start' => [null, '-1', Response::HTTP_BAD_REQUEST, $badRequest];
        yield 'String limit' => ['a', '10', Response::HTTP_BAD_REQUEST, $badRequest];
        yield 'Negative limit' => [-1, '0', Response::HTTP_BAD_REQUEST, $badRequest];
        yield 'Zero limit' => [0, '0', Response::HTTP_BAD_REQUEST, $badRequest];
        yield 'Ok' => [10, '0', Response::HTTP_OK, ['good', 'data']];
    }

    /**
     * @return array{0: CorePermissions&MockObject, 1: LeadModel&MockObject, ContainerInterface&MockObject, 2: LeadApiController}
     */
    private function controllerGetOwners(): array
    {
        $security = $this->createMock(CorePermissions::class);
        $leadModel = $this->createMock(LeadModel::class);
        $container = $this->createMock(ContainerInterface::class);

        $controller = new LeadApiController(
            $security,
            self::createStub(Translator::class),
            self::createStub(EntityResultHelper::class),
            self::createStub(RouterInterface::class),
            self::createStub(FormFactoryInterface::class),
            self::createStub(DoNotContactModel::class),
            self::createStub(AppVersion::class),
            self::createStub(ContactMerger::class),
            self::createStub(UserHelper::class),
            self::createStub(IpLookupHelper::class),
            self::createStub(RequestStack::class),
            self::createStub(ManagerRegistry::class),
            self::createStub(ModelFactory::class),
            self::createStub(EventDispatcherInterface::class),
            self::createStub(CoreParametersHelper::class),
            self::createStub(CampaignModel::class),
            self::createStub(FieldModel::class),
            $leadModel,
            self::createStub(StageModel::class),
            self::createStub(UserModel::class),
            self::createStub(DeviceModel::class),
            self::createStub(NoteModel::class),
        );

        $controller->setContainer($container);

        return [
            $security,
            $leadModel,
            $container,
            $controller,
        ];
    }
}
