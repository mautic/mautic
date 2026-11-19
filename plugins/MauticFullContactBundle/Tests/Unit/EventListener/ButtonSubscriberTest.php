<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFullContactBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\Event\CustomButtonEvent;
use Mautic\CoreBundle\Twig\Helper\ButtonHelper;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticFullContactBundle\EventListener\ButtonSubscriber;
use MauticPlugin\MauticFullContactBundle\Integration\FullContactIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ButtonSubscriberTest extends TestCase
{
    public function testNoButtonsWithoutSettingsRow(): void
    {
        $integrationsHelper = $this->createStub(IntegrationsHelper::class);
        $integrationsHelper->method('getIntegration')->willThrowException(new IntegrationNotFoundException());

        $event = $this->createContactListEvent();
        $this->createSubscriber($integrationsHelper)->injectViewButtons($event);

        $this->assertSame([], $event->getButtons());
    }

    public function testNoButtonsWhenUnpublished(): void
    {
        $event = $this->createContactListEvent();
        $this->createSubscriber($this->createIntegrationsHelper(false))->injectViewButtons($event);

        $this->assertSame([], $event->getButtons());
    }

    public function testContactBulkLookupButtonWhenPublished(): void
    {
        $event = $this->createContactListEvent();
        $this->createSubscriber($this->createIntegrationsHelper(true))->injectViewButtons($event);

        $buttons = $event->getButtons();
        $this->assertCount(1, $buttons);
        $this->assertSame('mautic.plugin.fullcontact.button.caption', array_values($buttons)[0]['btnText']);
    }

    private function createIntegrationsHelper(bool $isPublished): IntegrationsHelper
    {
        $settings = new Integration();
        $settings->setIsPublished($isPublished);

        $integration = new FullContactIntegration();
        $integration->setIntegrationConfiguration($settings);

        $integrationsHelper = $this->createStub(IntegrationsHelper::class);
        $integrationsHelper->method('getIntegration')->willReturn($integration);

        return $integrationsHelper;
    }

    private function createSubscriber(IntegrationsHelper $integrationsHelper): ButtonSubscriber
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/s/fullcontact/batchLookupPerson');

        return new ButtonSubscriber($integrationsHelper, $translator, $router);
    }

    private function createContactListEvent(): CustomButtonEvent
    {
        return new CustomButtonEvent(ButtonHelper::LOCATION_BULK_ACTIONS, new Request(attributes: ['_route' => 'mautic_contact_index']));
    }
}
