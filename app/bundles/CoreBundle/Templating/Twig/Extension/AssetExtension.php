<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Factory\MauticFactory;
use Twig_Environment;
use Twig_Extension;
use Twig_SimpleFunction;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;


class AssetExtension extends Twig_Extension
{
    /**
     * @var AssetsHelper
     */
    protected $helper;

    public function __construct(MauticFactory $factory)
    {
        $this->helper = $factory->getHelper('template.assets');
    }

    /**
     * @see Twig_Extension::getFunctions()
     */
    public function getFunctions()
    {
        return array(
            'outputScripts' => new Twig_SimpleFunction('outputScripts', array($this, 'outputScripts'), array('is_safe' => array('all'))),
            'outputHeadDeclarations' => new Twig_SimpleFunction('outputHeadDeclarations', array($this, 'outputHeadDeclarations'), array('is_safe' => array('all'))),
            'getAssetUrl' => new Twig_SimpleFunction('getAssetUrl', array($this, 'getAssetUrl'), array('is_safe' => array('html'))),
            'outputStyles' => new Twig_SimpleFunction('outputStyles', array($this, 'outputStyles'), array('is_safe' => array('html'))),
            'outputSystemScripts' => new Twig_SimpleFunction('outputSystemScripts', array($this, 'outputSystemScripts'), array('is_safe' => array('html'))),
            'outputSystemStylesheets' => new Twig_SimpleFunction('outputSystemStylesheets', array($this, 'outputSystemStylesheets'), array('is_safe' => array('html'))),
        );
    }

    public function getName()
    {
        return 'asset';
    }

    public function outputSystemStylesheets()
    {
        ob_start();

        $this->helper->outputSystemStylesheets();

        return ob_get_clean();
    }

    /**
     * @param bool $includeEditor
     * @return string
     */
    public function outputSystemScripts($includeEditor = false)
    {
        ob_start();

        $this->helper->outputSystemScripts($includeEditor);

        return ob_get_clean();
    }

    public function outputScripts($name)
    {
        ob_start();

        $this->helper->outputScripts($name);

        return ob_get_clean();
    }

    public function outputStyles()
    {
        ob_start();

        $this->helper->outputStyles();

        return ob_get_clean();
    }

    public function outputHeadDeclarations()
    {
        ob_start();

        $this->helper->outputHeadDeclarations();

        return ob_get_clean();
    }

    public function getAssetUrl($path, $packageName = null, $version = null, $absolute = false, $ignorePrefix = false)
    {
        return $this->helper->getUrl($path, $packageName, $version, $absolute, $ignorePrefix);
    }
}