<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Configurator\Step;

use Mautic\InstallBundle\Configurator\Form\CheckStepType;

/**
 * Check Step.
 */
class CheckStep implements StepInterface
{
    private $configIsWritable;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $parameters, $configIsWritable)
    {
        $this->configIsWritable = $configIsWritable;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return new CheckStepType();
    }

    /**
     * {@inheritdoc}
     */
    public function checkRequirements()
    {
        $messages = array();

        if (!$this->configIsWritable) {
            $messages[] = 'mautic.install.config.unwritable';
        }

        return $messages;
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
        return 'MauticInstallBundle:Step:check.html.php';
    }

    /**
     * {@inheritdoc}
     */
    public function update(StepInterface $data)
    {
        return array();
    }
}
