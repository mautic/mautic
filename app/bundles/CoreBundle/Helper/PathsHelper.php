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

use Mautic\UserBundle\Entity\User;

/**
 * Class PathsHelper.
 */
class PathsHelper
{
    /**
     * @var array
     */
    protected $paths;

    /**
     * @var string
     */
    protected $theme;

    /**
     * @var string
     */
    protected $imagePath;

    /**
     * @var string
     */
    protected $assetPath;

    /**
     * @var string
     */
    protected $dashboardImportDir;

    /**
     * @var string
     */
    protected $dashboardUserImportDir;

    /**
     * @var string
     */
    protected $kernelCacheDir;

    /**
     * @var string
     */
    protected $kernelLogsDir;

    /**
     * @var mixed
     */
    protected $temporaryDir;

    /**
     * @var User
     */
    protected $user;

    /**
     * PathsHelper constructor.
     *
     * @param CoreParametersHelper
     * @param UserHelper $userHelper
     */
    public function __construct(UserHelper $userHelper, CoreParametersHelper $coreParametersHelper)
    {
        $this->user                   = $userHelper->getUser();
        $this->paths                  = $coreParametersHelper->getParameter('paths');
        $this->theme                  = $coreParametersHelper->getParameter('theme');
        $this->imagePath              = $this->removeTrailingSlash($coreParametersHelper->getParameter('image_path'));
        $this->dashboardImportDir     = $this->removeTrailingSlash($coreParametersHelper->getParameter('dashboard_import_dir'));
        $this->temporaryDir           = $this->removeTrailingSlash($coreParametersHelper->getParameter('tmp_path'));
        $this->dashboardImportUserDir = $this->removeTrailingSlash($coreParametersHelper->getParameter('dashboard_import_user_dir'));
        $this->kernelCacheDir         = $this->removeTrailingSlash($coreParametersHelper->getParameter('kernel.cache_dir'));
        $this->kernelLogsDir          = $this->removeTrailingSlash($coreParametersHelper->getParameter('kernel.logs_dir'));
    }

    /**
     * Get the path to specified area.  Returns relative by default with the exception of cache and log
     * which will be absolute regardless of $fullPath setting.
     *
     * @param string $name
     * @param bool   $fullPath
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function getSystemPath($name, $fullPath = false)
    {
        switch ($name) {
            case 'currentTheme':
            case 'current_theme':
                $path = $this->paths['themes'].'/'.$this->theme;
                break;

            case 'cache':
            case 'logs':
            case 'temporary':
            case 'tmp':
                //these are absolute regardless as they are configurable
                if ('cache' === $name) {
                    return $this->kernelCacheDir;
                } elseif ('logs' === $name) {
                    return $this->kernelLogsDir;
                } else {
                    if (!is_dir($this->temporaryDir) && !file_exists($this->temporaryDir)) {
                        mkdir($this->temporaryDir, 0755);
                    }

                    return $this->temporaryDir;
                }

            case 'images':
                $path = $this->imagePath;
                break;

            case 'dashboard.user':
            case 'dashboard.global':
                //these are absolute regardless as they are configurable
                $globalPath = $this->dashboardImportDir;

                if ($name == 'dashboard.global') {
                    return $globalPath;
                }

                if (!$userPath = $this->dashboardUserImportDir) {
                    $userPath = $globalPath;
                }

                $userPath .= '/'.$this->user->getId();

                // @todo check is_writable
                if (!is_dir($userPath) && !file_exists($userPath)) {
                    mkdir($userPath, 0755);
                }

                return $userPath;

            default:
                if (isset($this->paths[$name])) {
                    $path = $this->paths[$name];
                } elseif (strpos($name, '_root') !== false) {
                    // Assume system root if one is not set specifically
                    $path = $this->paths['root'];
                } else {
                    throw new \InvalidArgumentException("$name does not exist.");
                }
        }

        if ($fullPath) {
            $rootPath = (!empty($this->paths[$name.'_root'])) ? $this->paths[$name.'_root'] : $this->paths['root'];

            return $rootPath.'/'.$path;
        }

        return $path;
    }

    /**
     * @param $dir
     *
     * @return string
     */
    private function removeTrailingSlash($dir)
    {
        if (substr($dir, -1) === '/') {
            $dir = substr($dir, 0, -1);
        }

        return $dir;
    }
}
