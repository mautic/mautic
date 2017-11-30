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

use Symfony\Component\HttpFoundation\Request;

/**
 * Class CustomTemplateEvent.
 */
class CustomTemplateEvent extends AbstractCustomRequestEvent
{
    /**
     * @var
     */
    protected $template;

    /**
     * @var
     */
    protected $vars;

    /**
     * CustomTemplateEvent constructor.
     *
     * @param Request $request
     * @param         $template
     * @param array   $vars
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

    /**
     * @return mixed
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param mixed $template
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
     * @return mixed
     */
    public function getVars()
    {
        return $this->vars;
    }

    /**
     * @param mixed $vars
     *
     * @return CustomTemplateEvent
     */
    public function setVars($vars)
    {
        $this->vars = $vars;

        return $this;
    }
}
