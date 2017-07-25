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

use Mautic\CoreBundle\Helper\AssetGenerationHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Asset\Packages;

/**
 * Class AssetsHelper.
 */
class AssetsHelper
{
    /**
     * Used for Mautic app.
     */
    const CONTEXT_APP = 'app';

    /**
     * Used within the content iframe when building content with a theme.
     */
    const CONTEXT_BUILDER = 'builder';

    /**
     * @var AssetGenerationHelper
     */
    protected $assetHelper;

    /**
     * @var
     */
    protected $context = self::CONTEXT_APP;

    /**
     * @var array
     */
    protected $assets = [
        self::CONTEXT_APP => [],
    ];

    /**
     * @var
     */
    protected $version;

    /**
     * @var Packages
     */
    protected $packages;

    /**
     * @var
     */
    protected $siteUrl;

    /**
     * @var PathsHelper
     */
    protected $pathsHelper;

    /**
     * AssetsHelper constructor.
     *
     * @param Packages $packages
     */
    public function __construct(Packages $packages)
    {
        $this->packages = $packages;
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
        $prefix = $this->pathsHelper->getSystemPath('asset_prefix');
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
    public function getUrl($path, $packageName = null, $version = null, $absolute = false, $ignorePrefix = false)
    {
        // if we have http in the url it is absolute and we can just return it
        if (strpos($path, 'http') === 0) {
            return $path;
        }

        // otherwise build the complete path
        if (!$ignorePrefix) {
            $assetPrefix = $this->getAssetPrefix(strpos($path, '/') !== 0);
            $path        = $assetPrefix.$path;
        }

        $url = ((strpos($path, '/') !== 0) ? '/' : '').$path;

        if ($absolute) {
            $url = $this->getBaseUrl().$url;
        }

        // Remove the dev index so the assets work in the dev mode
        if (strpos($url, '/index_dev.php/')) {
            $url = str_replace('index_dev.php/', '', $url);
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
        return $this->siteUrl;
    }

    /**
     * Define the context for which the assets will be injected and/or retrieved.
     *
     * If changing the context from app, it's important to reset the context back to app after
     * injecting/fetching assets for a different context.
     *
     * @param $context
     *
     * @return $this
     */
    public function setContext($context = self::CONTEXT_APP)
    {
        $this->context = $context;
        if (!isset($this->assets[$context])) {
            $this->assets[$context] = [];
        }

        return $this;
    }

    /**
     * Adds a JS script to the template.
     *
     * @param string $script
     * @param string $location
     * @param bool   $async
     * @param string $name
     *
     * @return $this
     */
    public function addScript($script, $location = 'head', $async = false, $name = null)
    {
        $assets     = &$this->assets[$this->context];
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

        return $this;
    }

    /**
     * Adds JS script declarations to the template.
     *
     * @param string $script
     * @param string $location
     *
     * @return $this
     */
    public function addScriptDeclaration($script, $location = 'head')
    {
        if ($location == 'head') {
            //special place for these so that declarations and scripts can be mingled
            $this->assets[$this->context]['headDeclarations'][] = ['declaration' => $script];
        } else {
            if (!isset($this->assets[$this->context]['scriptDeclarations'][$location])) {
                $this->assets[$this->context]['scriptDeclarations'][$location] = [];
            }

            if (!in_array($script, $this->assets[$this->context]['scriptDeclarations'][$location])) {
                $this->assets[$this->context]['scriptDeclarations'][$location][] = $script;
            }
        }

        return $this;
    }

    /**
     * Adds a stylesheet to be loaded in the template header.
     *
     * @param string $stylesheet
     *
     * @return $this
     */
    public function addStylesheet($stylesheet)
    {
        $addSheet = function ($s) {
            if (!isset($this->assets[$this->context]['stylesheets'])) {
                $this->assets[$this->context]['stylesheets'] = [];
            }

            if (!in_array($s, $this->assets[$this->context]['stylesheets'])) {
                $this->assets[$this->context]['stylesheets'][] = $s;
            }
        };

        if (is_array($stylesheet)) {
            foreach ($stylesheet as $s) {
                $addSheet($s);
            }
        } else {
            $addSheet($stylesheet);
        }

        return $this;
    }

    /**
     * Add style tag to the header.
     *
     * @param string $styles
     *
     * @return $this
     */
    public function addStyleDeclaration($styles)
    {
        if (!isset($this->assets[$this->context]['styleDeclarations'])) {
            $this->assets[$this->context]['styleDeclarations'] = [];
        }

        if (!in_array($styles, $this->assets[$this->context]['styleDeclarations'])) {
            $this->assets[$this->context]['styleDeclarations'][] = $styles;
        }

        return $this;
    }

    /**
     * Adds a custom declaration to <head />.
     *
     * @param string $declaration
     * @param string $location
     *
     * @return $this
     */
    public function addCustomDeclaration($declaration, $location = 'head')
    {
        if ($location == 'head') {
            $this->assets[$this->context]['headDeclarations'][] = ['custom' => $declaration];
        } else {
            if (!isset($this->assets[$this->context]['customDeclarations'][$location])) {
                $this->assets[$this->context]['customDeclarations'][$location] = [];
            }

            if (!in_array($declaration, $this->assets[$this->context]['customDeclarations'][$location])) {
                $this->assets[$this->context]['customDeclarations'][$location][] = $declaration;
            }
        }

        return $this;
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
     *
     * @return string
     */
    public function getStyles()
    {
        $styles = '';
        if (isset($this->assets[$this->context]['stylesheets'])) {
            foreach (array_reverse($this->assets[$this->context]['stylesheets']) as $s) {
                $styles .= '<link rel="stylesheet" href="'.$this->getUrl($s).'" data-source="mautic" />'."\n";
            }
        }

        if (isset($this->assets[$this->context]['styleDeclarations'])) {
            $styles .= "<style data-source=\"mautic\">\n";
            foreach (array_reverse($this->assets[$this->context]['styleDeclarations']) as $d) {
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
        if (isset($this->assets[$this->context]['scripts'][$location])) {
            foreach (array_reverse($this->assets[$this->context]['scripts'][$location]) as $s) {
                list($script, $async) = $s;
                echo '<script src="'.$this->getUrl($script).'"'.($async ? ' async' : '').' data-source="mautic"></script>'."\n";
            }
        }

        if (isset($this->assets[$this->context]['scriptDeclarations'][$location])) {
            echo "<script data-source=\"mautic\">\n";
            foreach (array_reverse($this->assets[$this->context]['scriptDeclarations'][$location]) as $d) {
                echo "$d\n";
            }
            echo "</script>\n";
        }

        if (isset($this->assets[$this->context]['customDeclarations'][$location])) {
            foreach (array_reverse($this->assets[$this->context]['customDeclarations'][$location]) as $d) {
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
     *
     * @return string
     */
    public function getHeadDeclarations()
    {
        $headOutput = $this->getStyles();
        if (!empty($this->assets[$this->context]['headDeclarations'])) {
            $scriptOpen = false;

            foreach ($this->assets[$this->context]['headDeclarations'] as $declaration) {
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
     * Loads an addon script.
     *
     * @param string $assetFilePath         The path to the file location. Can use full path or relative to mautic web root
     * @param string $onLoadCallback        Mautic namespaced function to call for the script onload
     * @param string $alreadyLoadedCallback Mautic namespaced function to call if the script has already been loaded
     *
     * @return string
     */
    public function includeScript($assetFilePath, $onLoadCallback = '', $alreadyLoadedCallback = '')
    {
        return  '<script async="async" type="text/javascript" data-source="mautic">Mautic.loadScript(\''.$this->getUrl($assetFilePath)."', '$onLoadCallback', '$alreadyLoadedCallback');</script>";
    }

    /**
     * Include stylesheet.
     *
     * @param string $assetFilePath the path to the file location. Can use full path or relative to mautic web root
     *
     * @return string
     */
    public function includeStylesheet($assetFilePath)
    {
        return  '<script async="async" type="text/javascript" data-source="mautic">Mautic.loadStylesheet(\''.$this->getUrl($assetFilePath).'\');</script>';
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
     * @param           $country
     * @param bool|true $urlOnly
     * @param string    $class
     *
     * @return string
     */
    public function getCountryFlag($country, $urlOnly = true, $class = '')
    {
        $flagPath = $this->pathsHelper->getSystemPath('assets', true).'/images/flags/';
        $relpath  = $this->pathsHelper->getSystemPath('assets').'/images/flags/';
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
     * Not used.
     */
    public function setCharset()
    {
    }

    /**
     * @param AssetGenerationHelper $helper
     */
    public function setAssetHelper(AssetGenerationHelper $helper)
    {
        $this->assetHelper = $helper;
    }

    /**
     * @param $siteUrl
     */
    public function setSiteUrl($siteUrl)
    {
        if (substr($siteUrl, -1) === '/') {
            $siteUrl = substr($siteUrl, 0, -1);
        }

        $this->siteUrl = $siteUrl;
    }

    /**
     * @param PathsHelper $pathsHelper
     */
    public function setPathsHelper(PathsHelper $pathsHelper)
    {
        $this->pathsHelper = $pathsHelper;
    }

    /**
     * @param $secretKey
     * @param $version
     */
    public function setVersion($secretKey, $version)
    {
        $this->version = substr(hash('sha1', $secretKey.$version), 0, 8);
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
