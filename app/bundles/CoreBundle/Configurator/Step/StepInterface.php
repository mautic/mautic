<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Configurator\Step;

interface StepInterface
{
    /**
     * Returns the form used for configuration.
     *
     * @return string
     */
    public function getFormType();

    /**
     * Checks for requirements.
     */
    public function checkRequirements(): array;

    /**
     * Checks for optional settings.
     */
    public function checkOptionalSettings(): array;

    /**
     * Returns the template to be rendered for this step.
     *
     * @return string
     */
    public function getTemplate();

    /**
     * Updates form data parameters.
     */
    public function update(self $data): array;
}
