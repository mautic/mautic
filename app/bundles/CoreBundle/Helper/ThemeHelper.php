<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;


use Mautic\CoreBundle\Exception as MauticException;
use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Filesystem\Filesystem;

class ThemeHelper
{

    private $factory;
    private $themes;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;

        $this->themes = $this->factory->getInstalledThemes();

    }

    private function getDirectoryName($newName)
    {
        return InputHelper::alphanum($newName, true);
    }

    /**
     * @param $theme
     * @param $newName
     *
     * @throws MauticException\FileExistsException
     * @throws MauticException\FileNotFoundException
     */
    public function copy($theme, $newName)
    {
        $root      = $this->factory->getSystemPath('themes_root') . '/';
        $themes    = $this->factory->getInstalledThemes();

        //check to make sure the theme exists
        if (!isset($themes[$theme])) {
            throw new MauticException\FileNotFoundException($theme . ' not found!');
        }

        $dirName = $this->getDirectoryName($newName);

        $fs = new Filesystem();

        if ($fs->exists($root . $dirName)) {
            throw new MauticException\FileExistsException("$dirName already exists");
        }

        $fs->mirror($root . $theme, $root . $dirName);

        $this->updateConfig($root . $dirName, $newName);
    }

    /**
     * @param $theme
     * @param $newName
     *
     * @throws FileN
     * @throws MauticException\FileExistsException
     */
    public function rename ($theme, $newName)
    {
        $root      = $this->factory->getSystemPath('themes_root') . '/';
        $themes    = $this->factory->getInstalledThemes();

        //check to make sure the theme exists
        if (!isset($themes[$theme])) {
            throw new FileN($theme . ' not found!');
        }

        $dirName = $this->getDirectoryName($newName);

        $fs = new Filesystem();

        if ($fs->exists($root . $dirName)) {
            throw new MauticException\FileExistsException("$dirName already exists");
        }

        $fs->rename($root . $theme, $root . $dirName);

        $this->updateConfig($root . $theme, $dirName);
    }

    /**
     * @param $theme
     *
     * @throws MauticException\FileNotFoundException
     */
    public function delete($theme)
    {
        $root      = $this->factory->getSystemPath('themes_root') . '/';
        $themes    = $this->factory->getInstalledThemes();

        //check to make sure the theme exists
        if (!isset($themes[$theme])) {
            throw new MauticException\FileNotFoundException($theme . ' not found!');
        }

        $fs = new Filesystem();
        $fs->remove($root . $theme);
    }

    /**
     * @param $themePath
     * @param $newName
     */
    private function updateConfig($themePath, $newName)
    {
        $config = include $themePath . '/config.php';

        $docblock = '';
        $configRaw = file_get_contents($themePath . '/config.php');
        if (preg_match_all('/@(\w+)\s+(.*)\r?\n/m', $configRaw, $matches)){
            $docblock = array_combine($matches[1], $matches[2]);
        }

        $config['name'] = $newName;
        $updatedConfig = $this->renderConfig($config, $docblock);
        file_put_contents($themePath . '/config.php', $updatedConfig);
    }

    /**
     * Fetches the optional settings from the defined steps.
     *
     * @return array
     */
    public function getOptionalSettings()
    {
        $minors = array();
        foreach ($this->steps as $step) {
            foreach ($step->checkOptionalSettings() as $minor) {
                $minors[] = $minor;
            }
        }

        return $minors;
    }

    /**
     * @param $config
     *
     * @param $config
     *
     * @return string
     */
    public function renderConfig($config)
    {
        $string = "<?php\n";

        if (!empty($docblock)) {
            $string .= "$docblock\n\n";
        }

        $string .= "\$config = array(\n";

        foreach ($config as $key => $value) {
            if ($value !== '') {
                if (is_string($value)) {
                    $value = "'$value'";
                } elseif (is_bool($value)) {
                    $value = ($value) ? 'true' : 'false';
                } elseif (is_null($value)) {
                    $value = 'null';
                } elseif (is_array($value)) {
                    $value = $this->renderArray($value);
                }

                $string .= "\t'$key' => $value,\n";
            }
        }

        $string .= ");\n";

        $string .= 'return $config;\n';

        return $string;
    }

    /**
     * @param     $array
     * @param int $level
     *
     * @return string
     */
    protected function renderArray($array, $level = 1)
    {
        $string = "array(\n";

        $count = $counter = count($array);
        foreach ($array as $key => $value) {
            if (is_string($key)) {
                if ($counter === $count) {
                    $string .= str_repeat("\t", $level + 1);
                }
                $string .= '"'.$key.'" => ';
            }

            if (is_array($value)) {
                $string .= $this->renderArray($value, $level + 1);
            } else {
                $string .= '"'.addslashes($value).'"';
            }

            $counter--;
            if ($counter > 0) {
                $string .= ", \n" . str_repeat("\t", $level + 1);
            }
        }
        $string .= "\n" . str_repeat("\t", $level) . ")";

        return $string;
    }
}