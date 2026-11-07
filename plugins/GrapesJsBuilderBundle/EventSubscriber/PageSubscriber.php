<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\EventSubscriber;

use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\PageBundle\Entity\Page;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class PageSubscriber
{
    public function __construct(
        private Config $config,
        private GrapesJsBuilderModel $grapesJsBuilderModel,
    ) {
    }

    #[AsEventListener(
        event: EntityPostSave::class,
    )]
    public function onPagePostSave(EntityPostSave $event): void
    {
        $page = $event->getEntity();
        if (!$page instanceof Page) {
            return;
        }

        if (!$this->config->isPublished()) {
            return;
        }

        $this->grapesJsBuilderModel->addOrEditPageEntity($page);
    }
}
