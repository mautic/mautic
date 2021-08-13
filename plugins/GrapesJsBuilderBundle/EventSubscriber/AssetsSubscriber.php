<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\EventSubscriber;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomAssetsEvent;
use Mautic\InstallBundle\Install\InstallService;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

class AssetsSubscriber implements EventSubscriberInterface
{
    const FIREWALL_CONTEXT_PUBLIC = "security.firewall.map.context.public";

    /**
     * @var Config
     */
    private $config;

    /**
     * @var InstallService
     */
    private $installer;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        Config $config,
        InstallService $installer,
        RequestStack $requestStack
    ) {
        $this->config    = $config;
        $this->installer = $installer;
        $this->requestStack       = $requestStack;
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
        
        // dont load js and css for public (landingpage) requests
        $request = $this->requestStack->getCurrentRequest();
        if ($this->isPublicRequest($request)) {
            return;
        }
            
        if ($this->config->isPublished()) {
            $assetsEvent->addScript('plugins/GrapesJsBuilderBundle/Assets/library/js/dist/builder.js');
            $assetsEvent->addStylesheet('plugins/GrapesJsBuilderBundle/Assets/library/js/dist/builder.css');
        }
    }

    /**
     * Check if the current route is publicly accessible
     *
     * @return boolean
     */
    private function isPublicRequest(Request $request)
    {
        return ($request->attributes->get("_firewall_context") === AssetsSubscriber::FIREWALL_CONTEXT_PUBLIC);
    }
}
