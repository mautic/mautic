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
    private $kernelRoot;

    /**
     * Constructor
     *
     * @param array   $parameters       Existing parameters in local configuration
     * @param boolean $configIsWritable Flag if the configuration file is writable
     * @param string  $kernelRoot       Kernel root path
     */
    public function __construct(array $parameters, $configIsWritable, $kernelRoot)
    {
        $this->configIsWritable = $configIsWritable;
        $this->kernelRoot       = $kernelRoot;
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

        if (version_compare(PHP_VERSION, '5.3.7', '<')) {
            $messages[] = 'mautic.install.minimum.php.version';
        }

        if (version_compare(PHP_VERSION, '5.3.16', '==')) {
            $messages[] = 'mautic.install.buggy.php.version';
        }

        if (!is_dir(dirname($this->kernelRoot) . '/vendor/composer')) {
            $messages[] = 'mautic.install.composer.dependencies';
        }

        if (!$this->configIsWritable) {
            $messages[] = 'mautic.install.config.unwritable';
        }

        if (!is_writable($this->kernelRoot . '/cache')) {
            $messages[] = 'mautic.install.cache.unwritable';
        }

        if (!is_writable($this->kernelRoot . '/logs')) {
            $messages[] = 'mautic.install.logs.unwritable';
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
