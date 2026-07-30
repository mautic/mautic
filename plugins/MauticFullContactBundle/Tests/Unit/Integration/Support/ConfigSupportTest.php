<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFullContactBundle\Tests\Unit\Integration\Support;

use MauticPlugin\MauticFullContactBundle\Form\Type\FullContactKeysType;
use MauticPlugin\MauticFullContactBundle\Integration\Support\ConfigSupport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class ConfigSupportTest extends TestCase
{
    private ConfigSupport $configSupport;

    protected function setUp(): void
    {
        parent::setUp();

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')
            ->with('mautic_plugin_fullcontact_index', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://mautic.example.com/fullcontact/callback');

        $this->configSupport = new ConfigSupport($router);
    }

    public function testGetAuthConfigFormNameReturnsFullContactKeysType(): void
    {
        $this->assertSame(FullContactKeysType::class, $this->configSupport->getAuthConfigFormName());
    }

    public function testGetConfigFormContentTemplateReturnsExpectedTemplate(): void
    {
        $this->assertSame('@MauticFullContact/Integration/config_form.html.twig', $this->configSupport->getConfigFormContentTemplate());
    }

    public function testGetWebhookUrlReturnsAbsoluteCallbackUrl(): void
    {
        $this->assertSame('https://mautic.example.com/fullcontact/callback', $this->configSupport->getWebhookUrl());
    }
}
