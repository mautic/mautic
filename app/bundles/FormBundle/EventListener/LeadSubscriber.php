<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\FormBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\LeadChangeEvent;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class LeadSubscriber
 *
 * @package Mautic\FormBundle\EventListener
 */
class LeadSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            LeadEvents::TIMELINE_ON_GENERATE => array('onTimelineGenerate', 0),
            LeadEvents::LEAD_POST_MERGE      => array('onLeadMerge', 0)
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
        $eventTypeKey = 'form.submitted';
        $eventTypeName = $this->translator->trans('mautic.form.event.submitted');
        $event->addEventType($eventTypeKey, $eventTypeName);

        $filters = $event->getEventFilters();

        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $lead    = $event->getLead();
        $options = array('ipIds' => array(), 'leadId' => $lead->getId(), 'filters' => $filters);

        /** @var \Mautic\CoreBundle\Entity\IpAddress $ip */
        /*
        foreach ($lead->getIpAddresses() as $ip) {
            $options['ipIds'][] = $ip->getId();
        }
        */

        /** @var \Mautic\FormBundle\Entity\SubmissionRepository $submissionRepository */
        $submissionRepository = $this->factory->getEntityManager()->getRepository('MauticFormBundle:Submission');

        $rows = $submissionRepository->getSubmissions($options);

        $pageModel = $this->factory->getModel('page.page');
        $formModel = $this->factory->getModel('form.form');

        // Add the submissions to the event array
        foreach ($rows as $row) {
            // Convert to local from UTC
            $dtHelper = $this->factory->getDate($row['dateSubmitted'], 'Y-m-d H:i:s', 'UTC');

            $submission = $submissionRepository->getEntity($row['id']);
            $event->addEvent(array(
                'event'     => $eventTypeKey,
                'eventLabel'=> $eventTypeName,
                'timestamp' => $dtHelper->getLocalDateTime(),
                'extra'     => array(
                    'submission' => $submission,
                    'form'  => $formModel->getEntity($row['form_id']),
                    'page'  => $pageModel->getEntity($row['page_id'])
                ),
                'contentTemplate' => 'MauticFormBundle:SubscribedEvents\Timeline:index.html.php',
                'icon'            => 'fa-pencil-square-o'
            ));
        }
    }

    /**
     * @param LeadMergeEvent $event
     */
    public function onLeadMerge(LeadMergeEvent $event)
    {
        $this->factory->getModel('form.submission')->getRepository()->updateLead($event->getLoser()->getId(), $event->getVictor()->getId());
    }
}