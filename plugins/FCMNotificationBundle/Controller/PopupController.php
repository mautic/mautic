<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\FCMNotificationBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;

class PopupController extends CommonController
{
    public function indexAction()
    {
        /** @var \Mautic\CoreBundle\Templating\Helper\AssetsHelper $assetsHelper */
        $assetsHelper = $this->factory->getHelper('template.assets');
        $assetsHelper->addStylesheet('/plugins/FCMNotificationBundle/Assets/css/popup/popup.css');

        $this->integrationHelper = $this->get('mautic.helper.integration');       
        $integration = $this->integrationHelper->getIntegrationObject('FCM');

        $settings          = $integration->getIntegrationSettings();
        $features          = $settings->getSupportedFeatures();
        $featureSettings   = $settings->getFeatureSettings();        

        $response = $this->render(
            'FCMNotificationBundle:Popup:index.html.php',
            [
                'siteUrl' => $this->coreParametersHelper->getParameter('site_url'),
                'icon'  => $integration->getIcon(),
                'sampleNotificationTitle'  => $featureSettings['sample_notification_title'],
                'sampleNotificationText'  => $featureSettings['sample_notification_text']
            ]
        );

        $content = $response->getContent();

        $event = new PageDisplayEvent($content, new Page());
        $this->dispatcher->dispatch(PageEvents::PAGE_ON_DISPLAY, $event);
        $content = $event->getContent();

        return $response->setContent($content);
    }
}
