<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageHitEvent;
use Mautic\PageBundle\Helper\TrackingHelper;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\PageEvents;

/**
 * Class CampaignSubscriber.
 */
class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var PageModel
     */
    protected $pageModel;

    /**
     * @var EventModel
     */
    protected $campaignEventModel;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var TrackingHelper
     */
    protected $trackingHelper;

    /**
     * CampaignSubscriber constructor.
     *
     * @param PageModel      $pageModel
     * @param EventModel     $campaignEventModel
     * @param LeadModel      $leadModel
     * @param TrackingHelper $trackingHelper
     */
    public function __construct(PageModel $pageModel, EventModel $campaignEventModel, LeadModel $leadModel, TrackingHelper $trackingHelper)
    {
        $this->pageModel          = $pageModel;
        $this->campaignEventModel = $campaignEventModel;
        $this->leadModel          = $leadModel;
        $this->trackingHelper     = $trackingHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD        => ['onCampaignBuild', 0],
            PageEvents::PAGE_ON_HIT                  => ['onPageHit', 0],
            PageEvents::ON_CAMPAIGN_TRIGGER_DECISION => [
                ['onCampaignTriggerDecision', 0],
                ['onCampaignTriggerDecisionDeviceHit', 1],
            ],
            PageEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerAction', 0],
        ];
    }

    /**
     * Add event triggers and actions.
     *
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        //Add trigger
        $pageHitTrigger = [
            'label'          => 'mautic.page.campaign.event.pagehit',
            'description'    => 'mautic.page.campaign.event.pagehit_descr',
            'formType'       => 'campaignevent_pagehit',
            'eventName'      => PageEvents::ON_CAMPAIGN_TRIGGER_DECISION,
            'channel'        => 'page',
            'channelIdField' => 'pages',
        ];
        $event->addDecision('page.pagehit', $pageHitTrigger);

        //Add trigger
        $deviceHitTrigger = [
            'label'          => 'mautic.page.campaign.event.devicehit',
            'description'    => 'mautic.page.campaign.event.devicehit_descr',
            'formType'       => 'Mautic\LeadBundle\Form\Type\CampaignEventLeadDeviceType',
            'eventName'      => PageEvents::ON_CAMPAIGN_TRIGGER_DECISION,
            'channel'        => 'page',
            'channelIdField' => 'pages',
        ];
        $event->addDecision('page.devicehit', $deviceHitTrigger);

        $trackingServices = $this->trackingHelper->getEnabledServices();
        if (!empty($trackingServices)) {
            $action = [
                'label'                  => 'mautic.page.tracking.pixel.event.send',
                'description'            => 'mautic.page.tracking.pixel.event.send_desc',
                'eventName'              => PageEvents::ON_CAMPAIGN_TRIGGER_ACTION,
                'formType'               => 'tracking_pixel_send_action',
                'connectionRestrictions' => [
                    'anchor' => [
                        'decision.inaction',
                    ],
                    'source' => [
                        'decision' => [
                            'page.pagehit',
                        ],
                    ],
                ],
            ];
            $event->addAction('tracking.pixel.send', $action);
        }
    }

    /**
     * Trigger actions for page hits.
     *
     * @param PageHitEvent $event
     */
    public function onPageHit(PageHitEvent $event)
    {
        $hit       = $event->getHit();
        $channel   = 'page';
        $channelId = null;
        if ($redirect = $hit->getRedirect()) {
            $channel   = 'page.redirect';
            $channelId = $redirect->getId();
        } elseif ($page = $hit->getPage()) {
            $channelId = $page->getId();
        }
        $this->campaignEventModel->triggerEvent('page.pagehit', $hit, $channel, $channelId);
        $this->campaignEventModel->triggerEvent('page.devicehit', $hit, $channel, $channelId);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerDecisionDeviceHit(CampaignExecutionEvent $event)
    {
        $eventDetails = $event->getEventDetails();
        $config       = $event->getConfig();
        $lead         = $event->getLead();

        if (!$event->checkContext('page.devicehit')) {
            return false;
        }

        $deviceRepo = $this->leadModel->getDeviceRepository();
        $result     = false;

        $deviceId     = $eventDetails->getDeviceStat() ? $eventDetails->getDeviceStat()->getId() : null;
        $deviceType   = $config['device_type'];
        $deviceBrands = $config['device_brand'];
        $deviceOs     = $config['device_os'];

        if (!empty($deviceType)) {
            $result = false;
            if (!empty($deviceRepo->getDevice($lead, $deviceType, null, null, null, $deviceId))) {
                $result = true;
            }
        }

        if (!empty($deviceBrands)) {
            $result = false;
            if (!empty($deviceRepo->getDevice($lead, null, $deviceBrands, null, null, $deviceId))) {
                $result = true;
            }
        }

        if (!empty($deviceOs)) {
            $result = false;
            if (!empty($deviceRepo->getDevice($lead, null, null, null, $deviceOs, $deviceId))) {
                $result = true;
            }
        }

        return $event->setResult($result);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerDecision(CampaignExecutionEvent $event)
    {
        $eventDetails = $event->getEventDetails();
        $config       = $event->getConfig();

        if (!$event->checkContext('page.pagehit')) {
            return false;
        }

        if ($eventDetails == null) {
            return true;
        }
        $pageHit = $eventDetails->getPage();

        // Check Landing Pages
        if ($pageHit instanceof Page) {
            list($parent, $children) = $pageHit->getVariants();
            //use the parent (self or configured parent)
            $pageHitId = $parent->getId();
        } else {
            $pageHitId = 0;
        }

        $limitToPages = (isset($config['pages'])) ? $config['pages'] : [];

        $urlMatches = [];

        // Check Landing Pages URL or Tracing Pixel URL
        if (isset($config['url']) && $config['url']) {
            $pageUrl     = $eventDetails->getUrl();
            $limitToUrls = explode(',', $config['url']);

            foreach ($limitToUrls as $url) {
                $url              = trim($url);
                $urlMatches[$url] = fnmatch($url, $pageUrl);
            }
        }

        $refererMatches = [];

        // Check Landing Pages URL or Tracing Pixel URL
        if (isset($config['referer']) && $config['referer']) {
            $refererUrl      = $eventDetails->getReferer();
            $limitToReferers = explode(',', $config['referer']);

            foreach ($limitToReferers as $referer) {
                $referer                  = trim($referer);
                $refererMatches[$referer] = fnmatch($referer, $refererUrl);
            }
        }

        // **Page hit is true if:**
        // 1. no landing page is set and no URL rule is set
        $applyToAny = (empty($config['url']) && empty($config['referer']) && empty($limitToPages));

        // 2. some landing pages are set and page ID match
        $langingPageIsHit = (!empty($limitToPages) && in_array($pageHitId, $limitToPages));

        // 3. URL rule is set and match with URL hit
        $urlIsHit = (!empty($config['url']) && in_array(true, $urlMatches));

        // 3. URL rule is set and match with URL hit
        $refererIsHit = (!empty($config['referer']) && in_array(true, $refererMatches));

        if ($applyToAny || $langingPageIsHit || $urlIsHit || $refererIsHit) {
            return $event->setResult(true);
        }

        return $event->setResult(false);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerAction(CampaignExecutionEvent $event)
    {
        $config = $event->getConfig();
        if (empty($config['services'])) {
            return $event->setResult(false);
        }

        $values = [];
        foreach ($config['services'] as $service) {
            $values[$service][] = ['category' => $config['category'], 'action' => $config['action'], 'label' => $config['label']];
        }
        $this->trackingHelper->updateSession($values);

        return $event->setResult(true);
    }
}
