<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\EventSubscriber;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomAssetsEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;

class AssetsSubscriber extends CommonSubscriber
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_ASSETS => ['injectAssets', 0],
        ];
    }

    /**
     * @param CustomAssetsEvent $assetsEvent
     */
    public function injectAssets(CustomAssetsEvent $assetsEvent)
    {
        if ($this->config->isPublished()) {
            $assetsEvent->addScript('plugins/GrapesJsBuilderBundle/Assets/js/builder.js');
            $assetsEvent->addScript('https://unpkg.com/grapesjs');
            $assetsEvent->addScript('https://unpkg.com/grapesjs-preset-newsletter');
            $assetsEvent->addScript('https://unpkg.com/grapesjs-mjml');
            $assetsEvent->addScript('https://unpkg.com/grapesjs-parser-postcss');

            $assetsEvent->addStylesheet('plugins/GrapesJsBuilderBundle/Assets/css/builder.css');
            $assetsEvent->addStylesheet('https://unpkg.com/grapesjs/dist/css/grapes.min.css');
            $assetsEvent->addStylesheet('https://unpkg.com/grapesjs-preset-newsletter/dist/grapesjs-preset-newsletter.css');
        }
    }
}
