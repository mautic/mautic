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
use Symfony\Component\Templating\Helper\CoreAssetsHelper;

/**
 * Class AssetsHelper
 */
class AssetsHelper extends CoreAssetsHelper
{

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @var array
     */
    protected $assets;

    /**
     * Adds a JS script to the template
     *
     * @param string $script
     * @param string $location
     *
     * @return void
     */
    public function addScript($script, $location = 'head')
    {
        $assets     =& $this->assets;
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
     *
     * @return void
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
     *
     * @return void
     */
    public function addStylesheet($stylesheet)
    {
        $assets   =& $this->assets;
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
     *
     * @return void
     */
    public function loadEditor()
    {
        static $editorLoaded;

        if (empty($editorLoaded)) {
            $editorLoaded = true;
            $this->addScript(array(
                'app/bundles/CoreBundle/Assets/js/libraries/ckeditor/ckeditor.js',
                'app/bundles/CoreBundle/Assets/js/libraries/ckeditor/adapters/jquery.js'
            ));
        }
    }

    /**
     * Add style tag to the header
     *
     * @param string $styles
     *
     * @return void
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
     * @param string $declaration
     * @param string $location
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
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

    /**
     * Output system stylesheets
     *
     * @return void
     */
    public function outputSystemStylesheets()
    {
        $assets = $this->getAssets();

        if (isset($assets['css'])) {
            foreach ($assets['css'] as $url) {
                echo '<link rel="stylesheet" href="' . $this->getUrl($url) . '" />' . "\n";
            }
        }
    }

    /**
     * Output system scripts
     *
     * @return void
     */
    public function outputSystemScripts()
    {
        $assets = $this->getAssets();

        if (isset($assets['js'])) {
            foreach ($assets['js'] as $url) {
                echo '<script src="' . $this->getUrl($url) . '"></script>' . "\n";
            }
        }
    }

    /**
     * Fetch system scripts
     *
     * @return array
     */
    public function getSystemScripts()
    {
        $assets = $this->getAssets();

        return $assets['js'];
    }

    /**
     * Generate assets
     *
     * @return array
     */
    private function getAssets()
    {
        static $assets = array();

        if (empty($assets)) {

            $loadAll    = true;
            $env        = $this->factory->getEnvironment();
            $rootPath   = $this->factory->getSystemPath('root');
            $assetsPath = $this->factory->getSystemPath('assets');

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
                ini_set('max_execution_time', 300);

                $modifiedLast = array();

                //get a list of all core asset files
                $bundles = $this->factory->getParameter('bundles');

                $fileTypes = array('css', 'js');
                foreach ($bundles as $bundle) {
                    foreach ($fileTypes as $ft) {
                        if (!isset($modifiedLast[$ft])) {
                            $modifiedLast[$ft] = array();
                        }
                        $dir = "{$bundle['directory']}/Assets/$ft";
                        if (file_exists($dir)) {
                            $modifiedLast[$ft] = array_merge($modifiedLast[$ft], $this->findAssets($dir, $ft, $env, $assets, $bundle));
                        }
                    }
                }
                $modifiedLast = array_merge($modifiedLast, $this->findOverrides($env, $assets));

                //combine the files into their corresponding name and put in the root media folder
                if ($env == "prod") {
                    $checkPaths = array(
                        $assetsFullPath,
                        "$assetsFullPath/css",
                        "$assetsFullPath/js",
                    );
                    array_walk($checkPaths, function ($path) {
                        if (!file_exists($path)) {
                            mkdir($path);
                        }
                    });

                    foreach ($assets as $type => $groups) {
                        foreach ($groups as $group => $files) {
                            $assetFile = "$assetsFullPath/$type/$group.$type";

                            //only refresh if a change has occurred
                            $modified = (!file_exists($assetFile)) ? true : filemtime($assetFile) < $modifiedLast[$type][$group];
                            if ($modified) {
                                if (file_exists($assetFile)) {
                                    //delete it
                                    unlink($assetFile);
                                }

                                if ($type == 'css') {
                                    $out = fopen($assetFile, 'w');

                                    foreach ($files as $relPath => $details) {
                                        $content = \Minify::combine(array($relPath), array(
                                            'rewriteCssUris'  => false,
                                            'minifierOptions' => array(
                                                'text/css' => array(
                                                    'currentDir' => '',
                                                    'prependRelativePath' => '../../' . dirname($relPath) . '/'
                                                )
                                            )
                                        ));
                                        fwrite($out, $content);
                                    }

                                    fclose($out);
                                } else {
                                    file_put_contents($assetFile, \Minify::combine(array_keys($files)));
                                }
                            }
                        }
                    }
                }
            }

            if ($env == 'prod') {
                //return prod generated assets
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
            } else {
                foreach ($assets as $type => &$typeAssets) {
                    $typeAssets = array_keys($typeAssets);
                }
            }
        }

        return $assets;
    }

    /**
     * Finds directory assets
     *
     * @param string $dir
     * @param string $ext
     * @param string $env
     * @param array  $assets
     * @param string $bundle
     *
     * @return array
     */
    protected function findAssets($dir, $ext, $env, &$assets, $bundle)
    {
        $rootPath    = $this->factory->getSystemPath('root') . '/';
        $directories = new Finder();
        $directories->directories()->exclude('*less')->depth('0')->ignoreDotFiles(true)->in($dir);

        $modifiedLast = array();

        if (count($directories)) {
            foreach ($directories as $directory) {
                $files         = new Finder();
                $thisDirectory = str_replace('\\', '/', $directory->getRealPath());
                $files->files()->depth('0')->name('*.' . $ext)->in($thisDirectory)->sortByName();
                $group = $directory->getBasename();
                foreach ($files as $file) {
                    $fullPath = $file->getPathname();
                    $relPath  = str_replace($rootPath, '', $file->getPathname());
                    if (strpos($relPath, '/') === 0) {
                        $relPath = substr($relPath, 1);
                    }

                    $details = array(
                        'fullPath'  => $fullPath,
                        'relPath'   => $relPath
                    );

                    if ($env == 'prod') {
                        $lastModified = filemtime($fullPath);
                        if (!isset($modifiedLast[$group]) || $lastModified > $modifiedLast[$group]) {
                            $modifiedLast[$group] = $lastModified;
                        }
                        $assets[$ext][$group][$relPath] = $details;
                    } else {
                        $assets[$ext][$relPath] = $details;
                    }
                }
                unset($files);
            }
        }

        unset($directories);
        $files = new Finder();
        $files->files()->depth('0')->ignoreDotFiles(true)->name('*.' . $ext)->in($dir)->sortByName();
        foreach ($files as $file) {
            $fullPath = $file->getPathname();
            $relPath  = str_replace($rootPath, '', $fullPath);

            $details = array(
                'fullPath'  => $fullPath,
                'relPath'   => $relPath
            );

            if ($env == 'prod') {
                $lastModified = filemtime($fullPath);
                if (!isset($modifiedLast['app']) || $lastModified > $modifiedLast['app']) {
                    $modifiedLast['app'] = $lastModified;
                }
                $assets[$ext]['app'][$relPath] = $details;
            } else {
                $assets[$ext][$relPath] = $details;
            }
        }
        unset($files);

        return $modifiedLast;
    }

    /**
     * Find asset overrides in the template
     *
     * @param $env
     * @param $assets
     */
    protected function findOverrides ($env, &$assets)
    {
        $rootPath      = $this->factory->getSystemPath('root');
        $currentTheme  = $this->factory->getSystemPath('currentTheme');
        $modifiedLast  = array();
        $types         = array('css', 'js');
        $overrideFiles = array(
            "libraries" => "libraries_custom",
            "app"       => "app_custom"
        );

        foreach ($types as $ext) {
            foreach ($overrideFiles as $group => $of) {
                if (file_exists("$rootPath/$currentTheme/$ext/$of.$ext")) {
                    $fullPath = "$rootPath/$currentTheme/$ext/$of.$ext";
                    $relPath  = "$currentTheme/$ext/$of.$ext";

                    $details = array(
                        'fullPath'  => $fullPath,
                        'relPath'   => $relPath
                    );

                    if ($env == 'prod') {
                        $lastModified = filemtime($fullPath);
                        if (!isset($modifiedLast[$ext][$group]) || $lastModified > $modifiedLast[$ext][$group]) {
                            $modifiedLast[$ext][$group] = $lastModified;
                        }
                        $assets[$ext][$group][$relPath] = $details;
                    } else {
                        $assets[$ext][$relPath] = $details;
                    }
                }
            }
        }

        return $modifiedLast;
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public function makeLinks($text)
    {
        return preg_replace(
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

    /**
     * @param MauticFactory $factory
     *
     * @return void
     */
    public function setFactory(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'assets';
    }
}
