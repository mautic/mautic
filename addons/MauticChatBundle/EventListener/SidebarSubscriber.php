<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticChatBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\SidebarCanvasEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Symfony\Component\HttpKernel\Controller\ControllerReference;

/**
 * Class SidebarSubscriber
 */
class SidebarSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            CoreEvents::BUILD_CANVAS_CONTENT => array('onCanvasBuild', 0)
        );
    }

    /**
     * Add chat to sidebar
     *
     * @param SidebarCanvasEvent $event
     *
     * @return void
     */
    public function onCanvasBuild(SidebarCanvasEvent $event)
    {
        $templating = $event->getTemplating();
        $event->pushToMainCanvas(array(
            'header'  => 'mautic.chat.sidebar.communication',
            'content' => $templating['actions']->render(new ControllerReference('MauticChatBundle:Default:index')),
            'footer'  => ''
        ));

        $event->pushToRightCanvas(array(
            'header'  => 'mautic.chat.sidebar.chat',
            'content' => '<ul class="media-list media-list-bubble"></ul>',
            'footer'  => $templating->render('MauticChatBundle:Default:footer.html.php')
        ));
    }
}
