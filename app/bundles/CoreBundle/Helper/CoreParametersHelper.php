<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class CoreParametersHelper.
 */
class CoreParametersHelper
{
    /**
     * @var \MauticParameterImporter
     */
    private $parameters;

    /**
     * CoreParametersHelper constructor.
     */
    public function __construct(string $root)
    {
        $this->rootPath = $root;

        /** @var array $paths */
        include $root.'/config/paths_helper.php';
        $this->parameters = new \MauticParameterImporter($paths['local_config'], $paths);
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getParameter($name, $default = null)
    {
        if ('db_table_prefix' === $name && defined('MAUTIC_TABLE_PREFIX')) {
            //use the constant in case in the installer
            return MAUTIC_TABLE_PREFIX;
        }

        return $this->parameters->has($name) ? $this->parameters->get($name) : $default;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasParameter($name)
    {
        return $this->parameters->has($name);
    }
}
