<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Configurator\Step;

use Symfony\Component\Form\FormTypeInterface;

/**
 * StepInterface.
 *
 * @author      Marc Weistroff <marc.weistroff@sensio.com>
 * @deprecated  To be removed in 2.0, implement Mautic\CoreBundle\Configurator\Step\StepInterface instead
 */
interface StepInterface
{
    /**
     * Returns the form used for configuration.
     *
     * @return FormTypeInterface
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
     * @param StepInterface $data
     *
     * @return array
     */
    public function update(StepInterface $data);
}
