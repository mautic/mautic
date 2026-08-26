<?php

declare(strict_types=1);

namespace MauticPlugin\MauticClearbitBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\MauticClearbitBundle\Form\Type\ClearbitKeysType;
use MauticPlugin\MauticClearbitBundle\Integration\ClearbitIntegration;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class ConfigSupport extends ClearbitIntegration implements ConfigFormInterface, ConfigFormAuthInterface
{
    use DefaultConfigFormTrait;

    public function __construct(
        private readonly RouterInterface $router,
    ) {
    }

    public function getAuthConfigFormName(): string
    {
        return ClearbitKeysType::class;
    }

    public function getConfigFormContentTemplate(): string
    {
        return '@MauticClearbit/Integration/config_form.html.twig';
    }

    public function getWebhookUrl(): string
    {
        return $this->router->generate('mautic_plugin_clearbit_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
