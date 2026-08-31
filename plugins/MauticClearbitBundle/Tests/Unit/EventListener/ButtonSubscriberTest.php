<?php

declare(strict_types=1);

namespace MauticPlugin\MauticClearbitBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomButtonEvent;
use Mautic\CoreBundle\Twig\Helper\ButtonHelper;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticClearbitBundle\EventListener\ButtonSubscriber;
use MauticPlugin\MauticClearbitBundle\Integration\ClearbitIntegration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ButtonSubscriberTest extends TestCase
{
    private MockObject&IntegrationsHelper $integrationsHelper;

    private MockObject&TranslatorInterface $translator;

    private MockObject&RouterInterface $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->integrationsHelper = $this->createMock(IntegrationsHelper::class);
        $this->translator         = $this->createMock(TranslatorInterface::class);
        $this->router             = $this->createMock(RouterInterface::class);

        $this->translator->method('trans')->willReturnArgument(0);
        $this->router->method('generate')->willReturn('/some/url');
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS => ['injectViewButtons', 0]],
            ButtonSubscriber::getSubscribedEvents()
        );
    }

    public function testInjectViewButtonsDoesNothingWhenIntegrationNotFound(): void
    {
        $this->integrationsHelper->method('getIntegration')->with('Clearbit')
            ->willThrowException(new IntegrationNotFoundException());

        $event = $this->makeEvent(ButtonHelper::LOCATION_BULK_ACTIONS, 'mautic_contact_index');

        $this->getSubscriber()->injectViewButtons($event);

        $this->assertSame([], $event->getButtons());
    }

    public function testInjectViewButtonsDoesNothingWhenIntegrationNotPublished(): void
    {
        $this->integrationsHelper->method('getIntegration')->with('Clearbit')
            ->willReturn($this->makeIntegration(false));

        $event = $this->makeEvent(ButtonHelper::LOCATION_BULK_ACTIONS, 'mautic_contact_index');

        $this->getSubscriber()->injectViewButtons($event);

        $this->assertSame([], $event->getButtons());
    }

    public function testInjectViewButtonsAddsBulkActionButtonForContactRoute(): void
    {
        $this->integrationsHelper->method('getIntegration')->with('Clearbit')
            ->willReturn($this->makeIntegration(true));

        $event = $this->makeEvent(ButtonHelper::LOCATION_BULK_ACTIONS, 'mautic_contact_index');

        $this->getSubscriber()->injectViewButtons($event);

        $this->assertCount(1, $event->getButtons());
    }

    public function testInjectViewButtonsAddsPageActionButtonForContactViewWithItem(): void
    {
        $this->integrationsHelper->method('getIntegration')->with('Clearbit')
            ->willReturn($this->makeIntegration(true));

        $lead = $this->createMock(Lead::class);
        $lead->method('getEmail')->willReturn('john@example.com');
        $lead->method('getId')->willReturn(42);

        $event = $this->makeEvent(
            ButtonHelper::LOCATION_PAGE_ACTIONS,
            'mautic_contact_action',
            ['objectAction' => 'view'],
            $lead
        );

        $this->getSubscriber()->injectViewButtons($event);

        $this->assertCount(1, $event->getButtons());
    }

    public function testInjectViewButtonsAddsBulkActionButtonForCompanyRoute(): void
    {
        $this->integrationsHelper->method('getIntegration')->with('Clearbit')
            ->willReturn($this->makeIntegration(true));

        $event = $this->makeEvent(ButtonHelper::LOCATION_BULK_ACTIONS, 'mautic_company_index');

        $this->getSubscriber()->injectViewButtons($event);

        $this->assertCount(1, $event->getButtons());
    }

    public function testInjectViewButtonsAddsNothingForUnrelatedRoute(): void
    {
        $this->integrationsHelper->method('getIntegration')->with('Clearbit')
            ->willReturn($this->makeIntegration(true));

        $event = $this->makeEvent(ButtonHelper::LOCATION_BULK_ACTIONS, 'mautic_email_index');

        $this->getSubscriber()->injectViewButtons($event);

        $this->assertSame([], $event->getButtons());
    }

    private function getSubscriber(): ButtonSubscriber
    {
        return new ButtonSubscriber($this->integrationsHelper, $this->translator, $this->router);
    }

    private function makeIntegration(bool $isPublished): ClearbitIntegration
    {
        $configuration = new Integration();
        $configuration->setIsPublished($isPublished);

        $integration = new ClearbitIntegration();
        $integration->setIntegrationConfiguration($configuration);

        return $integration;
    }

    /**
     * @param array<string, mixed> $routeParams
     */
    private function makeEvent(string $location, string $route, array $routeParams = [], mixed $item = null): CustomButtonEvent
    {
        $request = new Request();
        $request->attributes->set('_route', $route);
        $request->attributes->set('_route_params', $routeParams);

        return new CustomButtonEvent($location, $request, [], $item);
    }
}
