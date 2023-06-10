<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class CustomContentEvent extends Event
{
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
     * @param string $viewName
     */
    public function __construct(protected $viewName, protected ?string $context = null, array $vars = [])
    {
        $this->vars     = $vars;
    }

    /**
     * Check if the context is applicable.
     *
     * @param string $viewName
     *
     * @return bool
     */
    public function checkContext($viewName, ?string $context)
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

    public function getContext(): ?string
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
