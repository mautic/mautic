<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Class PathsHelper
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
    protected $kernelLogDir;

    /**
     * @var User
     */
    protected $user;

    /**
     * PathsHelper constructor.
     * @param array $paths
     * @param string $theme
     * @param string $imagePath
     * @param string $dashboardImportDir
     * @param string $dashboardImportUserDir
     * @param string $kernelCacheDir
     * @param string $kernelLogDir
     */
    public function __construct(
        $paths, 
        $theme,
        $imagePath, 
        $dashboardImportDir, 
        $dashboardImportUserDir,
        $kernelCacheDir,
        $kernelLogDir
    )
    {
        $this->paths = $paths;
        $this->theme = $theme;
        $this->imagePath = $imagePath;
        $this->dashboardImportDir = $dashboardImportDir;
        $this->dashboardUserImportDir = $dashboardImportUserDir;
        $this->kernelCacheDir = $kernelCacheDir;
        $this->kernelLogDir = $kernelLogDir;
    }

    /**
     * Initialize the user parameter
     *
     * @param SecurityContext $context
     */
    public function initUser(SecurityContext $context)
    {
        $user = $context->getToken()->getUser();

        if (! $user instanceof User) {
            $user = new User();
            $user->isGuest = true;
        }

        $this->user = $user;
    }

    /**
     * Get the path to specified area.  Returns relative by default with the exception of cache and log
     * which will be absolute regardless of $fullPath setting
     *
     * @param string $name
     * @param bool   $fullPath
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getSystemPath($name, $fullPath = false)
    {
        if ($name == 'currentTheme' || $name == 'current_theme') {
            $path = $this->paths['themes']."/".$this->theme;
        } elseif ($name == 'cache' || $name == 'log') {
            //these are absolute regardless as they are configurable
            return ($name === 'cache') ? $this->kernelCacheDir : $this->kernelLogDir;
        } elseif ($name == 'images') {
            $path = $this->imagePath;
            
            if (substr($path, -1) === '/') {
                $path = substr($path, 0, -1);
            }
        } elseif ($name == 'dashboard.user' || $name == 'dashboard.global') {
            //these are absolute regardless as they are configurable
            $globalPath = $this->dashboardImportDir;
            
            if (substr($globalPath, -1) === '/') {
                $globalPath = substr($globalPath, 0, -1);
            }

            if ($name == 'dashboard.global') {
                return $globalPath;
            }

            if (! $userPath = $this->dashboardUserImportDir) {
                $userPath = $globalPath;
            } elseif (substr($userPath, -1) === '/') {
                $userPath = substr($userPath, 0, -1);
            }

            $userPath .= '/'.$this->user->getId();

            // @todo check is_writable
            if (!is_dir($userPath) && !file_exists($userPath)) {
                mkdir($userPath, 0755);
            }

            return $userPath;
        } elseif (isset($this->paths[$name])) {
            $path = $this->paths[$name];
        } elseif (strpos($name, '_root') !== false) {
            // Assume system root if one is not set specifically
            $path = $this->paths['root'];
        } else {
            throw new \InvalidArgumentException("$name does not exist.");
        }

        if ($fullPath) {
            $rootPath = (!empty($this->paths[$name . '_root'])) ? $this->paths[$name . '_root'] : $this->paths['root'];

            return $rootPath . '/' . $path;
        }

        return $path;
    }
}
