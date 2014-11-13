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
use Symfony\Component\Templating\Helper\CoreAssetsHelper as BaseAssetsHelper;

class AssetsHelper extends BaseAssetsHelper
{

    protected $factory;

    protected $assets;

    /**
     * Adds a JS script to the template
     *
     * @param string $script
     * @param string $location
     */
    public function addScript($script, $location = 'head')
    {
        $assets =& $this->assets;
        $addScripts = function ($s) use ($location, &$assets) {
            if ($location == 'head') {
                //special place for these so that declarations and scripts can be mingled
                $assets['headDeclarations'][] = array(
                    'type' => 'script',
                    'src'  => $s
                );
            } else {
                if (!isset($assets['scripts'][$location])) {
                    $assets['scripts'][$location] = array();
                }

                if (!in_array($s, $assets['scripts'][$location])) {
                    $assets['scripts'][$location][] = $s;
                }
            }
        };

        if (is_array($script)) {
            foreach ($script as $s) {
                $addScripts($s);
            }
        } else {
            $addScripts($script);
        }
    }

    /**
     * Adds JS script declarations to the template
     *
     * @param string $script
     * @param string $location
     */
    public function addScriptDeclaration($script, $location = 'head')
    {
        if ($location == 'head') {
            //special place for these so that declarations and scripts can be mingled
            $this->assets['headDeclarations'][] = array(
                'type'   => 'declaration',
                'script' => $script
            );
        } else {
            if (!isset($this->assets['scriptDeclarations'][$location])) {
                $this->assets['scriptDeclarations'][$location] = array();
            }

            if (!in_array($script, $this->assets['scriptDeclarations'][$location])) {
                $this->assets['scriptDeclarations'][$location][] = $script;
            }
        }
    }

    /**
     * Adds a stylesheet to be loaded in the template header
     *
     * @param string $stylesheet
     */
    public function addStylesheet($stylesheet)
    {
        $assets =& $this->assets;
        $addSheet = function ($s) use (&$assets) {
            if (!isset($assets['stylesheets'])) {
                $assets['stylesheets'] = array();
            }

            if (!in_array($s, $assets['stylesheets'])) {
                $assets['stylesheets'][] = $s;
            }
        };

        if (is_array($stylesheet)) {
            foreach ($stylesheet as $s) {
                $addSheet($s);
            }
        } else {
            $addSheet($stylesheet);
        }
    }

    /**
     * Load ckeditor source files
     */
    public function loadEditor()
    {
        static $editorLoaded;

        if (empty($editorLoaded)) {
            $editorLoaded = true;
            $this->addScript(array(
                'media/js/ckeditor/ckeditor.js',
                'media/js/ckeditor/adapters/jquery.js'
            ));
        }
    }

    /**
     * Add style tag to the header
     *
     * @param $styles
     */
    public function addStyleDeclaration($styles)
    {
        if (!isset($this->assets['styleDeclarations'])) {
            $this->assets['styleDeclarations'] = array();
        }

        if (!in_array($styles, $this->assets['styleDeclarations'])) {
            $this->assets['styleDeclarations'][] = $styles;
        }
    }

    /**
     * Adds a custom declaration to <head />
     *
     * @param $declaration
     */
    public function addCustomDeclaration($declaration, $location = 'head')
    {
        if ($location == 'head') {
            $this->assets['headDeclarations'][] = array(
                'type'        => 'custom',
                'declaration' => $declaration
            );
        } else {
            if (!isset($this->assets['customDeclarations'][$location])) {
                $this->assets['customDeclarations'][$location] = array();
            }

            if (!in_array($declaration, $this->assets['customDeclarations'][$location])) {
                $this->assets['customDeclarations'][$location][] = $declaration;
            }
        }
    }

