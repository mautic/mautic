<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\PluginBundle\Entity\Integration;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ConfigSaveEvent extends Event
{
    /** @param ?FormInterface<mixed> $form */
    public function __construct(private Integration $integrationConfiguration, private ?FormInterface $form)
    {
    }

    public function getIntegrationConfiguration(): Integration
    {
        return $this->integrationConfiguration;
    }

    public function getIntegration(): string
    {
        return $this->integrationConfiguration->getName();
    }

    /** @return  ?FormInterface<mixed> $form */
    public function getForm(): ?FormInterface
    {
        return $this->form;
    }
}
