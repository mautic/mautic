<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Component\HttpFoundation\Request;

class CustomTemplateEvent extends AbstractCustomRequestEvent
{
    /**
     * @var string|null
     */
    protected $template;

    /**
     * @var array
     */
    protected $vars;

    /**
     * @param Request     $request
     * @param string|null $template
     */
    public function __construct(Request $request = null, $template = null, array $vars = [])
    {
        parent::__construct($request);

        if (empty($template)) {
            throw new \InvalidArgumentException('$template is required');
        }

        $this->template = $template;
        $this->vars     = $vars;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * @return CustomTemplateEvent
     */
    public function setTemplate(?string $template)
    {
        $this->template = $template;

        $this->stopPropagation();

        return $this;
    }

    /**
     * @return array
     */
    public function getVars()
    {
        return $this->vars;
    }

    /**
     * @param array<string, mixed> $vars
     *
     * @return CustomTemplateEvent
     */
    public function setVars($vars)
    {
        $this->vars = $vars;

        return $this;
    }
}
