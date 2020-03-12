<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Loader\EnvVars;

use Symfony\Component\HttpFoundation\ParameterBag;

class ElFinderEnvVars implements EnvVarsInterface
{
    public static function load(ParameterBag $config, ParameterBag $defaultConfig, ParameterBag $envVars): void
    {
        $root = rtrim($defaultConfig->get('local_root'), '/') ?: '%kernel.root_dir%/..';

        $relativeImageFolderPath = trim($config->get('image_path'), '/');
        $absoluteImageFolderPath = $root.'/'.$relativeImageFolderPath;
        $envVars->set('MAUTIC_EL_FINDER_PATH', $absoluteImageFolderPath);

        $url = rtrim($config->get('site_url'), '/').'/'.$relativeImageFolderPath;
        $envVars->set('MAUTIC_EL_FINDER_URL', $url);
    }
}
