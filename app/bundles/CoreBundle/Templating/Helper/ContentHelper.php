<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Templating\Helper\Helper;

class ContentHelper extends Helper
{
    /**
     * @var DelegatingEngine
     */
    protected $templating;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * UIHelper constructor.
     *
     * @param DelegatingEngine         $templating
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(DelegatingEngine $templating, EventDispatcherInterface $dispatcher)
    {
        $this->templating = $templating;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatch an event to collect custom content.
     *
     * @param       $context  Context of the content requested for the viewName
     * @param array $vars     Templating vars
     * @param       $viewName The main identifier for the content requested. Will be etracted from $vars if get_
     *defined
     *
     * @return string
     */
    public function getCustomContent($context = null, array $vars = [], $viewName = null)
    {
        if (null === $viewName) {
            if (empty($vars['mauticTemplate'])) {
                return '';
            }

            $viewName = $vars['mauticTemplate'];
        }

        /** @var ContentEvent $event */
        $event = $this->dispatcher->dispatch(
            CoreEvents::VIEW_INJECT_CUSTOM_CONTENT,
            new CustomContentEvent($viewName, $context, $vars)
        );

        $content = $event->getContent();

        if ($templates = $event->getTemplates()) {
            foreach ($templates as $template => $templateVars) {
                $content[] = $this->templating->render($template, array_merge($vars, $templateVars));
            }
        }

        return implode("\n\n", $content);
    }

    /**
     * Replaces HTML script tags with non HTML tags so the JS inside them won't execute and will be readable.
     *
     * @param string $html
     *
     * @return string
     */
    public function showScriptTags($html)
    {
        return str_replace(['<script>', '</script>'], ['[script]', '[/script]'], $html);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'content';
    }
}
