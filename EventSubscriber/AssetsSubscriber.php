<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\EventSubscriber;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomAssetsEvent;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AssetsSubscriber implements EventSubscriberInterface
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

    public function injectAssets(CustomAssetsEvent $assetsEvent)
    {
        if ($this->config->isPublished()) {
            $assetsEvent->addScript('plugins/GrapesJsBuilderBundle/dist/builder.js');
            // $assetsEvent->addScript('plugins/GrapesJsBuilderBundle/Assets/js/grapes.min.js');
            // $assetsEvent->addScript('plugins/GrapesJsBuilderBundle/Assets/js/grapesjs-preset-newsletter.min.js');
            // $assetsEvent->addScript('plugins/GrapesJsBuilderBundle/Assets/js/grapesjs-preset-webpage.min.js');
            // $assetsEvent->addScript('plugins/GrapesJsBuilderBundle/Assets/js/grapesjs-mjml.min.js');
            // $assetsEvent->addScript('plugins/GrapesJsBuilderBundle/Assets/js/grapesjs-parser-postcss.min.js');
            // $assetsEvent->addScript('plugins/GrapesJsBuilderBundle/Assets/js/grapesjs-preset-mautic.min.js');

            $assetsEvent->addStylesheet('plugins/GrapesJsBuilderBundle/Assets/library/css/builder.css');
            $assetsEvent->addStylesheet('plugins/GrapesJsBuilderBundle/Assets/library/css/grapes.min.css');
            $assetsEvent->addStylesheet('plugins/GrapesJsBuilderBundle/Assets/library/css/grapes-code-editor.min.css');
        }
    }
}
