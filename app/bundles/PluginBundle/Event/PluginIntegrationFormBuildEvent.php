<?php

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;
use Symfony\Component\Form\FormBuilderInterface;

class PluginIntegrationFormBuildEvent extends AbstractPluginIntegrationEvent
{
    public function __construct(
        UnifiedIntegrationInterface $integration,
        private readonly FormBuilderInterface $builder,
        private readonly array $options,
    ) {
        $this->integration = $integration;
    }

    public function getFormBuilder(): FormBuilderInterface
    {
        return $this->builder;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
