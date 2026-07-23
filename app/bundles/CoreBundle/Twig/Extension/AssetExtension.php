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

    #[\Twig\Attribute\AsTwigFunction(name: 'outputSystemStylesheets', isSafe: ['html'])]
    public function outputSystemStylesheets(): string
    {
        ob_start();

        $this->assetsHelper->outputSystemStylesheets();

        return ob_get_clean();
    }

    /**
     * Loads an addon JS script file.
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'includeScript', isSafe: ['all'])]
    public function includeScript(string $assetFilePath, string $onLoadCallback = '', string $alreadyLoadedCallback = ''): string
    {
        return $this->assetsHelper->includeScript($assetFilePath, $onLoadCallback, $alreadyLoadedCallback);
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'includeStylesheet', isSafe: ['all'])]
    public function includeStylesheet(string $assetFilePath): string
    {
        return $this->assetsHelper->includeStylesheet($assetFilePath);
    }

    /**
     * @param bool $includeEditor
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'outputSystemScripts', isSafe: ['html'])]
    public function outputSystemScripts($includeEditor = false): string
    {
        ob_start();

        $this->assetsHelper->outputSystemScripts($includeEditor);

        return ob_get_clean();
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'outputScripts', isSafe: ['all'])]
    public function outputScripts(string $name): string
    {
        ob_start();

        $this->assetsHelper->outputScripts($name);

        return ob_get_clean();
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'outputStyles', isSafe: ['html'])]
    public function outputStyles(): string
    {
        ob_start();

        $this->assetsHelper->outputStyles();

        return ob_get_clean();
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'outputHeadDeclarations', isSafe: ['all'])]
    public function outputHeadDeclarations(): string
    {
        ob_start();

        $this->assetsHelper->outputHeadDeclarations();

        return ob_get_clean();
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'addAssetScript', isSafe: ['html'])]
    public function addScript(string $script, string $location = 'head', bool $async = false, ?string $name = null): AssetsHelper
    {
        return $this->assetsHelper->addScript($script, $location, $async, $name);
    }

    /**
     * @param bool $absolute
     * @param bool $ignorePrefix
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'getAssetUrl', isSafe: ['html'])]
    public function getAssetUrl(string $path, ?string $packageName = null, ?string $version = null, $absolute = false, $ignorePrefix = false): string
    {
        return $this->assetsHelper->getUrl($path, $packageName, $version, $absolute, $ignorePrefix);
    }

    /**
     * @param string     $path
     * @param bool|false $absolute
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'getOverridableUrl', isSafe: ['html'])]
    public function getOverridableUrl($path, $absolute = false): string
    {
        return $this->assetsHelper->getOverridableUrl($path, $absolute);
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'assetsGetImagesPath')]
    public function getImagesPath(): string
    {
        return $this->assetsHelper->getImagesPath();
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'assetsGetPrefix')]
    public function getAssetPrefix(bool $includeEndingslash = false): string
    {
        return $this->assetsHelper->getAssetPrefix($includeEndingslash);
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'assetAddScriptDeclaration')]
    public function addScriptDeclaration(string $script, string $location = 'head'): string
    {
        $this->assetsHelper->addScriptDeclaration($script, $location);

        return '';
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'assetAddCustomDeclaration')]
    public function addCustomDeclaration(string $script, string $location): string
    {
        $this->assetsHelper->addCustomDeclaration($script, $location);

        return '';
    }

    /**
     * @see Mautic\CoreBundle\Twig\Helper\AssetsHelper::getCountryFlag
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'assetGetCountryFlag')]
    public function getCountryFlag(string $country, bool $urlOnly = true, string $class = ''): string
    {
        return $this->assetsHelper->getCountryFlag($country, $urlOnly, $class);
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'assetGetBaseUrl', isSafe: ['html'])]
    public function getBaseUrl(): string
    {
        return (string) $this->assetsHelper->getBaseUrl();
    }

    /**
     * @param array<string> $protocols
     * @param array<mixed>  $attributes
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'assetMakeLinks', isSafe: ['html'])]
    public function makeLinks(string $text, array $protocols = ['http', 'mail'], array $attributes = []): string
    {
        return $this->assetsHelper->makeLinks($text, $protocols, $attributes);
    }
}
