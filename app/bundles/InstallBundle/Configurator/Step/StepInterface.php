<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Based on Sensio\DistributionBundle
 */

namespace Mautic\InstallBundle\Configurator\Step;

use Symfony\Component\Form\Type\FormTypeInterface;

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
     * Checks for optional setting it could be nice to have.
     *
     * @return array
     */
    public function checkOptionalSettings();

    /**
     * Returns the template to be renderer for this step.
     *
     * @return string
     */
    public function getTemplate();

    /**
     * Updates form data parameters.
     *
     * @param  StepInterface $data
     * @return array
     */
    public function update(StepInterface $data);
}
