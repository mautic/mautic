<?php

namespace Mautic\NotificationBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PopupController extends CommonController
{
    #[Route('/notification', name: 'mautic_notification_popup', priority: -245)]
    public function indexAction(AssetsHelper $assetsHelper): Response
    {
        $assetsHelper->addStylesheet('/app/bundles/NotificationBundle/Assets/css/popup/popup.css');

        $response = $this->render(
            '@MauticNotification/Popup/index.html.twig',
            [
                'siteUrl' => $this->coreParametersHelper->get('site_url'),
            ]
        );

        $content = $response->getContent();

        $event = new PageDisplayEvent($content, new Page());
        $this->dispatcher->dispatch($event, PageEvents::PAGE_ON_DISPLAY);
        $content = $event->getContent();

        return $response->setContent($content);
    }
}
