<?php

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Loader\ParameterLoader;
use Mautic\UserBundle\Entity\User;

/**
 * Class PathsHelper.
 */
class PathsHelper
{
    /**
     * @var array
     */
    private $paths;

    /**
     * @var string
     */
    private $theme;

    /**
     * @var string
     */
    private $imagePath;

    /**
     * @var string
     */
    private $dashboardImportDir;

    /**
     * @var string
     */
    private $dashboardUserImportDir;

    /**
     * @var string
     */
    private $kernelCacheDir;

    /**
     * @var string
     */
    private $kernelLogsDir;

    /**
     * @var string
     */
    private $kernelRootDir;

    /**
     * @var mixed
     */
    private $temporaryDir;

    /**
     * @var User
     */
    private $user;

    /**
     * PathsHelper constructor.
     */
    public function __construct(UserHelper $userHelper, CoreParametersHelper $coreParametersHelper, string $cacheDir, string $logsDir, string $rootDir)
    {
        $this->user                   = $userHelper->getUser();
        $this->theme                  = $coreParametersHelper->get('theme');
        $this->imagePath              = $this->removeTrailingSlash($coreParametersHelper->get('image_path'));
        $this->dashboardImportDir     = $this->removeTrailingSlash($coreParametersHelper->get('dashboard_import_dir'));
        $this->temporaryDir           = $this->removeTrailingSlash($coreParametersHelper->get('tmp_path'));
        $this->dashboardUserImportDir = $this->removeTrailingSlash($coreParametersHelper->get('dashboard_import_user_dir'));
        $this->kernelCacheDir         = $this->removeTrailingSlash($cacheDir);
        $this->kernelLogsDir          = $this->removeTrailingSlash($logsDir);
        $this->kernelRootDir          = $this->removeTrailingSlash($rootDir);

        $root  = $rootDir;
        $paths = [];
        include $root.'/config/paths_helper.php';

        $this->paths = $paths;
    }

    public function getLocalConfigurationFile(): string
    {
        return ParameterLoader::getLocalConfigFile($this->kernelRootDir);
    }

    public function getCachePath(): string
    {
        return $this->getSystemPath('cache', true);
    }

    public function getRootPath(): string
    {
        return $this->getSystemPath('root', true);
    }

    public function getTemporaryPath(): string
    {
        return $this->getSystemPath('tmp', true);
    }

    public function getLogsPath(): string
    {
        return $this->getSystemPath('logs', true);
    }

    public function getImagePath(): string
    {
        return $this->getSystemPath('images', true);
    }

    public function getTranslationsPath(): string
    {
        return $this->getSystemPath('translations', true);
    }

    public function getThemesPath(): string
    {
        return $this->getSystemPath('themes', true);
    }

    public function getAssetsPath(): string
    {
        return $this->getSystemPath('assets', true);
    }

    public function getCoreBundlesPath(): string
    {
        return $this->getSystemPath('bundles', true);
    }

    public function getPluginsPath(): string
    {
        return $this->getSystemPath('plugins', true);
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
                return $this->kernelCacheDir;
            case 'logs':
                return $this->kernelLogsDir;
            case 'temporary':
            case 'tmp':
                if (!is_dir($this->temporaryDir) && !file_exists($this->temporaryDir) && is_writable($this->temporaryDir)) {
                    mkdir($this->temporaryDir, 0777, true);
                }

                return $this->temporaryDir;
            case 'images':
                $path = $this->imagePath;
                break;

            case 'dashboard.user':
            case 'dashboard.global':
                //these are absolute regardless as they are configurable
                $globalPath = $this->dashboardImportDir;

                if ('dashboard.global' == $name) {
                    return $globalPath;
                }

                if (!$userPath = $this->dashboardUserImportDir) {
                    $userPath = $globalPath;
                }

                $userPath .= '/'.$this->user->getId();

                if (!is_dir($userPath) && !file_exists($userPath) && is_writable($userPath)) {
                    mkdir($userPath);
                }

                return $userPath;

            default:
                if (isset($this->paths[$name])) {
                    $path = $this->paths[$name];
                } elseif (false !== strpos($name, '_root')) {
                    // Assume system root if one is not set specifically
                    $path = $this->paths['root'];
                } else {
                    throw new \InvalidArgumentException("$name does not exist.");
                }
        }

        if (!$fullPath) {
            return $path;
        }

        $rootPath = (!empty($this->paths[$name.'_root'])) ? $this->paths[$name.'_root'] : $this->paths['root'];
        if (false === strpos($path, $rootPath)) {
            return $rootPath.'/'.$path;
        }

        return $path;
    }

    private function removeTrailingSlash(?string $dir): ?string
    {
        if ('/' === substr($dir, -1)) {
            $dir = substr($dir, 0, -1);
        }

        return $dir;
    }
}
