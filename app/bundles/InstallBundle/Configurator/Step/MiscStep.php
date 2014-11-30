<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Configurator\Step;

use Mautic\InstallBundle\Configurator\Form\MiscStepType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Misc Step.
 */
class MiscStep implements StepInterface
{
    /**
     * Send server stats?
     */
    public $send_server_data = true;

    /**
     * Absolute path to cache directory
     *
     * @var string
     * @Assert\NotBlank(message = "mautic.install.notblank")
     */
    public $cache_path = '%kernel.root_dir%/cache';

    /**
     * Absolute path to log directory
     *
     * @var string
     * @Assert\NotBlank(message = "mautic.install.notblank")
     */
    public $log_path   = '%kernel.root_dir%/logs';

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return new MiscStepType();
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
        return 'MauticInstallBundle:Install:misc.html.php';
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
