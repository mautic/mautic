<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Integration\Interfaces;

interface ConfigFormInterface extends IntegrationInterface
{
    public function getDisplayName(): string;

    /**
     * Return the name/class of the form type to override the default or just return NULL to use the default.
     *
     * @return string|null Name of the form type service
     */
    public function getConfigFormName(): ?string;

    /**
     * Return the template to use from the controller. Return null to use the default.
     *
     * @return string|null Name of the template like SomethingBundle:Config:form.html.twig
     */
    public function getConfigFormContentTemplate(): ?string;
}
