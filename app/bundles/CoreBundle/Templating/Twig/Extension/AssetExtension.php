<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Twig_Extension;
use Twig_SimpleFunction;

class AssetExtension extends Twig_Extension
{
    /**
     * @var AssetsHelper
     */
    protected $assetsHelper;

    /**
     * AssetExtension constructor.
     *
     * @param AssetsHelper $assetsHelper
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
            'outputScripts'           => new Twig_SimpleFunction('outputScripts', [$this, 'outputScripts'], ['is_safe' => ['all']]),
            'outputHeadDeclarations'  => new Twig_SimpleFunction('outputHeadDeclarations', [$this, 'outputHeadDeclarations'], ['is_safe' => ['all']]),
            'getAssetUrl'             => new Twig_SimpleFunction('getAssetUrl', [$this, 'getAssetUrl'], ['is_safe' => ['html']]),
            'outputStyles'            => new Twig_SimpleFunction('outputStyles', [$this, 'outputStyles'], ['is_safe' => ['html']]),
            'outputSystemScripts'     => new Twig_SimpleFunction('outputSystemScripts', [$this, 'outputSystemScripts'], ['is_safe' => ['html']]),
            'outputSystemStylesheets' => new Twig_SimpleFunction('outputSystemStylesheets', [$this, 'outputSystemStylesheets'], ['is_safe' => ['html']]),
        ];
    }

    public function getName()
    {
        return 'coreasset';
    }

    public function outputSystemStylesheets()
    {
        ob_start();

        $this->assetsHelper->outputSystemStylesheets();

        return ob_get_clean();
    }

    /**
     * @param bool $includeEditor
     *
     * @return string
     */
    public function outputSystemScripts($includeEditor = false)
    {
        ob_start();

        $this->assetsHelper->outputSystemScripts($includeEditor);

        return ob_get_clean();
    }

    public function outputScripts($name)
    {
        ob_start();

        $this->assetsHelper->outputScripts($name);

        return ob_get_clean();
    }

    public function outputStyles()
    {
        ob_start();

        $this->assetsHelper->outputStyles();

        return ob_get_clean();
    }

    public function outputHeadDeclarations()
    {
        ob_start();

        $this->assetsHelper->outputHeadDeclarations();

        return ob_get_clean();
    }

    public function getAssetUrl($path, $packageName = null, $version = null, $absolute = false, $ignorePrefix = false)
    {
        return $this->assetsHelper->getUrl($path, $packageName, $version, $absolute, $ignorePrefix);
    }
}
