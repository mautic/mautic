<?php
namespace MauticPlugin\MauticMessengerBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Entity\Hit;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Event\CampaignLeadChangeEvent;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use MauticPlugin\MauticMessengerBundle\MessengerEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Helper\CookieHelper;

class CampaignSubscriber extends CommonSubscriber
{

    /**
     * @var $campaignModel
     */
    protected $campaignModel;
    /**
     * @var $cookieHelper ;
     */
    protected $cookieHelper;

    /**
     * @var $db
     */
    protected $db;

    /**
     * @var $request
     */
    protected $request;

    /**
     * @var $sesion
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
     * @var $pageModel
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
        $this->leadModel = $leadModel;
        $this->session = $session;
        $this->pageModel = $pageModel;
        $this->request = $requestStack->getCurrentRequest();
        $this->db = $db;
        $this->cookieHelper = $cookieHelper;
        $this->campaignModel = $campaignModel;
    }


    static public function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD => ['onCampaignBuild', 0],
            MessengerEvents::MESSENGER_ON_SEND => array('onCampaignTriggerAction', 0),

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
            'label' => 'plugin.messenger.send_to_messanger',
            'eventName' => MessengerEvents::ON_CAMPAIGN_TRIGGER_ACTION,
            'formType' => 'messengerevent_send_to_messenger',
            'channel' => 'messenger',
            'channelIdField' => 'id',
        ];
        $event->addAction('messanger.send_to_messanger', $action);

    }


    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerAction(CampaignExecutionEvent $event) {
        $lead = $event->getLead();
        $eventConfig = $event->getConfig();
        $eventDetails = $event->getEventDetails();


    }
}
