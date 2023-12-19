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
     * @param string|null $template
     */
    public function __construct(
        Request $request = null,
        $template = null,
        protected array $vars = []
    ) {
        parent::__construct($request);

        if (empty($template)) {
            throw new \InvalidArgumentException('$template is required');
        }

        $this->template = $template;
    }

    /**
     * @return string|null
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string|null $template
     *
     * @return CustomTemplateEvent
     */
    public function setTemplate($template)
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
