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
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class PageSubscriber.
 */
class PageSubscriber extends CommonSubscriber
{
    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * @var AssetsHelper
     */
    protected $assetsHelper;

    /**
     * PageSubscriber constructor.
     *
     * @param AssetsHelper         $assetsHelper
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(AssetsHelper $assetsHelper, CoreParametersHelper $coreParametersHelper)
    {
        $this->assetsHelper         = $assetsHelper;
        $this->coreParametersHelper = $coreParametersHelper;
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

        $script = '';
        if (!$this->coreParametersHelper->getParameter('notification_landing_page_enabled')) {
            $script = 'disable_notification = true;';
        }

        $this->assetsHelper->addScriptDeclaration($script, 'onPageDisplay_headClose');
    }
}
