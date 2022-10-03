<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class CustomContentEvent extends Event
{
    /**
     * @var string
     */
    protected $viewName;

    /**
     * @var string|null
     */
    protected $context;

    /**
     * @var array
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
     * @param string      $viewName
     * @param string|null $context
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
     * @param string      $viewName
     * @param string|null $context
     *
     * @return bool
     */
    public function checkContext($viewName, $context)
    {
        return $viewName === $this->viewName && $context === $this->context;
    }

    /**
     * @param string $content
     */
    public function addContent($content)
    {
        $this->content[] = $content;
    }

    /**
     * @param string $template
     */
    public function addTemplate($template, array $vars = [])
    {
        $this->templates[] = [
            'template' => $template,
            'vars'     => $vars,
        ];
    }

    /**
     * @return mixed
     */
    public function getViewName()
    {
        return $this->viewName;
    }

    /**
     * @return string|null
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return array
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
