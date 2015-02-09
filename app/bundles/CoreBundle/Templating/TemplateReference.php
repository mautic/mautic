<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference as BaseTemplateReference;

/**
 * Class TemplateReference
 */
class TemplateReference extends BaseTemplateReference
{

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * Set Mautic's factory class
     *
     * @param MauticFactory $factory
     */
    public function setFactory(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        $controller = str_replace('\\', '/', $this->get('controller'));

        $fileName = $this->get('name').'.'.$this->get('format').'.'.$this->get('engine');
        $path     = (empty($controller) ? '' : $controller.'/').$fileName;

        if (!empty($this->parameters['bundle'])) {
            preg_match('/Mautic(.*?)Bundle/', $this->parameters['bundle'], $match);

            if (!empty($match[1])) {
                $theme = $this->factory->getTheme();
                //check for an override and load it if there is
                $themeDir = $theme->getThemePath();
                if (!file_exists($template = $themeDir . '/html/' . $this->parameters['bundle'] . '/' . $path)) {
                    $template = '@' . $this->get('bundle') . '/Views/' . $path;
                }
            }
        } else {
            $themes = $this->factory->getInstalledThemes();
            if (isset($themes[$controller])) {
                //this is a file in a specific Mautic theme folder
                $theme = $this->factory->getTheme($controller);

                $template = $theme->getThemePath() . '/html/' . $fileName;
            }
        }

        if (empty($template)) {
            //try the parent
            return parent::getPath();
        }

        return $template;
    }
}
