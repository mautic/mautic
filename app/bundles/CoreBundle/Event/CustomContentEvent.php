<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class CustomContentEvent extends Event
{
    private array $content = [];

    private array $templates = [];

    /**
     * @param string      $viewName
     * @param string|null $context
     */
    public function __construct(
        private $viewName,
        private $context = null,
        private readonly array $vars = [],
    ) {
    }

    /**
     * Check if the context is applicable.
     *
     * @param string      $viewName
     * @param string|null $context
     */
    public function checkContext($viewName, $context): bool
    {
        return $viewName === $this->viewName && $context === $this->context;
    }

    /**
     * @param string $content
     */
    public function addContent($content): void
    {
        $this->content[] = $content;
    }

    /**
     * @param string $template
     */
    public function addTemplate($template, array $vars = []): void
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

    public function getVars(): array
    {
        return $this->vars;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function getTemplates(): array
    {
        return $this->templates;
    }
}
