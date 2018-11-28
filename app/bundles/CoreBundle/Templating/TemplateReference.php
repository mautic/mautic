<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating;

use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference as BaseTemplateReference;

/**
 * Class TemplateReference.
 */
class TemplateReference extends BaseTemplateReference
{
    /**
     * @var
     */
    protected $themeOverride;

    /**
     * @var ThemeHelper
     */
    protected $themeHelper;

    /**
     * @var PathsHelper
     */
    protected $pathsHelper;

    /**
     * @param ThemeHelper $themeHelper
     */
    public function setThemeHelper(ThemeHelper $themeHelper)
    {
        $this->themeHelper = $themeHelper;
    }

    /**
     * @param PathsHelper $pathsHelper
     */
    public function setPathsHelper(PathsHelper $pathsHelper)
    {
        $this->pathsHelper = $pathsHelper;
    }

    /**
     * Set a template specific theme override.
     *
     * @param $theme
     */
    public function setThemeOverride($theme)
    {
        $this->themeOverride = $theme;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        $controller = str_replace('\\', '/', $this->get('controller'));

        if (!empty($this->themeOverride)) {
            try {
                $theme    = $this->themeHelper->getTheme($this->themeOverride);
                $themeDir = $theme->getThemePath();
            } catch (\Exception $e) {
            }
        } else {
            $theme    = $this->themeHelper->getTheme();
            $themeDir = $theme->getThemePath();
        }

        $fileName = $this->get('name').'.'.$this->get('format').'.'.$this->get('engine');
        $path     = (empty($controller) ? '' : $controller.'/').$fileName;

        if (!empty($this->parameters['bundle'])) {
            $bundleRoot = $this->pathsHelper->getSystemPath('bundles', true);
            $pluginRoot = $this->pathsHelper->getSystemPath('plugins', true);

            // Check for a system-wide override
            $themePath      = $this->pathsHelper->getSystemPath('themes', true);
            $systemTemplate = $themePath.'/system/'.$this->parameters['bundle'].'/'.$path;

            if (file_exists($systemTemplate)) {
                $template = $systemTemplate;
            } else {
                //check for an override and load it if there is
                if (!empty($themeDir) && file_exists($themeDir.'/html/'.$this->parameters['bundle'].'/'.$path)) {
                    // Theme override
                    $template = $themeDir.'/html/'.$this->parameters['bundle'].'/'.$path;
                } else {
                    preg_match('/Mautic(.*?)Bundle/', $this->parameters['bundle'], $match);

                    if (
                        (!empty($match[1]) && file_exists($bundleRoot.'/'.$match[1].'Bundle/Views/'.$path)) ||
                        file_exists($pluginRoot.'/'.$this->parameters['bundle'].'/Views/'.$path)
                    ) {
                        // Mautic core template
                        $template = '@'.$this->get('bundle').'/Views/'.$path;
                    }
                }
            }
        } else {
            $themes = $this->themeHelper->getInstalledThemes();
            if (isset($themes[$controller])) {
                //this is a file in a specific Mautic theme folder
                $theme = $this->themeHelper->getTheme($controller);

                $template = $theme->getThemePath().'/html/'.$fileName;
            }
        }

        if (empty($template)) {
            //try the parent
            return parent::getPath();
        }

        return $template;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogicalName()
    {
        $logicalName = parent::getLogicalName();

        if (!empty($this->themeOverride)) {
            $logicalName = $this->themeOverride.'|'.$logicalName;
        }

        return $logicalName;
    }
}
