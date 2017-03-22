<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Middleware;

trait ConfigAwareTrait
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @return array
     */
    public function getConfig()
    {
        // Include paths
        $root = realpath(__DIR__.'/..');

        /** @var array $paths */
        include $root.'/config/paths.php';

        $localParameters = [];
        $localConfig     = str_replace('%kernel.root_dir%', $root, $paths['local_config']);

        if (file_exists($localConfig)) {
            /** @var $parameters */
            include $localConfig;

            $localParameters = $parameters;
        }

        //check for parameter overrides
        if (file_exists($root.'/config/parameters_local.php')) {
            include $root.'/config/parameters_local.php';
            $localParameters = array_merge($localParameters, $parameters);
        }

        return $localParameters;
    }
}
