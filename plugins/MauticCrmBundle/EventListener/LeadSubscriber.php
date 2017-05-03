<?php
/**
 * @copyright   2016 Webmecanik
 * @author      Webmecanik
 * @link        http://www.webmecanik.com
 */

namespace MauticPlugin\MauticCrmBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Event as Events;

/**
 * Class LeadSubscriber
 */
class LeadSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return array(
            LeadEvents::LEAD_POST_SAVE  => array('onLeadPostSave', 0),
			LeadEvents::LEAD_COMPANY_CHANGE  => array('onLeadCompanyChange', 0),
            LeadEvents::LEAD_PRE_DELETE  => array('onLeadPreDelete', 0)
        );
    }


	/**
	 * If the "INES full-sync" mode is enabled :
     * Enqueue a lead in sync queue
     *
     * @param Events\LeadEvent $event
     */
    public function onLeadPostSave(Events\LeadEvent $event)
    {
		$lead = $event->getLead();
        if ($lead === false) {
            return;
        }
		$inesIntegration = $this->factory->getHelper('integration')->getIntegrationObject('Ines');
		$inesIntegration->enqueueLead($lead);
	}


	/**
	 * Reset INES keys if the company of a lead changes
	 */
	public function onLeadCompanyChange($event)
	{
		$lead = $event->getLead();
		$inesIntegration = $this->factory->getHelper('integration')->getIntegrationObject('Ines');
		$inesIntegration->setInesKeysToLead($lead, 0, 0);
		$inesIntegration->enqueueLead($lead);
	}


	/**
	 * If the "INES full-sync" mode is enabled :
	 * Enqueue a lead in the delete queue
	 *
	 * @param Events\LeadEvent $event
	 */
	public function onLeadPreDelete(Events\LeadEvent $event)
	{
		$lead = $event->getLead();
		$inesIntegration = $this->factory->getHelper('integration')->getIntegrationObject('Ines');
		$inesIntegration->enqueueLead($lead, 'DELETE');
	}


}
