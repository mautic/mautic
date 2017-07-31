<?php

namespace MauticPlugin\MauticCrmBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PluginBundle\Helper\IntegrationHelper;
/**
 * Class CustomContentSubscriber.
 */
class CustomContentSubscriber extends CommonSubscriber
{
    /** @var EntityManager */
    protected $em;

    /** @var IntegrationHelper */
    protected $integrationHelper;

    public function __construct(EntityManager $em, IntegrationHelper $integrationHelper)
    {
        $this->em = $em;
        $this->integrationHelper = $integrationHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_CONTENT => ['injectCustomContent'],
        ];

    }

    public function injectCustomContent(CustomContentEvent $event)
    {
        $pipedriveIntegration  = $this->integrationHelper->getIntegrationObject('Pipedrive');
        if ($pipedriveIntegration->isDealSupportEnabled()) {


            $parameters = $event->getVars();

            if ($event->checkContext('MauticLeadBundle:Lead:lead.html.php', 'tabs')) {
                if (isset($parameters['lead'])) {
                    $lead = $parameters['lead'];
                    $deals = $this->em->getRepository('MauticCrmBundle:PipedriveDeal')->findByLead($lead);
                    $event->addTemplate('MauticCrmBundle:Integration:pipedrive_lead_tab.html.php', [
                        'countDeals' => count($deals),
                    ]);
                }
            }

            if ($event->checkContext('MauticLeadBundle:Lead:lead.html.php', 'tabs.content')) {
                if (isset($parameters['lead'])) {
                    $lead = $parameters['lead'];
                    $deals = $this->em->getRepository('MauticCrmBundle:PipedriveDeal')->findByLead($lead);

                    $event->addTemplate('MauticCrmBundle:Integration:pipedrive_lead_tab.content.html.php', [
                        'deals'      => $deals,
                        'tmpl'       => 'index',
                    ]);
                }
            }
        }
    }

}
