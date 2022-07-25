<?php

namespace Mautic\CoreBundle\Templating\Helper;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\SidebarCanvasEvent;
use Symfony\Bundle\FrameworkBundle\Templating\PhpEngine;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Templating\Helper\Helper;

/**
 * Class SidebarCanvasHelper.
 *
 * @deprecated Deprecated in Mautic 4.3. Will be removed in Mautic 5.0
 */
class SidebarCanvasHelper extends Helper
{
    /**
     * @var array
     */
    protected $canvases = ['left', 'main', 'right'];

    /**
     * @var array
     */
    protected $content = [];

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * SidebarCanvasHelper constructor.
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function renderCanvasContent(PhpEngine $templating)
    {
        if ($this->dispatcher->hasListeners(CoreEvents::BUILD_CANVAS_CONTENT)) {
            $event = new SidebarCanvasEvent($templating);
            $this->dispatcher->dispatch(CoreEvents::BUILD_CANVAS_CONTENT, $event);
            $this->content = $event->getCanvasContent();
        }

        $adminMenuContent = $templating['menu']->render('admin');

        if (!empty($adminMenuContent)) {
            $settingsMenu = [
                'header'  => 'mautic.core.settings',
                'content' => '<nav class="nav-sidebar">'.$adminMenuContent.'</nav>',
                'footer'  => '',
            ];

            if (empty($this->content['main'])) {
                //insert settings menu
                $this->content['main'] = $settingsMenu;
            } else {
                $this->content['left'] = $settingsMenu;
            }
        }

        $hasContent = false;
        foreach ($this->canvases as $canvas) {
            if (!isset($this->content[$canvas])) {
                $this->content[$canvas] = false;
            }

            if ($this->content[$canvas]) {
                $hasContent = true;
            }
        }

        if (!$hasContent) {
            $this->content['main'] = [
                'header'  => false,
                'content' => '<div class="mautibot-image"><img class="img-responsive mt-lg" style="margin-right: auto; margin-left: auto;" src="'.MautibotHelper::get('wave').'" /></div>',
                'footer'  => '',
            ];
        }
    }

    /**
     * @return mixed
     */
    public function getLeftContent()
    {
        return $this->content['left'];
    }

    /**
     * @return mixed
     */
    public function getRightContent()
    {
        return $this->content['right'];
    }

    /**
     * @return mixed
     */
    public function getMainContent()
    {
        return $this->content['main'];
    }

    /**
     * @return array
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'canvas';
    }
}
