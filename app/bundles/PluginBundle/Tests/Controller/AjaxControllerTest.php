<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\MauticModelInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\PluginBundle\Controller\AjaxController;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\PluginBundle\Model\PluginModel;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class AjaxControllerTest extends TestCase
{
    public function testGetIntegrationFieldsAcceptsPostedPayload(): void
    {
        $settings = [
            'prefix' => 'integration_details[featureSettings][leadFields]',
            'object' => 'lead',
        ];
        $request = Request::create(
            '/s/ajax?action=plugin:getIntegrationFields&integration=QueryOnly&settings[prefix]=integration_details[featureSettings][leadFields]&page=1',
            Request::METHOD_POST,
            [
                'integration' => 'Salesforce',
                'settings'    => [
                    'object' => 'lead',
                ],
                'page'        => '3',
            ]
        );
        $request->setSession(new Session(new MockArraySessionStorage()));

        $integrationSettings = new Integration();
        $integrationSettings->setFeatureSettings([]);

        // @phpstan-ignore-next-line Test coverage intentionally exercises the legacy PluginBundle integration path.
        $integration = $this->createMock(AbstractIntegration::class);
        $integration->expects($this->once())
            ->method('getFormLeadFields')
            ->with($settings)
            ->willReturn([
                'Email' => [
                    'label' => 'Email',
                    'type'  => 'email',
                ],
            ]);
        $integration->expects($this->once())
            ->method('getIntegrationSettings')
            ->willReturn($integrationSettings);
        $integration->expects($this->once())
            ->method('getDataPriority')
            ->willReturn(true);

        $helper = $this->createMock(IntegrationHelper::class);
        $helper->expects($this->once())
            ->method('getIntegrationObject')
            ->with('Salesforce')
            ->willReturn($integration);

        $pluginModel = $this->createMock(PluginModel::class);
        $pluginModel->expects($this->once())
            ->method('getLeadFields')
            ->willReturn([
                'email' => [
                    'label' => 'Email',
                    'type'  => 'email',
                ],
            ]);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')
            ->with('default_pagelimit')
            ->willReturn(30);

        $form = $this->createStub(FormInterface::class);
        $form->method('getName')->willReturn('integration_fields');
        $form->method('createView')->willReturn(new FormView());

        $controller = new TestableAjaxController(
            $pluginModel,
            $form,
            $coreParametersHelper,
            $this->createStub(ManagerRegistry::class),
            $this->createStub(ModelFactory::class),
            $this->createStub(UserHelper::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(Translator::class),
            $this->createStub(FlashBag::class),
            $this->createStub(RequestStack::class),
            $this->createStub(CorePermissions::class),
        );

        $response = $controller->getIntegrationFieldsAction($request, $helper);
        $data     = json_decode((string) $response->getContent(), true);

        Assert::assertSame(1, $data['success']);
        Assert::assertSame('rendered field mapping form', $data['html']);
        Assert::assertSame('3', $request->getSession()->get('mautic.plugin.Salesforce.lead.page'));
        Assert::assertSame('Salesforce', $controller->formOptions['integration']);
        Assert::assertSame('3', $controller->formOptions['page']);
        Assert::assertSame(30, $controller->formOptions['limit']);
    }
}

final class TestableAjaxController extends AjaxController
{
    /** @var array<string, mixed> */
    public array $formOptions = [];

    public function __construct(
        private MauticModelInterface $pluginModel,
        private FormInterface $form,
        CoreParametersHelper $coreParametersHelper,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,
        CorePermissions $security,
    ) {
        parent::__construct(
            $doctrine,
            $modelFactory,
            $userHelper,
            $coreParametersHelper,
            $dispatcher,
            $translator,
            $flashBag,
            $requestStack,
            $security,
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function createForm(string $type, mixed $data = null, array $options = []): FormInterface
    {
        $this->formOptions = $options;

        return $this->form;
    }

    /**
     * @param array<string, mixed> $dataArray
     */
    protected function sendJsonResponse($dataArray, $statusCode = null, $addIgnoreWdt = true): JsonResponse
    {
        return new JsonResponse($dataArray, $statusCode ?? 200);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        return new Response('rendered field mapping form');
    }

    protected function getModel($modelNameKey): MauticModelInterface
    {
        Assert::assertSame('plugin', $modelNameKey);

        return $this->pluginModel;
    }
}
