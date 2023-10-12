<?php

namespace Mautic\Middleware;

use Mautic\CoreBundle\Loader\ParameterLoader;

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
        $root          = realpath(__DIR__.'/..');
        $configBaseDir = ParameterLoader::getLocalConfigBaseDir($root);
        $projectRoot   = ParameterLoader::getProjectDirByRoot($root);

        /** @var array $paths */
        include $root.'/config/paths.php';

        $localParameters = [];

        $localConfig = ParameterLoader::getLocalConfigFile($root, false);

        if (file_exists($localConfig)) {
            /** @var $parameters */
            include $localConfig;

            $localParameters = $parameters;
        }

        // check for parameter overrides
        if (file_exists($configBaseDir.'/config/parameters_local.php')) {
            include $configBaseDir.'/config/parameters_local.php';
            $localParameters = array_merge($localParameters, $parameters);
        }

        return $localParameters;
    }
}
