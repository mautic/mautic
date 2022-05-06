<?php

namespace Mautic\CoreBundle\Configurator\Step;

/**
 * StepInterface.
 *
 * @author Marc Weistroff <marc.weistroff@sensio.com>
 */
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
     *
     * @return array
     */
    public function checkRequirements();

    /**
     * Checks for optional settings.
     *
     * @return array
     */
    public function checkOptionalSettings();

    /**
     * Returns the template to be rendered for this step.
     *
     * @return string
     */
    public function getTemplate();

    /**
     * Updates form data parameters.
     *
     * @return array
     */
    public function update(StepInterface $data);
}
