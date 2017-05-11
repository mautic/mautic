<?php
/**
 * @copyright   2016 Webmecanik
 * @author      Webmecanik
 *
 * @link        http://www.webmecanik.com
 */

namespace MauticPlugin\MauticCrmBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event as Events;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class LeadSubscriber.
 */
class LeadSubscriber extends CommonSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_POST_SAVE      => ['onLeadPostSave', 0],
            LeadEvents::LEAD_COMPANY_CHANGE => ['onLeadCompanyChange', 0],
            LeadEvents::LEAD_PRE_DELETE     => ['onLeadPreDelete', 0],
        ];
    }

    /**
     * If the "INES full-sync" mode is enabled :
          * Enqueue a lead in sync queue.
     *
     * @param Events\LeadEvent $event
     */
    public function onLeadPostSave(Events\LeadEvent $event)
    {
        $lead = $event->getLead();
        if ($lead === false) {
            return;
        }
        $inesIntegration = $this->dispatcher->getContainer()->get('mautic.helper.integration')->getIntegrationObject('Ines');
        $inesIntegration->enqueueLead($lead);
    }

    /**
     * Reset INES keys if the company of a lead changes.
     */
    public function onLeadCompanyChange($event)
    {
        $lead            = $event->getLead();
        $inesIntegration = $this->dispatcher->getContainer()->get('mautic.helper.integration')->getIntegrationObject('Ines');
        $inesIntegration->setInesKeysToLead($lead, 0, 0);
        $inesIntegration->enqueueLead($lead);
    }

    /**
     * If the "INES full-sync" mode is enabled :
     * Enqueue a lead in the delete queue.
     *
     * @param Events\LeadEvent $event
     */
    public function onLeadPreDelete(Events\LeadEvent $event)
    {
        $lead            = $event->getLead();
        $inesIntegration = $this->dispatcher->getContainer()->get('mautic.helper.integration')->getIntegrationObject('Ines');
        $inesIntegration->enqueueLead($lead, 'DELETE');
    }
}
