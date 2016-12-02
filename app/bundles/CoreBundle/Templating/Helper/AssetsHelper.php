<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\AssetGenerationHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Asset\Packages;

/**
 * Class AssetsHelper.
 */
class AssetsHelper
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
     * @var
     */
    protected $version;

    protected $packages;

    protected $coreParametersHelper;

    public function __construct(Packages $packages)
    {
        $this->packages = $packages;
    }

    public function setCharset()
    {
    }

    /**
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function setParamsHelper(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * Gets asset prefix.
     *
     * @param bool $includeEndingSlash
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
     * Set asset url path.
     *
     * @param string     $path
     * @param null       $packageName
     * @param null       $version
     * @param bool|false $absolute
     * @param bool|false $ignorePrefix
     *
     * @return string
     */
    public function getUrl($path, $packageName = null, $version = null)
    {
        // Dirty hack to work around strict notices with parent::getUrl
        $absolute = $ignorePrefix = false;
        if (func_num_args() > 3) {
            $args     = func_get_args();
            $absolute = $args[3];
            if (isset($args[4])) {
                $ignorePrefix = $args[4];
            }
        }

        // if we have http in the url it is absolute and we can just return it
        if (strpos($path, 'http') === 0) {
            return $path;
        }

        // otherwise build the complete path
        if (!$ignorePrefix) {
            $assetPrefix = $this->getAssetPrefix(strpos($path, '/') !== 0);
            $path        = $assetPrefix.$path;
        }

        $url = $this->packages->getUrl($path, $packageName, $version);

        if ($absolute) {
            $url = $this->getBaseUrl().$url;
        }

        return $url;
    }

    /**
     * Get base URL.
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->factory->getRequest()->getSchemeAndHttpHost();
    }

    /**
     * Adds a JS script to the template.
     *
     * @param string $script
     * @param string $location
     * @param bool   $async
     * @param string $name
     */
    public function addScript($script, $location = 'head', $async = false, $name = null)
    {
        $assets     = &$this->assets;
        $addScripts = function ($s) use ($location, &$assets, $async, $name) {
            $name = $name ?: 'script_'.hash('sha1', uniqid(mt_rand()));

            if ($location == 'head') {
                //special place for these so that declarations and scripts can be mingled
                $assets['headDeclarations'][$name] = ['script' => [$s, $async]];
            } else {
                if (!isset($assets['scripts'][$location])) {
                    $assets['scripts'][$location] = [];
                }

                if (!in_array($s, $assets['scripts'][$location])) {
                    $assets['scripts'][$location][$name] = [$s, $async];
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
     * Adds JS script declarations to the template.
     *
     * @param string $script
     * @param string $location
     */
    public function addScriptDeclaration($script, $location = 'head')
    {
        if ($location == 'head') {
            //special place for these so that declarations and scripts can be mingled
            $this->assets['headDeclarations'][] = ['declaration' => $script];
        } else {
            if (!isset($this->assets['scriptDeclarations'][$location])) {
                $this->assets['scriptDeclarations'][$location] = [];
            }

            if (!in_array($script, $this->assets['scriptDeclarations'][$location])) {
                $this->assets['scriptDeclarations'][$location][] = $script;
            }
        }
    }

    /**
     * Adds a stylesheet to be loaded in the template header.
     *
     * @param string $stylesheet
     */
    public function addStylesheet($stylesheet)
    {
        $assets   = &$this->assets;
        $addSheet = function ($s) use (&$assets) {
            if (!isset($assets['stylesheets'])) {
                $assets['stylesheets'] = [];
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

    /*
     * Loads an addon script
     *
     * @param $assetFilepath the path to the file location. Can use full path or relative to mautic web root
     * @param $onLoadCallback Mautic namespaced function to call for the script onload
     * @param $alreadyLoadedCallback Mautic namespaced function to call if the script has already been loaded
     */
    public function includeScript($assetFilePath, $onLoadCallback = '', $alreadyLoadedCallback = '')
    {
        return  '<script async="async" type="text/javascript" data-source="mautic">Mautic.loadScript(\''.$this->getUrl($assetFilePath)."', '$onLoadCallback', '$alreadyLoadedCallback');</script>";
    }

    /*
     * Include stylesheet
     *
     * @param $assetFilepath the path to the file location. Can use full path or relative to mautic web root
     */
    public function includeStylesheet($assetFilePath)
    {
        return  '<script async="async" type="text/javascript" data-source="mautic">Mautic.loadStylesheet(\''.$this->getUrl($assetFilePath).'\');</script>';
    }

    /**
     * Add style tag to the header.
     *
     * @param string $styles
     */
    public function addStyleDeclaration($styles)
    {
        if (!isset($this->assets['styleDeclarations'])) {
            $this->assets['styleDeclarations'] = [];
        }

        if (!in_array($styles, $this->assets['styleDeclarations'])) {
            $this->assets['styleDeclarations'][] = $styles;
        }
    }

    /**
     * Adds a custom declaration to <head />.
     *
     * @param string $declaration
     * @param string $location
     */
    public function addCustomDeclaration($declaration, $location = 'head')
    {
        if ($location == 'head') {
            $this->assets['headDeclarations'][] = ['custom' => $declaration];
        } else {
            if (!isset($this->assets['customDeclarations'][$location])) {
                $this->assets['customDeclarations'][$location] = [];
            }

            if (!in_array($declaration, $this->assets['customDeclarations'][$location])) {
                $this->assets['customDeclarations'][$location][] = $declaration;
            }
        }
    }

    /**
     * Outputs the stylesheets and style declarations.
     */
    public function outputStyles()
    {
        echo $this->getStyles();
    }

    /**
     * Outputs the stylesheets and style declarations.
     */
    public function getStyles()
    {
        $styles = '';

        if (isset($this->assets['stylesheets'])) {
            foreach (array_reverse($this->assets['stylesheets']) as $s) {
                $styles .= '<link rel="stylesheet" href="'.$this->getUrl($s).'" data-source="mautic" />'."\n";
            }
        }

        if (isset($this->assets['styleDeclarations'])) {
            $styles .= "<style data-source=\"mautic\">\n";
            foreach (array_reverse($this->assets['styleDeclarations']) as $d) {
                $styles .= "$d\n";
            }
            $styles .= "</style>\n";
        }

        return $styles;
    }

    /**
     * Outputs the script files and declarations.
     *
     * @param string $location
     */
    public function outputScripts($location)
    {
        if (isset($this->assets['scripts'][$location])) {
            foreach (array_reverse($this->assets['scripts'][$location]) as $s) {
                list($script, $async) = $s;
                echo '<script src="'.$this->getUrl($script).'"'.($async ? ' async' : '').' data-source="mautic"></script>'."\n";
            }
        }

        if (isset($this->assets['scriptDeclarations'][$location])) {
            echo "<script data-source=\"mautic\">\n";
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
     * Output head scripts, stylesheets, and custom declarations.
     */
    public function outputHeadDeclarations()
    {
        echo $this->getHeadDeclarations();
    }

    /**
     * Returns head scripts, stylesheets, and custom declarations.
     */
    public function getHeadDeclarations()
    {
        $headOutput = $this->getStyles();
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
                        list($script, $async) = $output;

                        $headOutput .= "\n".'<script src="'.$this->getUrl($script).'"'.($async ? ' async' : '').' data-source="mautic"></script>';
                        break;
                    case 'custom':
                    case 'declaration':
                        if ($type == 'custom' && $scriptOpen) {
                            $headOutput .= "\n</script>";
                            $scriptOpen = false;
                        } elseif ($type == 'declaration' && !$scriptOpen) {
                            $headOutput .= "\n<script data-source=\"mautic\">";
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

        return $headOutput;
    }

    /**
     * Output system stylesheets.
     */
    public function outputSystemStylesheets()
    {
        $assets = $this->assetHelper->getAssets();

        if (isset($assets['css'])) {
            foreach ($assets['css'] as $url) {
                echo '<link rel="stylesheet" href="'.$this->getUrl($url).'" data-source="mautic" />'."\n";
            }
        }
    }

    /**
     * Output system scripts.
     *
     * @param bool|false $includeEditor
     */
    public function outputSystemScripts($includeEditor = false)
    {
        $assets = $this->assetHelper->getAssets();

        if ($includeEditor) {
            $assets['js'] = array_merge($assets['js'], $this->getFroalaScripts());
        }

        if (isset($assets['js'])) {
            foreach ($assets['js'] as $url) {
                echo '<script src="'.$this->getUrl($url).'" data-source="mautic"></script>'."\n";
            }
        }
    }

    /**
     * Fetch system scripts.
     *
     * @param bool $render        If true, a string will be returned of rendered script for header
     * @param bool $includeEditor
     *
     * @return array|string
     */
    public function getSystemScripts($render = false, $includeEditor = false)
    {
        $assets = $this->assetHelper->getAssets();

        if ($includeEditor) {
            $assets['js'] = array_merge($assets['js'], $this->getFroalaScripts());
        }

        if ($render) {
            $js = '';
            if (isset($assets['js'])) {
                foreach ($assets['js'] as $url) {
                    $js .= '<script src="'.$this->getUrl($url).'" data-source="mautic"></script>'."\n";
                }
            }

            return $js;
        }

        return $assets['js'];
    }

    /**
     * Load Froala JS source files.
     *
     * @return array
     */
    public function getFroalaScripts()
    {
        $base    = 'app/bundles/CoreBundle/Assets/js/libraries/froala/';
        $plugins = $base.'plugins/';

        return [
            $base.'froala_editor.js?v'.$this->version,
            $plugins.'align.js?v'.$this->version,
            $plugins.'code_beautifier.js?v'.$this->version,
            $plugins.'code_view.js?v'.$this->version,
            $plugins.'colors.js?v'.$this->version,
            // $plugins . 'file.js?v' . $this->version,  // @todo
            $plugins.'font_family.js?v'.$this->version,
            $plugins.'font_size.js?v'.$this->version,
            $plugins.'fullscreen.js?v'.$this->version,
            $plugins.'image.js?v'.$this->version,
            // $plugins . 'image_manager.js?v' . $this->version,
            $plugins.'filemanager.js?v'.$this->version,
            $plugins.'inline_style.js?v'.$this->version,
            $plugins.'line_breaker.js?v'.$this->version,
            $plugins.'link.js?v'.$this->version,
            $plugins.'lists.js?v'.$this->version,
            $plugins.'paragraph_format.js?v'.$this->version,
            $plugins.'paragraph_style.js?v'.$this->version,
            $plugins.'quick_insert.js?v'.$this->version,
            $plugins.'quote.js?v'.$this->version,
            $plugins.'table.js?v'.$this->version,
            $plugins.'url.js?v'.$this->version,
            //$plugins . 'video.js?v' . $this->version,
            $plugins.'gatedvideo.js?v'.$this->version,
            $plugins.'token.js?v'.$this->version,
            $plugins.'dynamic_content.js?v'.$this->version,
        ];
    }

    /**
     * Turn all URLs in clickable links.
     *
     * @param string $text
     * @param array  $protocols  http/https, ftp, mail, twitter
     * @param array  $attributes
     *
     * @return string
     */
    public function makeLinks($text, $protocols = ['http', 'mail'], array $attributes = [])
    {
        // Link attributes
        $attr = '';
        foreach ($attributes as $key => $val) {
            $attr = ' '.$key.'="'.htmlentities($val).'"';
        }

        $links = [];

        // Extract existing links and tags
        $text = preg_replace_callback('~(<a .*?>.*?</a>|<.*?>)~i', function ($match) use (&$links) {
            return '<'.array_push($links, $match[1]).'>';
        }, $text);

        // Extract text links for each protocol
        foreach ((array) $protocols as $protocol) {
            switch ($protocol) {
                case 'http':
                case 'https':
                    $text = preg_replace_callback('~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) {
                        if ($match[1]) {
                            $protocol = $match[1];
                        }
                        $link = $this->escape($match[2] ?: $match[3]);

                        return '<'.array_push($links, "<a $attr href=\"$protocol://$link\">$link</a>").'>';
                    }, $text);
                    break;
                case 'mail':
                    $text = preg_replace_callback('~([^\s<]+?@[^\s<]+?\.[^\s<]+)(?<![\.,:])~', function ($match) use (&$links, $attr) {
                        $match[1] = $this->escape($match[1]);

                        return '<'.array_push($links, "<a $attr href=\"mailto:{$match[1]}\">{$match[1]}</a>").'>';
                    }, $text);
                    break;
                case 'twitter':
                    $text = preg_replace_callback('~(?<!\w)[@#](\w++)~', function ($match) use (&$links, $attr) {
                        $match[0] = $this->escape($match[0]);
                        $match[1] = $this->escape($match[1]);

                        return '<'.array_push($links, "<a $attr href=\"https://twitter.com/".($match[0][0] == '@' ? '' : 'search/%23').$match[1]."\">{$match[0]}</a>").'>';
                    }, $text);
                    break;
                default:
                    $text = preg_replace_callback('~'.preg_quote($protocol, '~').'://([^\s<]+?)(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) {
                        $match[1] = $this->escape($match[1]);

                        return '<'.array_push($links, "<a $attr href=\"$protocol://{$match[1]}\">{$match[1]}</a>").'>';
                    }, $text);
                    break;
            }
        }

        // Insert all link
        return preg_replace_callback('/<(\d+)>/', function ($match) use (&$links) {
            return $links[$match[1] - 1];
        }, $text);
    }

    /**
     * Returns only first $charCount chars of the $text and adds "..." if it is shortened.
     *
     * @param string $text
     * @param int    $charCount
     *
     * @return string
     */
    public function shortenText($text, $charCount = null)
    {
        if ($charCount && strlen($text) > $charCount) {
            return mb_substr($text, 0, $charCount, 'utf-8').'...';
        }

        return $text;
    }

    /**
     * @param MauticFactory $factory
     */
    public function setFactory(MauticFactory $factory)
    {
        $this->factory = $factory;
        $this->version = substr(hash('sha1', $this->factory->getParameter('secret_key').$this->factory->getVersion()), 0, 8);
    }

    /**
     * @param AssetGenerationHelper $helper
     */
    public function setAssetHelper(AssetGenerationHelper $helper)
    {
        $this->assetHelper = $helper;
    }

    /**
     * @param           $country
     * @param bool|true $urlOnly
     * @param string    $class
     *
     * @return string
     */
    public function getCountryFlag($country, $urlOnly = true, $class = '')
    {
        $flagPath = $this->factory->getSystemPath('assets', true).'/images/flags/';
        $relpath  = $this->factory->getSystemPath('assets').'/images/flags/';
        $country  = ucwords(str_replace(' ', '-', $country));
        $flagImg  = '';
        if (file_exists($flagPath.$country.'.png')) {
            if (file_exists($flagPath.$country.'.png')) {
                $flagImg = $this->getUrl($relpath.$country.'.png');
            }
        }

        if ($urlOnly) {
            return $flagImg;
        } else {
            return '<img src="'.$flagImg.'" class="'.$class.'" />';
        }
    }

    /**
     * @return array
     *
     * @internal
     */
    public function getAssets()
    {
        return $this->assets;
    }

    /**
     * @param $assets
     *
     * @internal
     */
    public function setAssets($assets)
    {
        $this->assets = $assets;
    }

    /**
     * Clear all the assets.
     */
    public function clear()
    {
        $this->assets = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'assets';
    }

    /**
     * @param $string
     *
     * @return string
     */
    private function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }
}