    /**
     * Outputs the stylesheets and style declarations
     */
    public function outputStyles()
    {
        if (isset($this->assets['stylesheets'])) {
            foreach ($this->assets['stylesheets'] as $s) {
                echo '<link rel="stylesheet" href="' . $this->getUrl($s) . '" />' . "\n";
            }
        }

        if (isset($this->assets['styleDeclarations'])) {
            echo "<style>\n";
            foreach ($this->assets['styleDeclarations'] as $d) {
                echo "$d\n";
            }
            echo "</style>\n";
        }
    }

    /**
     * Outputs the script files and declarations
     *
     * @param string $location
     */
    public function outputScripts($location)
    {
        if (isset($this->assets['scripts'][$location])) {
            foreach ($this->assets['scripts'][$location] as $s) {
                echo '<script src="'.$this->getUrl($s).'"></script>'."\n";
            }
        }

        if (isset($this->assets['scriptDeclarations'][$location])) {
            echo "<script>\n";
            foreach ($this->assets['scriptDeclarations'][$location] as $d) {
                echo "$d\n";
            }
            echo "</script>\n";
        }

        if (isset($this->assets['customDeclarations'][$location])) {
            foreach ($this->assets['customDeclarations'][$location] as $d) {
                echo "$d\n";
            }
        }
    }

    /**
     * Output head scripts, stylesheets, and custom declarations
     */
    public function outputHeadDeclarations()
    {
        $this->outputStyles();

        if (isset($this->assets['headDeclarations'])) {
            foreach ($this->assets['headDeclarations'] as $h) {
                if ($h['type'] == 'script') {
                    echo '<script src="'.$this->getUrl($h['src']).'"></script>'."\n";
                } elseif ($h['type'] == 'declaration') {
                    echo "<script>\n{$h['script']}\n</script>\n";
                } else {
                    echo $h['declaration'] . "\n";
                }
            }
        }
    }

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
                $thisDirectory = str_replace('\\', '/', $directory->getRealPath());
                $files->files()->depth('0')->name('*.'.$ext)->in($thisDirectory)->sortByName();
                $key = $directory->getBasename();
                foreach ($files as $file) {
                    $path = str_replace($rootPath, '', $file->getPathname());
                    if (strpos($path, '/') === 0) {
                        $path =  substr($path, 1);
                    }

                    if ($env == 'prod') {
                        $assets[$ext][$key][] = $path;
                    } else {
                        $assets[$ext][] = $path;
                    }
                }
                unset($files);
            }
        }

        unset($directories);
        $files = new Finder();
        $files->files()->depth('0')->ignoreDotFiles(true)->name('*.'.$ext)->in($dir)->sortByName();
        foreach ($files as $file) {
            if ($env == 'prod') {
                $assets[$ext]['app'][] = str_replace($rootPath, '', $file->getPathname());
            } else {
                $assets[$ext][] = str_replace($rootPath, '', $file->getPathname());
            }
        }
        unset($files);
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

    public function makeLinks($text)
    {
        return  preg_replace(
            array(
                '/(?(?=<a[^>]*>.+<\/a>)
                    (?:<a[^>]*>.+<\/a>)
                    |
                    ([^="\']?)((?:https?|ftp|bf2|):\/\/[^<> \n\r]+)
                 )/iex',
                '/<a([^>]*)target="?[^"\']+"?/i',
                '/<a([^>]+)>/i',
                '/(^|\s)(www.[^<> \n\r]+)/iex',
                '/(([_A-Za-z0-9-]+)(\\.[_A-Za-z0-9-]+)*@([A-Za-z0-9-]+)
                (\\.[A-Za-z0-9-]+)*)/iex'
            ),
            array(
                "stripslashes((strlen('\\2')>0?'\\1<a href=\"\\2\">\\2</a>\\3':'\\0'))",
                '<a\\1',
                '<a\\1 target="_blank">',
                "stripslashes((strlen('\\2')>0?'\\1<a href=\"http://\\2\">\\2</a>\\3':'\\0'))",
                "stripslashes((strlen('\\2')>0?'<a href=\"mailto:\\0\">\\0</a>':'\\0'))"
            ),
            $text
        );
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
