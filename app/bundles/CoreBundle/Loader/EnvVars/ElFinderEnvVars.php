<?php

namespace Mautic\CoreBundle\Loader\EnvVars;

use Symfony\Component\HttpFoundation\ParameterBag;

class ElFinderEnvVars implements EnvVarsInterface
{
    public static function load(ParameterBag $config, ParameterBag $defaultConfig, ParameterBag $envVars): void
    {
        $root = rtrim((string) $defaultConfig->get('local_root'), '/') ?: '%kernel.project_dir%';

        $relativeImageFolderPath = trim((string) $config->get('image_path'), '/');
        $absoluteImageFolderPath = $root.'/'.$relativeImageFolderPath;
        $envVars->set('MAUTIC_EL_FINDER_PATH', $absoluteImageFolderPath);

        $url = rtrim((string) $config->get('site_url'), '/').'/'.$relativeImageFolderPath;
        $envVars->set('MAUTIC_EL_FINDER_URL', $url);
    }
}
