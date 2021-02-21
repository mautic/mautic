<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\EventSubscriber;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomAssetsEvent;
use Mautic\InstallBundle\Install\InstallService;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AssetsSubscriber implements EventSubscriberInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var InstallService
     */
    private $installer;

    public function __construct(Config $config, InstallService $installer)
    {
        $this->config    = $config;
        $this->installer = $installer;
    }

    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_ASSETS => ['injectAssets', 0],
        ];
    }

    public function injectAssets(CustomAssetsEvent $assetsEvent)
    {
        if (!$this->installer->checkIfInstalled()) {
            return;
        }
        if ($this->config->isPublished()) {
            $assetsEvent->addScript('plugins/GrapesJsBuilderBundle/Assets/library/js/builder.js');
            $assetsEvent->addScript('plugins/GrapesJsBuilderBundle/Assets/library/js/grapes.min.js');
            $assetsEvent->addScript('plugins/GrapesJsBuilderBundle/Assets/library/js/grapesjs-preset-newsletter.min.js');
            $assetsEvent->addScript('plugins/GrapesJsBuilderBundle/Assets/library/js/grapesjs-preset-webpage.min.js');
            $assetsEvent->addScript('plugins/GrapesJsBuilderBundle/Assets/library/js/grapesjs-mjml.min.js');
            $assetsEvent->addScript('plugins/GrapesJsBuilderBundle/Assets/library/js/grapesjs-parser-postcss.min.js');
            $assetsEvent->addScript('plugins/GrapesJsBuilderBundle/Assets/library/js/grapesjs-preset-mautic.min.js');

            $assetsEvent->addStylesheet('plugins/GrapesJsBuilderBundle/Assets/library/css/builder.css');
            $assetsEvent->addStylesheet('plugins/GrapesJsBuilderBundle/Assets/library/css/grapes.min.css');
            $assetsEvent->addStylesheet('plugins/GrapesJsBuilderBundle/Assets/library/css/grapes-code-editor.min.css');
        }
    }
}
