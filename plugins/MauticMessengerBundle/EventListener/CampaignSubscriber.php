<?php

namespace MauticPlugin\MauticMessengerBundle\EventListener;

use Doctrine\DBAL\Connection;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Model\PageModel;
use MauticPlugin\MauticMessengerBundle\MessengerEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var
     */
    protected $campaignModel;
    /**
     * @var ;
     */
    protected $cookieHelper;

    /**
     * @var
     */
    protected $db;

    /**
     * @var
     */
    protected $request;

    /**
     * @var
     */
    protected $sesion;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var EventModel
     */
    protected $campaignEventModel;

    /**
     * @var
     */
    protected $pageModel;

    /**
     * CampaignSubscriber constructor.
     *
     * @param EventModel $campaignEventModel
     */
    public function __construct(
        EventModel $campaignEventModel,
        LeadModel $leadModel,
        Session $session,
        PageModel $pageModel,
        RequestStack $requestStack,
        Connection $db,
        CookieHelper $cookieHelper,
        CampaignModel $campaignModel
    ) {
        $this->campaignEventModel = $campaignEventModel;
        $this->leadModel          = $leadModel;
        $this->session            = $session;
        $this->pageModel          = $pageModel;
        $this->request            = $requestStack->getCurrentRequest();
        $this->db                 = $db;
        $this->cookieHelper       = $cookieHelper;
        $this->campaignModel      = $campaignModel;
    }

    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD  => ['onCampaignBuild', 0],
            MessengerEvents::MESSENGER_ON_SEND => ['onCampaignTriggerAction', 0],

        ];
    }

    /**
     * Add event triggers and actions.
     *
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $action = [
            'label'          => 'plugin.messenger.send_to_messanger',
            'eventName'      => MessengerEvents::ON_CAMPAIGN_TRIGGER_ACTION,
            'formType'       => 'messenger_send_to_messenger',
            'channel'        => 'messenger',
            'channelIdField' => 'id',
        ];
        $event->addAction('messanger.send_to_messanger', $action);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerAction(CampaignExecutionEvent $event)
    {
        $lead         = $event->getLead();
        $eventConfig  = $event->getConfig();
        $eventDetails = $event->getEventDetails();
    }
}
