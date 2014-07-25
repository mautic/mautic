<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Finder\Finder;
use \Symfony\Component\Templating\Helper\CoreAssetsHelper as BaseAssetsHelper;

class AssetsHelper extends BaseAssetsHelper
{

    protected $factory;

    public function outputSystemStylesheets()
    {
        $assets = $this->getAssets();

        if (isset($assets['css'])) {
            foreach ($assets['css'] as $url) {
                echo '<link rel="stylesheet" href="' . $this->getUrl($url) . '" />' . "\n";
            }
        }
    }

    public function outputSystemScripts()
    {
        $assets = $this->getAssets();

        if (isset($assets['js'])) {
            foreach ($assets['js'] as $url) {
                echo '<script src="'.$this->getUrl($url).'"></script>'."\n";
            }
        }
    }

    public function getSystemScripts()
    {
        $assets = $this->getAssets();
        return $assets['js'];
    }

    private function getAssets()
    {
        static $assets = array();

        if (empty($assets)) {

            $loadAll        = true;
            $env            = $this->factory->getEnvironment();
            $rootPath       = $this->factory->getSystemPath('root');
            $assetsPath     = $this->factory->getSystemPath('assets');

            $assetsFullPath = "$rootPath/$assetsPath";
            if ($env == 'prod') {
                $loadAll = false; //by default, loading should not be required

                //check for libraries and app files and generate them if they don't exist if in prod environment
                $prodFiles = array(
                    "css/libraries.css",
                    "css/app.css",
                    "js/libraries.js",
                    "js/app.js"
                );

                foreach ($prodFiles as $file) {
                    if (!file_exists("$assetsFullPath/$file")) {
                        $loadAll = true; //it's missing so compile it
                        break;
                    }
                }
            }

            if ($loadAll) {
                //get a list of all core asset files
                $bundles = $this->factory->getParameter('bundles');
                foreach ($bundles as $bundle) {
                    $css = "{$bundle['directory']}/Assets/css";
                    if (file_exists($css)) {
                        $this->findAssets($css, 'css', $env, $assets);
                    }


                    $js = "{$bundle['directory']}/Assets/js";
                    if (file_exists($js)) {
                        $this->findAssets($js, 'js', $env, $assets);
                    }
                }
                $this->findOverrides($env, $assets);

                if ($env == "prod") {
                    //combine the files into their corresponding name
                    foreach ($assets as $type => $groups) {
                        foreach ($groups as $group => $files) {
                            $assetFile = "$assetsFullPath/$type/$group.$type";

                            if (file_exists($assetFile)) {
                                //delete it
                                unlink($assetFile);
                            }

                            $out = fopen($assetFile, "w");
                            foreach ($files as $file) {
                                fwrite($out, file_get_contents("$rootPath/$file"));
                            }
                            fclose($out);
                            unset($out);
                        }
                    }
                }
            }

            if ($env == 'prod') {
                $assets = array(
                    'css' => array(
                        "{$assetsPath}/css/libraries.css",
                        "{$assetsPath}/css/app.css"
                    ),
                    'js'  => array(
                        "{$assetsPath}/js/libraries.js",
                        "{$assetsPath}/js/app.js"
                    )
                );
            }
        }
        return $assets;
    }

    protected function findAssets($dir, $ext, $env, &$assets)
    {
        $rootPath     = $this->factory->getSystemPath('root') . '/';
        $directories  = new Finder();
        $directories->directories()->exclude('*less')->depth('0')->ignoreDotFiles(true)->in($dir);

        if (count($directories)) {
            foreach ($directories as $directory) {
                $files = new Finder();
                $files->files()->depth('0')->name('*.'.$ext)->in($directory->getRealPath())->sortByName();
                $key = $directory->getBasename();

                foreach ($files as $file) {
                    if ($env == 'prod') {
                        $assets[$ext][$key][] = str_replace($rootPath, '', $file->getPathname());
                    } else {
                        $assets[$ext][] = str_replace($rootPath, '', $file->getPathname());
                    }
                }
            }
        }

        $files = new Finder();
        $files->files()->depth('0')->ignoreDotFiles(true)->name('*.'.$ext)->in($dir)->sortByName();
        foreach ($files as $file) {
            if ($env == 'prod') {
                $assets[$ext]['app'][] = str_replace($rootPath, '', $file->getPathname());
            } else {
                $assets[$ext][] = str_replace($rootPath, '', $file->getPathname());
            }
        }
    }

    protected function findOverrides($env, &$assets)
    {
        $rootPath     = $this->factory->getSystemPath('root');
        $currentTheme = $this->factory->getSystemPath('currentTheme');

        $types         = array('css', 'js');
        $overrideFiles = array(
            "libraries" => "libraries_custom",
            "app"       => "app_custom"
        );

        foreach ($types as $ext) {
            foreach ($overrideFiles as $group => $of) {
                if (file_exists("$rootPath/$currentTheme/$ext/$of.$ext")) {
                    if ($env == 'prod') {
                        $assets[$ext][$group][] = "$currentTheme/$ext/$of.$ext";
                    } else {
                        $assets[$ext][] = "$currentTheme/$ext/$of.$ext";
                    }
                }
            }
        }
    }

    public function setFactory(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'assets';
    }
}