<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\AssetGenerationHelper;
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
     * @var AssetGenerationHelper
     */
    protected $assetHelper;

    /**
     * @var array
     */
    protected $assets;

    /**
     * Gets asset prefix
     *
     * @param bool $forceEndingSlash
     *
     * @return string
     */
    public function getAssetPrefix($includeEndingSlash = false)
    {
        $prefix = $this->factory->getSystemPath('asset_prefix');
        if (!empty($prefix)) {
            if ($includeEndingSlash && substr($prefix, -1) != '/') {
                $prefix .= '/';
            } elseif (!$includeEndingSlash && substr($prefix, -1) == '/') {
                $prefix = substr($prefix, 0, -1);
            }
        }

        return $prefix;
    }

    /**
     * Set asset url path
     *
     * @param string $path
     * @param null   $packageName
     * @param null   $version
     *
     * @return string
     */
    public function getUrl($path, $packageName = null, $version = null, $absolute = false)
    {
        // if we have http in the url it is absolute and we can just return it
        if (strpos($path, 'http') === 0) {
            return $path;
        }

        // otherwise build the complete path
        $assetPrefix = $this->getAssetPrefix(strpos($path, '/') !== 0);
        $path        = $assetPrefix . $path;

        $url = parent::getUrl($path, $packageName, $version);

        if ($absolute) {
            $url = $this->factory->getRequest()->getSchemeAndHttpHost() . $url;
        }

        return $url;
    }

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
                $assets['headDeclarations'][] = array('script' => $s);
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
            $this->assets['headDeclarations'][] = array('declaration' => $script);
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

    /*
     * Loads an addon script
     *
     * @param $assetFilepath the path to the file location. Can use full path or relative to mautic web root
     */

    public function includeScript($assetFilePath)
    {
        return  '<script type="text/javascript">Mautic.loadScript(\'' . $this->getUrl($assetFilePath) . '\');</script>';
    }

    /*
     * Include stylesheet
     *
     * @param $assetFilepath the path to the file location. Can use full path or relative to mautic web root
     */

    public function includeStylesheet($assetFilePath)
    {
        return  '<script type="text/javascript">Mautic.loadStylesheet(\'' . $this->getUrl($assetFilePath) . '\');</script>';
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
            $this->assets['headDeclarations'][] = array('custom' => $declaration);
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

            foreach (array_reverse($this->assets['stylesheets']) as $s) {
                echo '<link rel="stylesheet" href="' . $this->getUrl($s) . '" />' . "\n";
            }
        }

        if (isset($this->assets['styleDeclarations'])) {
            echo "<style>\n";
            foreach (array_reverse($this->assets['styleDeclarations']) as $d) {
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
            foreach (array_reverse($this->assets['scripts'][$location]) as $s) {
                echo '<script src="'.$this->getUrl($s).'"></script>'."\n";
            }
        }

        if (isset($this->assets['scriptDeclarations'][$location])) {
            echo "<script>\n";
            foreach (array_reverse($this->assets['scriptDeclarations'][$location]) as $d) {
                echo "$d\n";
            }
            echo "</script>\n";
        }

        if (isset($this->assets['customDeclarations'][$location])) {
            foreach (array_reverse($this->assets['customDeclarations'][$location]) as $d) {
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
        $headOutput = '';
        if (!empty($this->assets['headDeclarations'])) {
            $scriptOpen = false;
            foreach ($this->assets['headDeclarations'] as $declaration) {
                $type   = key($declaration);
                $output = $declaration[$type];

                switch ($type) {
                    case 'script':
                        if ($scriptOpen) {
                            $headOutput .= "\n</script>";
                            $scriptOpen = false;
                        }
                        $headOutput .= "\n".'<script src="' . $this->getUrl($output) . '"></script>';
                        break;
                    case 'custom':
                    case 'declaration':
                        if ($type == 'custom' && $scriptOpen) {
                            $headOutput .= "\n</script>";
                            $scriptOpen = false;
                        } elseif ($type == 'declaration' && !$scriptOpen) {
                            $headOutput .= "\n<script>";
                            $scriptOpen = true;
                        }
                        $headOutput .= "\n$output";
                        break;

                }
            }
            if ($scriptOpen) {
                $headOutput .= "\n</script>\n\n";
            }
        }
        echo $headOutput;
    }

    /**
     * Output system stylesheets
     *
     * @return void
     */
    public function outputSystemStylesheets()
    {
        $assets = $this->assetHelper->getAssets();

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
    public function outputSystemScripts($includeEditor = false)
    {
        $assets = $this->assetHelper->getAssets();

        if ($includeEditor) {
            $assets['js'][] = 'app/bundles/CoreBundle/Assets/js/libraries/ckeditor/ckeditor.js';
            $assets['js'][] = 'app/bundles/CoreBundle/Assets/js/libraries/ckeditor/adapters/jquery.js';
        }

        if (isset($assets['js'])) {
            foreach ($assets['js'] as $url) {
                echo '<script src="' . $this->getUrl($url) . '"></script>' . "\n";
            }
        }
    }

    /**
     * Fetch system scripts
     *
     * @param bool $render If true, a string will be returned of rendered script for header
     *
     * @return array|string
     */
    public function getSystemScripts($render = false, $includeEditor = false)
    {
        $assets = $this->assetHelper->getAssets();

        if ($includeEditor) {
            $assets['js'][] = 'app/bundles/CoreBundle/Assets/js/libraries/ckeditor/ckeditor.js';
            $assets['js'][] = 'app/bundles/CoreBundle/Assets/js/libraries/ckeditor/adapters/jquery.js';
        }

        if ($render) {
            $js = '';
            if (isset($assets['js'])) {
                foreach ($assets['js'] as $url) {
                    $js .= '<script src="' . $this->getUrl($url) . '"></script>' . "\n";
                }
            }
            return $js;
        }

        return $assets['js'];
    }

    /**
     * Turn all URLs in clickable links.
     *
     * @param string $text
     * @param array  $protocols  http/https, ftp, mail, twitter
     * @param array  $attributes
     * @param string $mode       normal or all
     * @return string
     */
    public function makeLinks($text, $protocols = array('http', 'mail'), array $attributes = array())
    {
        if (strnatcmp(phpversion(),'4.0.5') >= 0)
        {
            // Link attributes
            $attr = '';
            foreach ($attributes as $key => $val) {
                $attr = ' ' . $key . '="' . htmlentities($val) . '"';
            }

            $links = array();

            // Extract existing links and tags
            $text = preg_replace_callback('~(<a .*?>.*?</a>|<.*?>)~i', function ($match) use (&$links) {
                return '<' . array_push($links, $match[1]) . '>';
            }, $text);

            // Extract text links for each protocol
            foreach ((array)$protocols as $protocol) {
                switch ($protocol) {
                    case 'http':
                    case 'https':
                        $text = preg_replace_callback('~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) {
                            if ($match[1]) $protocol = $match[1];
                            $link = $match[2] ?: $match[3];
                            return '<' . array_push($links, "<a $attr href=\"$protocol://$link\">$link</a>") . '>';
                        }, $text);
                    break;
                    case 'mail':
                        $text = preg_replace_callback('~([^\s<]+?@[^\s<]+?\.[^\s<]+)(?<![\.,:])~', function ($match) use (&$links, $attr) {
                            return '<' . array_push($links, "<a $attr href=\"mailto:{$match[1]}\">{$match[1]}</a>") . '>';
                        }, $text);
                    break;
                    case 'twitter':
                        $text = preg_replace_callback('~(?<!\w)[@#](\w++)~', function ($match) use (&$links, $attr) {
                            return '<' . array_push($links, "<a $attr href=\"https://twitter.com/" . ($match[0][0] == '@' ? '' : 'search/%23') . $match[1]  . "\">{$match[0]}</a>") . '>';
                        }, $text);
                        break;
                    default:
                        $text = preg_replace_callback('~' . preg_quote($protocol, '~') . '://([^\s<]+?)(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) {
                            return '<' . array_push($links, "<a $attr href=\"$protocol://{$match[1]}\">{$match[1]}</a>") . '>';
                        }, $text);
                        break;
                }
            }

            // Insert all link
            return preg_replace_callback('/<(\d+)>/', function ($match) use (&$links) {
                return $links[$match[1] - 1];
            }, $text);
        } else {
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
    }

    /**
     * Returns only first $charCount chars of the $text and adds "..." if it is shortened.
     *
     * @param string $text
     * @param integer $charCount
     * @return string
     */
    public function shortenText($text, $charCount = null)
    {
        if ($charCount && strlen($text) > $charCount) {
            return substr($text, 0, $charCount) . '...';
        }

        return $text;
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
     * @param AssetGenerationHelper $helper
     */
    public function setAssetHelper(AssetGenerationHelper $helper)
    {
        $this->assetHelper = $helper;
    }

    /**
     * @param $country
     */
    public function getCountryFlag($country, $urlOnly = true, $class = '')
    {
        $flagPath = $this->factory->getSystemPath('assets', true) . '/images/flags/';
        $relpath  = $this->factory->getSystemPath('assets') . '/images/flags/';
        $country = ucwords(str_replace(' ', '-', $country));
        $flagImg = '';
        if (file_exists($flagPath . $country . '.png')) {
            if (file_exists($flagPath . $country . '.png')) {
                $flagImg = $this->getUrl($relpath . $country . '.png');
            }
        }

        if ($urlOnly) {
            return $flagImg;
        } else {
            return '<img src="' . $flagImg . '" class="'.$class.'" />';
        }

    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'assets';
    }
}
