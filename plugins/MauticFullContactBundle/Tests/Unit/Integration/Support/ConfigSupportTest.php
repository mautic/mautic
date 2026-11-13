<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFullContactBundle\Tests\Unit\Integration\Support;

use MauticPlugin\MauticFullContactBundle\Form\Type\FullContactKeysType;
use MauticPlugin\MauticFullContactBundle\Integration\Support\ConfigSupport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

final class ConfigSupportTest extends TestCase
{
    private ConfigSupport $configSupport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configSupport = new ConfigSupport($this->createStub(RouterInterface::class));
    }

    public function testGetAuthConfigFormNameReturnsFullContactKeysType(): void
    {
        $this->assertSame(FullContactKeysType::class, $this->configSupport->getAuthConfigFormName());
    }

    public function testGetConfigFormContentTemplateReturnsExpectedTemplate(): void
    {
        $this->assertSame('@MauticFullContact/Integration/config_form.html.twig', $this->configSupport->getConfigFormContentTemplate());
    }
}
