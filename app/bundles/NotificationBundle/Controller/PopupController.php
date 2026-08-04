<?php

namespace Mautic\NotificationBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\HttpFoundation\Response;

final class PopupController extends CommonController
{
    /**
     * @param ModelFactory<object> $modelFactory
     */
    public function __construct(
        protected \Doctrine\Persistence\ManagerRegistry $doctrine,
        protected ModelFactory $modelFactory,
        \Mautic\CoreBundle\Helper\UserHelper $userHelper,
        protected \Mautic\CoreBundle\Helper\CoreParametersHelper $coreParametersHelper,
        protected \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher,
        protected \Mautic\CoreBundle\Translation\Translator $translator,
        private \Mautic\CoreBundle\Service\FlashBag $flashBag,
        private \Symfony\Component\HttpFoundation\RequestStack $requestStack,
        protected \Mautic\CoreBundle\Security\Permissions\CorePermissions $security,
        private readonly AssetsHelper $assetsHelper,
    ) {
        parent::__construct($doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    public function indexAction(): Response
    {
        $this->assetsHelper->addStylesheet('/app/bundles/NotificationBundle/Assets/css/popup/popup.css');
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
