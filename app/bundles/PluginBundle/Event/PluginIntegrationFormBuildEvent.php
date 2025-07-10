<?php

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;
use Symfony\Component\Form\FormBuilderInterface;

class PluginIntegrationFormBuildEvent extends AbstractPluginIntegrationEvent
{
    public function __construct(
        UnifiedIntegrationInterface $integration,
        private FormBuilderInterface $builder,
        private array $options,
    ) {
        $this->integration = $integration;
    }

    /**
     * @return FormBuilderInterface
     */
    public function getFormBuilder()
    {
        return $this->builder;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
