<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class CustomContentEvent.
 */
class CustomContentEvent extends Event
{
    /**
     * @var
     */
    protected $viewName;

    /**
     * @var
     */
    protected $context;

    /**
     * @var
     */
    protected $vars;

    /**
     * @var array
     */
    protected $content = [];

    /**
     * @var array
     */
    protected $templates = [];

    /**
     * CustomContentEvent constructor.
     *
     * @param       $viewName
     * @param       $context
     * @param array $vars
     */
    public function __construct($viewName, $context = null, array $vars = [])
    {
        $this->viewName = $viewName;
        $this->context  = $context;
        $this->vars     = $vars;
    }

    /**
     * Check if the context is applicable.
     *
     * @param $viewName
     * @param $context
     *
     * @return bool
     */
    public function checkContext($viewName, $context)
    {
        return $viewName === $this->viewName && $context === $this->context;
    }

    /**
     * @param $content
     */
    public function addContent($content)
    {
        $this->content[] = $content;
    }

    /**
     * @param       $template
     * @param array $vars
     */
    public function addTemplate($template, array $vars = [])
    {
        $this->templates[$template] = $vars;
    }

    /**
     * @return mixed
     */
    public function getViewName()
    {
        return $this->viewName;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return mixed
     */
    public function getVars()
    {
        return $this->vars;
    }

    /**
     * @return array
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return array
     */
    public function getTemplates()
    {
        return $this->templates;
    }
}
