<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\NotificationBundle\NotificationEvents;

/**
 * Class CampaignSubscriber
 *
 * @package MauticDynamicContentBundle
 */
class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * CampaignSubscriber constructor.
     *
     * @param LeadModel $leadModel
     */
    public function __construct(MauticFactory $factory, LeadModel $leadModel)
    {
        $this->leadModel = $leadModel;

        parent::__construct($factory);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD => ['onCampaignBuild', 0],
            DynamicContentEvents::ON_CAMPAIGN_TRIGGER_DECISION => ['onCampaignTriggerDecision', 0]
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
       $event->addLeadDecision(
            'dwc.push_content',
            [
                'label'           => 'mautic.notification.campaign.send_notification',
                'description'     => 'mautic.notification.campaign.send_notification.tooltip',
                'eventName'       => DynamicContentEvents::ON_CAMPAIGN_TRIGGER_DECISION,
                'formType'        => 'notificationsend_list',
                'formTypeOptions' => ['update_select' => 'campaignevent_properties_dwc'],
                'formTheme'       => 'MauticDynamicContentBundle:FormTheme\DynamicContentPushList'
            ]
        );
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerDecision(CampaignExecutionEvent $event)
    {
        // todo
    }
}