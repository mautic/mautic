<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class LeadSubscriber
 */
class LeadSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            LeadEvents::TIMELINE_ON_GENERATE => array('onTimelineGenerate', 0)
        );
    }

    /**
     * Compile events for the lead timeline
     *
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        // Set available event types
        $eventTypeKey = 'page.hit';
        $eventTypeName = $this->translator->trans('mautic.page.event.hit');
        $event->addEventType($eventTypeKey, $eventTypeName);

        $filters = $event->getEventFilters();

        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $lead    = $event->getLead();
        $options = array('ipIds' => array(), 'filters' => $filters);

        /** @var \Mautic\CoreBundle\Entity\IpAddress $ip */
        /*
        foreach ($lead->getIpAddresses() as $ip) {
            $options['ipIds'][] = $ip->getId();
        }
        */

        /** @var \Mautic\PageBundle\Entity\HitRepository $hitRepository */
        $hitRepository = $this->factory->getEntityManager()->getRepository('MauticPageBundle:Hit');

        $hits = $hitRepository->getLeadHits($lead->getId(), $options);

        $model = $this->factory->getModel('page.page');

        // Add the hits to the event array
        foreach ($hits as $hit) {
            if ($hit['source'] && $hit['sourceId']) {
                $sourceModel = $this->factory->getModel($hit['source'] . '.' . $hit['source']);
                $sourceEntity = $sourceModel->getEntity($hit['sourceId']);
                if (method_exists($sourceEntity, 'getName')) {
                    $hit['sourceName'] = $sourceEntity->getName();
                }
                if (method_exists($sourceEntity, 'getTitle')) {
                    $hit['sourceName'] = $sourceEntity->getTitle();
                }
            }
            $event->addEvent(array(
                'event'     => $eventTypeKey,
                'eventLabel' => $eventTypeName,
                'timestamp' => $hit['dateHit'],
                'extra'     => array(
                    'page' => $model->getEntity($hit['page_id']),
                    'hit'  => $hit
                ),
                'contentTemplate' => 'MauticPageBundle:SubscribedEvents\Timeline:index.html.php'
            ));
        }
    }
}
