<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Component\HttpFoundation\Request;

class CustomTemplateEvent extends AbstractCustomRequestEvent
{
    protected string $template;

    /**
     * @param array<string, string> $vars
     */
    public function __construct(
        Request $request = null,
        string $template = '',
        protected array $vars = [],
    ) {
        parent::__construct($request);

        if ('' === $template) {
            throw new \InvalidArgumentException('$template is required');
        }

        $this->template = $template;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): self
    {
        $this->template = $template;

        $this->stopPropagation();

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getVars(): array
    {
        return $this->vars;
    }

    /**
     * @param array<string, string> $vars
     */
    public function setVars(array $vars): self
    {
        $this->vars = $vars;

        return $this;
    }
}
