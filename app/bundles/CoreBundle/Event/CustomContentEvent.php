<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class CustomContentEvent extends Event
{
    /**
     * @var string[]
     */
    private array $content = [];

    /**
     * @var array<mixed, array<string, string|mixed[]>>
     */
    private array $templates = [];

    public function __construct(
        private string $viewName,
        private ?string $context = null,
        private readonly array $vars = [],
    ) {
    }

    /**
     * Check if the context is applicable.
     *
     * @param string|null $context
     */
    public function checkContext(string $viewName, string $context): bool
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
    public function addTemplate(string $template, array $vars = []): void
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

    /**
     * @return string[]
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * @return array<mixed, array<string, string|mixed[]>>
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }
}
