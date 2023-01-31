<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AssetExtension extends AbstractExtension
{
    /**
     * @var AssetsHelper
     */
    protected $assetsHelper;

    /**
     * AssetExtension constructor.
     */
    public function __construct(AssetsHelper $assetsHelper)
    {
        $this->assetsHelper = $assetsHelper;
    }

    /**
     * @see Twig_Extension::getFunctions()
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('outputScripts', [$this, 'outputScripts'], ['is_safe' => ['all']]),
            new TwigFunction('includeScript', [$this, 'includeScript'], ['is_safe' => ['all']]),
            new TwigFunction('outputHeadDeclarations', [$this, 'outputHeadDeclarations'], ['is_safe' => ['all']]),
            new TwigFunction('getAssetUrl', [$this, 'getAssetUrl'], ['is_safe' => ['html']]),
            new TwigFunction('addAssetScript', [$this, 'addScript'], ['is_safe' => ['html']]),
            new TwigFunction('outputStyles', [$this, 'outputStyles'], ['is_safe' => ['html']]),
            new TwigFunction('outputSystemScripts', [$this, 'outputSystemScripts'], ['is_safe' => ['html']]),
            new TwigFunction('outputSystemStylesheets', [$this, 'outputSystemStylesheets'], ['is_safe' => ['html']]),
            new TwigFunction('assetsGetImagesPath', [$this, 'getImagesPath']),
            new TwigFunction('assetsGetPrefix', [$this, 'getAssetPrefix']),
            new TwigFunction('assetAddScriptDeclaration', [$this, 'addScriptDeclaration']),
            new TwigFunction('assetGetCountryFlag', [$this, 'getCountryFlag']),
        ];
    }

    public function getName()
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

    /**
     * @param bool $includeEditor
     */
    public function outputSystemScripts($includeEditor = false): string
    {
        ob_start();

        $this->assetsHelper->outputSystemScripts($includeEditor);

        return ob_get_clean();
    }

    public function outputScripts($name): string
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

    public function getAssetUrl($path, $packageName = null, $version = null, $absolute = false, $ignorePrefix = false): string
    {
        return $this->assetsHelper->getUrl($path, $packageName, $version, $absolute, $ignorePrefix);
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
     * @see Mautic\CoreBundle\Templating\Helper\AssetsHelper::getCountryFlag
     */
    public function getCountryFlag(string $country, bool $urlOnly = true, string $class = ''): string
    {
        return $this->assetsHelper->getCountryFlag($country, $urlOnly, $class);
    }
}
