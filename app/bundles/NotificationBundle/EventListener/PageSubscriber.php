<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use Mautic\PluginBundle\Helper\IntegrationHelper;

/**
 * Class PageSubscriber.
 */
class PageSubscriber extends CommonSubscriber
{
    /**
     * @var AssetsHelper
     */
    protected $assetsHelper;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * PageSubscriber constructor.
     *
     * @param AssetsHelper      $assetsHelper
     * @param IntegrationHelper $integrationHelper
     */
    public function __construct(AssetsHelper $assetsHelper, IntegrationHelper $integrationHelper)
    {
        $this->assetsHelper      = $assetsHelper;
        $this->integrationHelper = $integrationHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            PageEvents::PAGE_ON_DISPLAY => ['onPageDisplay', 0],
        ];
    }

    /**
     * @param PageDisplayEvent $event
     */
    public function onPageDisplay(PageDisplayEvent $event)
    {
        $integrationObject = $this->integrationHelper->getIntegrationObject('OneSignal');
        $settings          = $integrationObject->getIntegrationSettings();
        $features          = $settings->getFeatureSettings();

        $script = '';
        if (!in_array('landing_page_enabled', $features)) {
            $script = 'disable_notification = true;';
        }

        $this->assetsHelper->addScriptDeclaration($script, 'onPageDisplay_headClose');
    }
}
