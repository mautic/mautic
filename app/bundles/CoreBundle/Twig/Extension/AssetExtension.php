<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AssetExtension extends AbstractExtension
{
    public function __construct(
        protected AssetsHelper $assetsHelper
    ) {
    }

    /**
     * @see Twig_Extension::getFunctions()
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('outputScripts', [$this, 'outputScripts'], ['is_safe' => ['all']]),
            new TwigFunction('includeScript', [$this, 'includeScript'], ['is_safe' => ['all']]),
            new TwigFunction('includeStylesheet', [$this, 'includeStylesheet'], ['is_safe' => ['all']]),
            new TwigFunction('outputHeadDeclarations', [$this, 'outputHeadDeclarations'], ['is_safe' => ['all']]),
            new TwigFunction('getAssetUrl', [$this, 'getAssetUrl'], ['is_safe' => ['html']]),
            new TwigFunction('getOverridableUrl', [$this, 'getOverridableUrl'], ['is_safe' => ['html']]),
            new TwigFunction('addAssetScript', [$this, 'addScript'], ['is_safe' => ['html']]),
            new TwigFunction('outputStyles', [$this, 'outputStyles'], ['is_safe' => ['html']]),
            new TwigFunction('outputSystemScripts', [$this, 'outputSystemScripts'], ['is_safe' => ['html']]),
            new TwigFunction('outputSystemStylesheets', [$this, 'outputSystemStylesheets'], ['is_safe' => ['html']]),
            new TwigFunction('assetsGetImagesPath', [$this, 'getImagesPath']),
            new TwigFunction('assetsGetPrefix', [$this, 'getAssetPrefix']),
            new TwigFunction('assetAddScriptDeclaration', [$this, 'addScriptDeclaration']),
            new TwigFunction('assetGetCountryFlag', [$this, 'getCountryFlag']),
            new TwigFunction('assetGetBaseUrl', [$this, 'getBaseUrl'], ['is_safe' => ['html']]),
            new TwigFunction('assetMakeLinks', [$this, 'makeLinks'], ['is_safe' => ['html']]),
        ];
    }

    public function getName(): string
    {
        return 'coreasset';
    }

    public function outputSystemStylesheets(): string
    {
        ob_start();

        $this->assetsHelper->outputSystemStylesheets();

        return ob_get_clean();
    }

    /**
     * Loads an addon JS script file.
     */
    public function includeScript(string $assetFilePath, string $onLoadCallback = '', string $alreadyLoadedCallback = ''): string
    {
        return $this->assetsHelper->includeScript($assetFilePath, $onLoadCallback, $alreadyLoadedCallback);
    }

    public function includeStylesheet(string $assetFilePath): string
    {
        return $this->assetsHelper->includeStylesheet($assetFilePath);
    }

    /**
     * @param bool $includeEditor
     */
    public function outputSystemScripts($includeEditor = false): string
    {
        ob_start();

        $this->assetsHelper->outputSystemScripts($includeEditor);

        return ob_get_clean();
    }

    public function outputScripts(string $name): string
    {
        ob_start();

        $this->assetsHelper->outputScripts($name);

        return ob_get_clean();
    }

    public function outputStyles(): string
    {
        ob_start();

        $this->assetsHelper->outputStyles();

        return ob_get_clean();
    }

    public function outputHeadDeclarations(): string
    {
        ob_start();

        $this->assetsHelper->outputHeadDeclarations();

        return ob_get_clean();
    }

    public function addScript(string $script, string $location = 'head', bool $async = false, string $name = null): AssetsHelper
    {
        return $this->assetsHelper->addScript($script, $location, $async, $name);
    }

    /**
     * @param string|null $packageName
     * @param string|null $version
     * @param bool        $absolute
     * @param bool        $ignorePrefix
     */
    public function getAssetUrl(string $path, $packageName = null, $version = null, $absolute = false, $ignorePrefix = false): string
    {
        return $this->assetsHelper->getUrl($path, $packageName, $version, $absolute, $ignorePrefix);
    }

    /**
     * @param string     $path
     * @param bool|false $absolute
     */
    public function getOverridableUrl($path, $absolute = false): string
    {
        return $this->assetsHelper->getOverridableUrl($path, $absolute);
    }

    public function getImagesPath(): string
    {
        return $this->assetsHelper->getImagesPath();
    }

    public function getAssetPrefix(bool $includeEndingslash = false): string
    {
        return $this->assetsHelper->getAssetPrefix($includeEndingslash);
    }

    public function addScriptDeclaration(string $script, string $location = 'head'): AssetsHelper
    {
        return $this->assetsHelper->addScriptDeclaration($script, $location);
    }

    /**
     * @see Mautic\CoreBundle\Twig\Helper\AssetsHelper::getCountryFlag
     */
    public function getCountryFlag(string $country, bool $urlOnly = true, string $class = ''): string
    {
        return $this->assetsHelper->getCountryFlag($country, $urlOnly, $class);
    }

    public function getBaseUrl(): string
    {
        return (string) $this->assetsHelper->getBaseUrl();
    }

    /**
     * @param array<string> $protocols
     * @param array<mixed>  $attributes
     */
    public function makeLinks(string $text, array $protocols = ['http', 'mail'], array $attributes = []): string
    {
        return $this->assetsHelper->makeLinks($text, $protocols, $attributes);
    }
}
