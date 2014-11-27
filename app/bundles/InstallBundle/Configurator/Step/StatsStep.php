<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Configurator\Step;

use Mautic\InstallBundle\Configurator\Form\StatsStepType;

/**
 * Stats Step.
 */
class StatsStep implements StepInterface
{
    /**
     * Send server stats?
     */
    public $send_server_data = true;

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return new StatsStepType();
    }

    /**
     * {@inheritdoc}
     */
    public function checkRequirements()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function checkOptionalSettings()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'MauticInstallBundle:Install:stats.html.php';
    }

    /**
     * {@inheritdoc}
     */
    public function update(StepInterface $data)
    {
        $parameters = array();

        foreach ($data as $key => $value) {
            $parameters[$key] = $value;
        }

        return $parameters;
    }
}
