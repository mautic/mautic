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
     * Absolute path to cache directory
     *
     * @var string
     */
    public $cache_path = '%kernel.root_dir%/cache';

    /**
     * Absolute path to log directory
     *
     * @var string
     */
    public $log_path = '%kernel.root_dir%/logs';

    /**
     * Set the domain URL for use in getting the absolute URL for cli/cronjob generated URLs
     *
     * @var string
     */
    public $site_url;

    /**
     * Set the update stability (used when non-stable packages are installed)
     *
     * @var string
     */
    public $update_stability = 'stable';

    /**
     * @var
     */
    private $request_url;

    public function __construct($url)
    {
        $this->request_url = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return new MiscStepType($this->request_url);
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
            // Exclude backup params from the config
            if ($key != 'request_url') {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }
}