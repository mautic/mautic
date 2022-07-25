<?php

namespace Mautic\NotificationBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;

class PopupController extends CommonController
{
    public function indexAction()
    {
        /** @var \Mautic\CoreBundle\Templating\Helper\AssetsHelper $assetsHelper */
        $assetsHelper = $this->container->get('templating.helper.assets');
        $assetsHelper->addStylesheet('/app/bundles/NotificationBundle/Assets/css/popup/popup.css');

        $response = $this->render(
            'MauticNotificationBundle:Popup:index.html.php',
            [
                'siteUrl' => $this->coreParametersHelper->get('site_url'),
            ]
        );

        $content = $response->getContent();

        $event = new PageDisplayEvent($content, new Page());
        $this->dispatcher->dispatch(PageEvents::PAGE_ON_DISPLAY, $event);
        $content = $event->getContent();

        return $response->setContent($content);
    }
}
