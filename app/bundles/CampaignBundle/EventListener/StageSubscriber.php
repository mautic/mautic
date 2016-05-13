<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\StageBundle\Event\StageBuilderEvent;
use Mautic\StageBundle\StageEvents;

/**
 * Class StageSubscriber
 */
class StageSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return array(
            StageEvents::STAGE_ON_BUILD   => array('onTriggerBuild', 0)
        );
    }

    /**
     * @param TriggerBuilderEvent $event
     */
    public function onTriggerBuild(StageBuilderEvent $event)
    {
        $changeLists = array(
            'group'       => 'mautic.campaign.stage.trigger',
            'label'       => 'mautic.campaign.stage.trigger.changecampaigns',
            'callback'    => array('\\Mautic\\CampaignBundle\\Helper\\CampaignEventHelper', 'addRemoveLead'),
            'formType'    => 'campaignevent_addremovelead'
        );

        //$event->addEvent('campaign.changecampaign', $changeLists);
    }
}
