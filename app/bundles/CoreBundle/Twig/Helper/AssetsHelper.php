<?php

namespace Mautic\CoreBundle\Twig\Helper;

use Mautic\CoreBundle\Helper\AssetGenerationHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\InstallBundle\Install\InstallService;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\BuilderIntegrationsHelper;
use Symfony\Component\Asset\Packages;

final class AssetsHelper
{
    /**
     * Used for Mautic app.
     */
    public const CONTEXT_APP = 'app';

    /**
     * Used within the content iframe when building content with a theme.
     */
    public const CONTEXT_BUILDER = 'builder';

    private ?\Mautic\CoreBundle\Helper\AssetGenerationHelper $assetHelper = null;

    private string $context = self::CONTEXT_APP;

    /**
     * @var array<mixed, mixed>
     */
    private $assets = [
        self::CONTEXT_APP => [],
    ];

    private ?string $version = null;

    /**
     * @var string
     */
    private $siteUrl;

    private ?\Mautic\CoreBundle\Helper\PathsHelper $pathsHelper = null;

    private BuilderIntegrationsHelper $builderIntegrationsHelper;

    private InstallService $installService;

    public function __construct(
        private Packages $packages,
        private CoreParametersHelper $coreParametersHelper
    ) {
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
            if ($includeEndingSlash && !str_ends_with($prefix, '/')) {
                $prefix .= '/';
            } elseif (!$includeEndingSlash && str_ends_with($prefix, '/')) {
                $prefix = substr($prefix, 0, -1);
            }
        }

