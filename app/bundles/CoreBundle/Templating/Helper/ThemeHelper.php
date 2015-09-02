<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Mautic\CoreBundle\Exception\BadConfigurationException;
use Mautic\CoreBundle\Exception\FileNotFoundException;
use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Finder\Finder;

/**
 * Class ThemeHelper
 */
class ThemeHelper
{

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @var string
     */
    private $theme;

    /**
     * @var string
     */
    private $themeDir;

    /**
     * @var string
     */
    private $themePath;

    /**
     * @var mixed
     */
    private $config;

    /**
     * @param MauticFactory $factory
     * @param string        $theme
     *
     * @throws BadConfigurationException
     * @throws FileNotFoundException
     */
    public function __construct(MauticFactory $factory, $theme = 'current')
    {
        $this->factory   = $factory;
        $this->theme     = ($theme == 'current') ? $factory->getParameter('theme') : $theme;
        if ($this->theme == null) {
            $this->theme = 'Mauve';
        }
        $this->themeDir  = $factory->getSystemPath('themes') . '/' . $this->theme;
        $this->themePath = $factory->getSystemPath('themes_root') . '/' . $this->themeDir;

        //check to make sure the theme exists
        if (!file_exists($this->themePath)) {
            throw new FileNotFoundException($this->theme . ' not found!');
        }

        //get the config
        if (!file_exists($this->themePath . '/config.php')) {
            throw new BadConfigurationException($this->theme . ' is missing a required config file');
        }

        $this->config = include $this->themePath . '/config.php';

        if (!isset($this->config['name'])) {
            throw new BadConfigurationException($this->theme . ' does not have a valid config file');
        }
    }

    /**
     * Return  name of the template
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->config['name'];
    }

    /**
     * Returns the theme folder name
     *
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Get the theme's config
     *
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get the theme's slots
     *
     * @param $type
     *
     * @return array
     */
    public function getSlots($type)
    {
        return (isset($this->config['slots'][$type])) ? $this->config['slots'][$type] : array();
    }

    /**
     * Returns path to this theme
     *
     * @param bool $relative
     *
     * @return string
     */
    public function getThemePath($relative = false)
    {
        return ($relative) ? $this->themeDir : $this->themePath;
    }

    /**
     * Returns template
     *
     * @param $code
     *
     * @return bool|string
     */
    public function getErrorPageTemplate($code)
    {
        $errorPage = $this->getThemePath()."/error_{$code}.html.php";
        if (file_exists($errorPage)) {
            return ":{$this->theme}:error_{$code}.html.php";
        } else {
            return false;
        }
    }
}
