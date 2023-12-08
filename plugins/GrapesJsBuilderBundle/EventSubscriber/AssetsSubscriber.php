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
<<<<<<< HEAD
    public function __construct(private Config $config, private InstallService $installer)
=======
    private \MauticPlugin\GrapesJsBuilderBundle\Integration\Config $config;

    private \Mautic\InstallBundle\Install\InstallService $installer;

    public function __construct(Config $config, InstallService $installer)
>>>>>>> 11b4805f88 ([type-declarations] Re-run rector rules on plugins, Report, Sms, User, Lead, Dynamic, Config bundles)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_ASSETS => ['injectAssets', 0],
        ];
    }

    public function injectAssets(CustomAssetsEvent $assetsEvent): void
    {
        if (!$this->installer->checkIfInstalled()) {
            return;
        }
        if ($this->config->isPublished()) {
            $assetsEvent->addScript('plugins/GrapesJsBuilderBundle/Assets/library/js/dist/builder.js');
            $assetsEvent->addStylesheet('plugins/GrapesJsBuilderBundle/Assets/library/js/dist/builder.css');
        }
    }
}
