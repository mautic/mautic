<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CampaignBundle\Controller\SourceController;
use Mautic\CampaignBundle\Form\Type\CampaignLeadSourceType;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SourceControllerUnitTest extends TestCase
{
    public function testNewActionBuildsBooleanModifiedSourceMap(): void
    {
        $formChild = $this->createMock(FormInterface::class);
        $formChild->expects($this->once())
            ->method('getData')
            ->willReturn(['12']);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('offsetGet')
            ->with('lists')
            ->willReturn($formChild);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects($this->once())
            ->method('create')
            ->with(
                CampaignLeadSourceType::class,
                ['sourceType' => 'lists'],
                [
                    'action'         => '/campaign/source/new',
                    'source_choices' => [12 => 'Segment 12'],
                ]
            )
            ->willReturn($form);

        $campaignModel = $this->createMock(CampaignModel::class);
        $campaignModel->expects($this->once())
            ->method('getSourceLists')
            ->with('lists')
            ->willReturn([12 => 'Segment 12']);

        $controller = $this->createController($formFactory, $campaignModel);
        $request    = $this->ajaxRequest('POST', [
            'submit'              => '1',
            'campaign_leadsource' => [
                'sourceType' => 'lists',
            ],
        ]);

        $response = $controller->newAction($request, 1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame(1, $payload['success']);
        $this->assertSame(['lists' => [12 => true]], $payload['modifiedSources']);
        $this->assertSame('lists', $payload['sourceType']);
        $this->assertSame(1, $payload['closeModal']);
    }

    public function testEditActionBuildsBooleanModifiedSourceMapFromSubmittedIds(): void
    {
        $formChild = $this->createMock(FormInterface::class);
        $formChild->expects($this->once())
            ->method('getData')
            ->willReturn(['12', '15']);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('offsetGet')
            ->with('lists')
            ->willReturn($formChild);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects($this->once())
            ->method('create')
            ->with(
                CampaignLeadSourceType::class,
                ['sourceType' => 'lists'],
                [
                    'action'         => '/campaign/source/edit',
                    'source_choices' => [12 => 'Segment 12', 15 => 'Segment 15'],
                ]
            )
            ->willReturn($form);

        $campaignModel = $this->createMock(CampaignModel::class);
        $campaignModel->expects($this->once())
            ->method('getSourceLists')
            ->with('lists')
            ->willReturn([12 => 'Segment 12', 15 => 'Segment 15']);

        $controller = $this->createController($formFactory, $campaignModel);
        $request    = $this->ajaxRequest('POST', [
            'submit'              => '1',
            'modifiedSources'     => json_encode(['lists' => [99 => true]], JSON_THROW_ON_ERROR),
            'campaign_leadsource' => [
                'sourceType' => 'lists',
            ],
        ]);

        $response = $controller->editAction($request, 1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame(1, $payload['success']);
        $this->assertSame(['lists' => [12 => true, 15 => true]], $payload['modifiedSources']);
        $this->assertSame('lists', $payload['sourceType']);
        $this->assertSame(1, $payload['closeModal']);
    }

    public function testDeleteActionRemovesSubmittedSourceTypeFromModifiedSources(): void
    {
        $controller = $this->createController(
            $this->createMock(FormFactoryInterface::class),
            $this->createMock(CampaignModel::class)
        );

        $request = $this->ajaxRequest('POST', [
            'sourceType'      => 'lists',
            'modifiedSources' => json_encode([
                'lists' => [12 => true],
                'forms' => [5 => true],
            ], JSON_THROW_ON_ERROR),
        ]);

        $response = $controller->deleteAction($request, 1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame(1, $payload['success']);
        $this->assertSame(1, $payload['deleted']);
        $this->assertSame('lists', $payload['sourceType']);
        $this->assertSame(['forms' => [5 => true]], $payload['modifiedSources']);
    }

    public function testDeleteActionWithNonPostRequestReturnsUnsuccessfulResponse(): void
    {
        $controller = $this->createController(
            $this->createMock(FormFactoryInterface::class),
            $this->createMock(CampaignModel::class)
        );

        $request = $this->ajaxRequest('GET', [
            'sourceType'      => 'lists',
            'modifiedSources' => json_encode(['lists' => [12 => true]], JSON_THROW_ON_ERROR),
        ]);

        $response = $controller->deleteAction($request, 1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame(['success' => 0], $payload);
    }

    /**
     * @return SourceController&MockObject
     */
    private function createController(FormFactoryInterface $formFactory, CampaignModel $campaignModel): SourceController
    {
        /** @var SourceController&MockObject $controller */
        $controller = $this->getMockBuilder(SourceController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getModel', 'generateUrl', 'isFormCancelled', 'isFormValid', 'renderView'])
            ->getMock();

        $controller->method('getModel')
            ->with('campaign')
            ->willReturn($campaignModel);
        $controller->method('isFormCancelled')
            ->willReturn(false);
        $controller->method('isFormValid')
            ->willReturn(true);
        $controller->method('renderView')
            ->willReturn('<div>rendered</div>');
        $controller->method('generateUrl')
            ->willReturnCallback(static function (string $route, array $parameters = []): string {
                if ('new' === ($parameters['objectAction'] ?? null)) {
                    return '/campaign/source/new';
                }

                if ('edit' === ($parameters['objectAction'] ?? null)) {
                    return '/campaign/source/edit';
                }

                return '/campaign/source';
            });

        $security = $this->createMock(CorePermissions::class);
        $security->method('isGranted')
            ->willReturn(true);

        $this->setProperty($controller, AbstractStandardFormController::class, 'formFactory', $formFactory);
        $this->setProperty($controller, CommonController::class, 'security', $security);

        return $controller;
    }

    /**
     * @param array<string, mixed> $requestData
     */
    private function ajaxRequest(string $method, array $requestData): Request
    {
        $request = new Request([], $requestData, [], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $request->setMethod($method);

        return $request;
    }

    private function setProperty(object $object, string $className, string $propertyName, mixed $value): void
    {
        $reflectionProperty = new \ReflectionProperty($className, $propertyName);
        $reflectionProperty->setValue($object, $value);
    }
}
