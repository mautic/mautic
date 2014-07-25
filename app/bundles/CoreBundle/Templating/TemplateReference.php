<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference as BaseTemplateReference;

/**
 * Class TemplateReference
 *
 * @package Mautic\CoreBundle\Templating
 */
class TemplateReference extends BaseTemplateReference
{

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
     *
     * @return string
     */
    public function getPath()
    {
        $controller = str_replace('\\', '/', $this->get('controller'));

        $path = (empty($controller) ? '' : $controller.'/').$this->get('name').'.'.$this->get('format').'.'.$this->get('engine');

        //check for an override
        $themeDir = $this->factory->getSystemPath('currentTheme', true);

        if (!empty($this->parameters['bundle'])) {
            if (!file_exists($template = $themeDir . '/html/' . $this->parameters['bundle'] . '/' . $path)) {
                $template = '@' . $this->get('bundle') . '/Views/' . $path;
            }
        } else {
            $template = $themeDir . '/html/' . $path;
        }

        return $template;
    }
}
