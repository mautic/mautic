<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\AssetsHelper;

class AssetExtension
{
    public function __construct(
        protected AssetsHelper $assetsHelper,
    ) {
    }

    public function getName(): string
    {
        return 'coreasset';
    }

    #[\Twig\Attribute\AsTwigFunction('outputSystemStylesheets', isSafe: ['html'])]
    public function outputSystemStylesheets(): string
    {
        ob_start();

        $this->assetsHelper->outputSystemStylesheets();

        return ob_get_clean();
    }

    /**
     * Loads an addon JS script file.
     */
    #[\Twig\Attribute\AsTwigFunction('includeScript', isSafe: ['all'])]
    public function includeScript(string $assetFilePath, string $onLoadCallback = '', string $alreadyLoadedCallback = ''): string
    {
        return $this->assetsHelper->includeScript($assetFilePath, $onLoadCallback, $alreadyLoadedCallback);
    }

    #[\Twig\Attribute\AsTwigFunction('includeStylesheet', isSafe: ['all'])]
    public function includeStylesheet(string $assetFilePath): string
    {
        return $this->assetsHelper->includeStylesheet($assetFilePath);
    }

    /**
     * @param bool $includeEditor
     */
    #[\Twig\Attribute\AsTwigFunction('outputSystemScripts', isSafe: ['html'])]
    public function outputSystemScripts($includeEditor = false): string
    {
        ob_start();

        $this->assetsHelper->outputSystemScripts($includeEditor);

        return ob_get_clean();
    }

    #[\Twig\Attribute\AsTwigFunction('outputScripts', isSafe: ['all'])]
    public function outputScripts(string $name): string
    {
        ob_start();

        $this->assetsHelper->outputScripts($name);

        return ob_get_clean();
    }

    #[\Twig\Attribute\AsTwigFunction('outputStyles', isSafe: ['html'])]
    public function outputStyles(): string
    {
        ob_start();

        $this->assetsHelper->outputStyles();

        return ob_get_clean();
    }

    #[\Twig\Attribute\AsTwigFunction('outputHeadDeclarations', isSafe: ['all'])]
    public function outputHeadDeclarations(): string
    {
        ob_start();

        $this->assetsHelper->outputHeadDeclarations();

        return ob_get_clean();
    }

    #[\Twig\Attribute\AsTwigFunction('addAssetScript', isSafe: ['html'])]
    public function addScript(string $script, string $location = 'head', bool $async = false, ?string $name = null): AssetsHelper
    {
        return $this->assetsHelper->addScript($script, $location, $async, $name);
    }

    /**
     * @param string|null $packageName
     * @param string|null $version
     * @param bool        $absolute
     * @param bool        $ignorePrefix
     */
    #[\Twig\Attribute\AsTwigFunction('getAssetUrl', isSafe: ['html'])]
    public function getAssetUrl(string $path, $packageName = null, $version = null, $absolute = false, $ignorePrefix = false): string
    {
        return $this->assetsHelper->getUrl($path, $packageName, $version, $absolute, $ignorePrefix);
    }

    /**
     * @param string     $path
     * @param bool|false $absolute
     */
    #[\Twig\Attribute\AsTwigFunction('getOverridableUrl', isSafe: ['html'])]
    public function getOverridableUrl($path, $absolute = false): string
    {
        return $this->assetsHelper->getOverridableUrl($path, $absolute);
    }

    #[\Twig\Attribute\AsTwigFunction('assetsGetImagesPath')]
    public function getImagesPath(): string
    {
        return $this->assetsHelper->getImagesPath();
    }

    #[\Twig\Attribute\AsTwigFunction('assetsGetPrefix')]
    public function getAssetPrefix(bool $includeEndingslash = false): string
    {
        return $this->assetsHelper->getAssetPrefix($includeEndingslash);
    }

    #[\Twig\Attribute\AsTwigFunction('assetAddScriptDeclaration')]
    public function addScriptDeclaration(string $script, string $location = 'head'): string
    {
        $this->assetsHelper->addScriptDeclaration($script, $location);

        return '';
    }

    #[\Twig\Attribute\AsTwigFunction('assetAddCustomDeclaration')]
    public function addCustomDeclaration(string $script, string $location): string
    {
        $this->assetsHelper->addCustomDeclaration($script, $location);

        return '';
    }

    /**
     * @see Mautic\CoreBundle\Twig\Helper\AssetsHelper::getCountryFlag
     */
    #[\Twig\Attribute\AsTwigFunction('assetGetCountryFlag')]
    public function getCountryFlag(string $country, bool $urlOnly = true, string $class = ''): string
    {
        return $this->assetsHelper->getCountryFlag($country, $urlOnly, $class);
    }

    #[\Twig\Attribute\AsTwigFunction('assetGetBaseUrl', isSafe: ['html'])]
    public function getBaseUrl(): string
    {
        return (string) $this->assetsHelper->getBaseUrl();
    }

    /**
     * @param array<string> $protocols
     * @param array<mixed>  $attributes
     */
    #[\Twig\Attribute\AsTwigFunction('assetMakeLinks', isSafe: ['html'])]
    public function makeLinks(string $text, array $protocols = ['http', 'mail'], array $attributes = []): string
    {
        return $this->assetsHelper->makeLinks($text, $protocols, $attributes);
    }
}
