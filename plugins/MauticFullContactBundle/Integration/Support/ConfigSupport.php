<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFullContactBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\MauticFullContactBundle\Form\Type\FullContactKeysType;
use MauticPlugin\MauticFullContactBundle\Integration\FullContactIntegration;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class ConfigSupport extends FullContactIntegration implements ConfigFormInterface, ConfigFormAuthInterface
{
    use DefaultConfigFormTrait;

    public function __construct(
        private RouterInterface $router,
    ) {
    }

    public function getAuthConfigFormName(): string
    {
        return FullContactKeysType::class;
    }

    public function getConfigFormContentTemplate(): string
    {
        return '@MauticFullContact/Integration/config_form.html.twig';
    }

    public function getWebhookUrl(): string
    {
        return $this->router->generate('mautic_plugin_fullcontact_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
