<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\EventSubscriber;

use Mautic\PageBundle\Event\PageEvent;
use Mautic\PageBundle\PageEvents;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;

final readonly class PageSubscriber
{
    public function __construct(
        private Config $config,
        private GrapesJsBuilderModel $grapesJsBuilderModel,
    ) {
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: PageEvents::PAGE_POST_SAVE,
        priority: 0
    )]
    public function onPagePostSave(PageEvent $event): void
    {
        if (!$this->config->isPublished()) {
            return;
        }

        $this->grapesJsBuilderModel->addOrEditPageEntity($event->getPage());
    }
}