        return $prefix;
    }

    public function getImagesPath(bool $absolute = false): string
    {
        return $this->pathsHelper->getSystemPath('images', $absolute);
    }

    /**
     * Returns the path to an asset that may be overridden in the media folder.
     *
     * This function is meant for assets that may be overridden in the media folder.
     * This could be logo's, country flags, ...
     * So to be able to override an asset, it has to exist in the assets folder.
     *
     * @param string     $path
     * @param bool|false $absolute
     *
     * @return string|bool
     */
    public function getOverridableUrl($path, $absolute = false)
    {
        $mediaPath  = $this->pathsHelper->getSystemPath('media', false);
        $assetsPath = $this->pathsHelper->getSystemPath('assets', false);

        if (!file_exists($this->pathsHelper->getAssetsPath().DIRECTORY_SEPARATOR.$path)) {
            return false;
        }

        if (file_exists($this->pathsHelper->getMediaPath().DIRECTORY_SEPARATOR.$path)) {
            $path = $mediaPath.DIRECTORY_SEPARATOR.$path;
        } else {
            $path = $assetsPath.DIRECTORY_SEPARATOR.$path;
        }

        return $this->getUrl($path, null, null, $absolute);
    }

    /**
     * Set asset url path.
     *
     * @param string      $path
     * @param string|null $packageName
     * @param string|null $version
     * @param bool|false  $absolute
     * @param bool|false  $ignorePrefix
     *
     * @return string
     */
    public function getUrl($path, $packageName = null, $version = null, $absolute = false, $ignorePrefix = false)
    {
        // if we have http in the url it is absolute and we can just return it
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        // otherwise build the complete path
        if (!$ignorePrefix) {
            $assetPrefix = $this->getAssetPrefix(!str_starts_with($path, '/'));
            $path        = $assetPrefix.$path;
        }

        $path = $this->appendVersion($path, $version);
        $url  = $this->packages->getUrl($path, $packageName);

        if ($absolute) {
            $url = $this->getBaseUrl().'/'.$path;
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
     * @param string $context
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
     * @param string|array<string, string> $script
     * @param string                       $location
     * @param bool                         $async
     * @param string                       $name
     *
     * @return $this
     */
    public function addScript($script, $location = 'head', $async = false, $name = null)
    {
        $assets     = &$this->assets[$this->context];
        $addScripts = function ($s) use ($location, &$assets, $async, $name): void {
            $name = $name ?: 'script_'.hash('sha1', uniqid((string) mt_rand()));

            if ('head' == $location) {
                // special place for these so that declarations and scripts can be mingled
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
        if ('head' == $location) {
            // special place for these so that declarations and scripts can be mingled
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
     * @param string|array<string, string> $stylesheet
     *
     * @return $this
     */
    public function addStylesheet($stylesheet)
    {
        $addSheet = function ($s): void {
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
        if ('head' == $location) {
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
    public function outputStyles(): void
    {
        echo $this->getStyles();
    }

    /**
     * Outputs the stylesheets and style declarations.
     */
    public function getStyles(): string
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
    public function outputScripts($location): void
    {
        if (isset($this->assets[$this->context]['scripts'][$location])) {
            foreach (array_reverse($this->assets[$this->context]['scripts'][$location]) as $s) {
                [$script, $async] = $s;
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
    public function outputHeadDeclarations(): void
    {
        echo $this->getHeadDeclarations();
    }

    /**
     * Returns head scripts, stylesheets, and custom declarations.
     */
    public function getHeadDeclarations(): string
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
                        [$script, $async] = $output;

                        $headOutput .= "\n".'<script src="'.$this->getUrl($script).'"'.($async ? ' async' : '').' data-source="mautic"></script>';
                        break;
                    case 'custom':
                    case 'declaration':
                        if ('custom' == $type && $scriptOpen) {
                            $headOutput .= "\n</script>";
                            $scriptOpen = false;
                        } elseif ('declaration' == $type && !$scriptOpen) {
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
    public function outputSystemStylesheets(): void
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
    public function outputSystemScripts($includeEditor = false): void
    {
        $assets = $this->assetHelper->getAssets();

        if ($includeEditor) {
            $assets['js'] = array_merge($assets['js'], $this->getFroalaScripts(), $this->getCKEditorScripts());
        }

        if (isset($assets['js'])) {
            foreach ($assets['js'] as $url) {
                echo '<script src="'.$this->getUrl($url).'" data-source="mautic"></script>'."\n";
            }
        }

        if ($this->installService->checkIfInstalled()) {
            /**
             * We want to enable JS consumers to simply query Mautic.getActiveBuilderName() so they can add logic based on the active builder.
             * The $builderName variable is passed to the template so we can get that info on the JS-side.
             */
            try {
                $builder     = $this->builderIntegrationsHelper->getBuilder('email');
                $builderName = $builder->getName();
            } catch (IntegrationNotFoundException) {
                // Assume legacy builder
                $builderName = 'legacy';
            }

            echo '<script>Mautic.getActiveBuilderName = function() { return \''.$builderName.'\'; }</script>'."\n";
        }
    }

    /**
     * Fetch system scripts.
     *
     * @param bool $render        If true, a string will be returned of rendered script for header
     * @param bool $includeEditor
     *
     * @return array<string, string>|string
     */
    public function getSystemScripts($render = false, $includeEditor = false)
    {
        $assets = $this->assetHelper->getAssets();

        if ($includeEditor) {
            $assets['js'] = array_merge($assets['js'], $this->getFroalaScripts(), $this->getCKEditorScripts());
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
     * Load CKEditor JS source files.
     *
     * @return array<string>
     */
    private function getCKEditorScripts(): array
    {
        $base    = 'media/libraries/ckeditor/';

        return [
            $base.'ckeditor.js?v'.$this->version,
        ];
    }

    /**
     * Load Froala JS source files.
     *
     * @return string[]
     */
    public function getFroalaScripts(): array
    {
        if (!$this->coreParametersHelper->get('load_froala_assets')) {
            return [];
        }

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
            // $plugins . 'video.js?v' . $this->version,
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
     */
    public function includeScript($assetFilePath, $onLoadCallback = '', $alreadyLoadedCallback = ''): string
    {
        return '<script async="async" type="text/javascript" data-source="mautic">Mautic.loadScript(\''.$this->getUrl($assetFilePath)."', '$onLoadCallback', '$alreadyLoadedCallback');</script>";
    }

    /**
     * Include stylesheet.
     *
     * @param string $assetFilePath the path to the file location. Can use full path or relative to mautic web root
     */
    public function includeStylesheet($assetFilePath): string
    {
        return '<script async="async" type="text/javascript" data-source="mautic">Mautic.loadStylesheet(\''.$this->getUrl($assetFilePath).'\');</script>';
    }

    /**
     * Turn all URLs in clickable links.
     *
     * @param string                $text
     * @param array<string>         $protocols  http/https, ftp, mail, twitter
     * @param array<string, string> $attributes
     *
     * @return string|string[]|null
     */
    public function makeLinks($text, $protocols = ['http', 'mail'], array $attributes = []): string|array|null
    {
        // clear tags in text
        $text = InputHelper::url($text, false, $protocols);

        // Link attributes
        $attr = '';
        foreach ($attributes as $key => $val) {
            $attr = ' '.$key.'="'.htmlentities($val).'"';
        }

        $links = [];

        // Extract existing links and tags
        $text = preg_replace_callback('~(<a .*?>.*?</a>|<.*?>)~i', function ($match) use (&$links): string {
            return '<'.array_push($links, $match[1]).'>';
        }, $text);

        // Extract text links for each protocol
        foreach ((array) $protocols as $protocol) {
            $text = match ($protocol) {
                'http', 'https' => preg_replace_callback('~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr): string {
                    if ($match[1]) {
                        $protocol = $match[1];
                    }
                    $link = $this->escape($match[2] ?: $match[3]);

                    return '<'.array_push($links, "<a $attr href=\"$protocol://$link\">$link</a>").'>';
                }, $text),
                'mail' => preg_replace_callback('~([^\s<]+?@[^\s<]+?\.[^\s<]+)(?<![\.,:])~', function ($match) use (&$links, $attr): string {
                    $match[1] = $this->escape($match[1]);

                    return '<'.array_push($links, "<a $attr href=\"mailto:{$match[1]}\">{$match[1]}</a>").'>';
                }, $text),
                'twitter' => preg_replace_callback('~(?<!\w)[@#](\w++)~', function ($match) use (&$links, $attr): string {
                    $match[0] = $this->escape($match[0]);
                    $match[1] = $this->escape($match[1]);

                    return '<'.array_push($links, "<a $attr href=\"https://twitter.com/".('@' == $match[0][0] ? '' : 'search/%23').$match[1]."\">{$match[0]}</a>").'>';
                }, $text),
                default => preg_replace_callback('~'.preg_quote($protocol, '~').'://([^\s<]+?)(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr): string {
                    $match[1] = $this->escape($match[1]);

                    return '<'.array_push($links, "<a $attr href=\"$protocol://{$match[1]}\">{$match[1]}</a>").'>';
                }, $text),
            };
        }

        // Insert all link
        return preg_replace_callback('/<(\d+)>/', function ($match) use (&$links): string {
            return $links[(int) $match[1] - 1];
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
     * @param string    $country
     * @param bool|true $urlOnly
     * @param string    $class
     *
     * @return string
     */
    public function getCountryFlag($country, $urlOnly = true, $class = '')
    {
        $country  = ucwords(str_replace(' ', '-', $country));
        $flagImg  = (string) $this->getOverridableUrl('images/flags/'.$country.'.png');

        if ($urlOnly) {
            return $flagImg;
        } else {
            return '<img src="'.$flagImg.'" class="'.$class.'" />';
        }
    }

    /**
     * Clear all the assets.
     */
    public function clear(): void
    {
        $this->assets = [];
    }

    public function getName(): string
    {
        return 'assets';
    }

    public function setAssetHelper(AssetGenerationHelper $helper): void
    {
        $this->assetHelper = $helper;
    }

    /**
     * @param ?string $siteUrl can be null on installation
     */
    public function setSiteUrl($siteUrl): void
    {
        if ($siteUrl && str_ends_with($siteUrl, '/')) {
            $siteUrl = substr($siteUrl, 0, -1);
        }

        $this->siteUrl = $siteUrl;
    }

    public function setPathsHelper(PathsHelper $pathsHelper): void
    {
        $this->pathsHelper = $pathsHelper;
    }

    /**
     * @param string     $secretKey
     * @param string|int $version
     */
    public function setVersion($secretKey, $version): void
    {
        $this->version = substr(hash('sha1', $secretKey.$version), 0, 8);
    }

    public function setBuilderIntegrationsHelper(BuilderIntegrationsHelper $builderIntegrationsHelper): void
    {
        $this->builderIntegrationsHelper = $builderIntegrationsHelper;
    }

    public function setInstallService(InstallService $installService): void
    {
        $this->installService = $installService;
    }

    private function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }

    /**
     * Appends the version to the path if is not present.
     */
    private function appendVersion(string $path, string $version = null): string
    {
        $version = $version ?: $this->version;

        if (!$version) {
            // no version is set
            return $path;
        }

        $versionArgument   = 'v'.$version;
        $querySeparator    = '?';
        $argumentSeparator = '&amp;';
        $query             = explode($querySeparator, $path)[1] ?? '';
        parse_str(str_replace($argumentSeparator, '&', $query), $arguments);

        if (isset($arguments[$versionArgument])) {
            // path already contains the version
            return $path;
        }

        return rtrim($path, $querySeparator).($query ? $argumentSeparator : $querySeparator).$versionArgument;
    }
}
