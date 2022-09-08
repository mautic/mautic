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
            new TwigFunction('outputHeadDeclarations', [$this, 'outputHeadDeclarations'], ['is_safe' => ['all']]),
            new TwigFunction('getAssetUrl', [$this, 'getAssetUrl'], ['is_safe' => ['html']]),
            new TwigFunction('outputStyles', [$this, 'outputStyles'], ['is_safe' => ['html']]),
            new TwigFunction('outputSystemScripts', [$this, 'outputSystemScripts'], ['is_safe' => ['html']]),
            new TwigFunction('outputSystemStylesheets', [$this, 'outputSystemStylesheets'], ['is_safe' => ['html']]),
            new TwigFunction('assetsGetImagesPath', [$this, 'getImagesPath']),
            new TwigFunction('assetsGetPrefix', [$this, 'getAssetPrefix']),
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
}
