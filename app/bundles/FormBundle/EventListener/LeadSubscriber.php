<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PageBundle\Model\PageModel;

/**
 * Class LeadSubscriber.
 */
class LeadSubscriber extends CommonSubscriber
{
    /**
     * @var FormModel
     */
    protected $formModel;

    /**
     * @var PageModel
     */
    protected $pageModel;

    /**
     * LeadSubscriber constructor.
     *
     * @param FormModel $formModel
     * @param PageModel $pageModel
     */
    public function __construct(FormModel $formModel, PageModel $pageModel)
    {
        $this->formModel = $formModel;
        $this->pageModel = $pageModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
            LeadEvents::LEAD_POST_MERGE      => ['onLeadMerge', 0],
        ];
    }

    /**
     * Compile events for the lead timeline.
     *
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        // Set available event types
        $eventTypeKey  = 'form.submitted';
        $eventTypeName = $this->translator->trans('mautic.form.event.submitted');
        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup(['formList', 'submissionEventDetails']);

        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        /** @var \Mautic\FormBundle\Entity\SubmissionRepository $submissionRepository */
        $submissionRepository = $this->em->getRepository('MauticFormBundle:Submission');
        $rows                 = $submissionRepository->getSubmissions($event->getQueryOptions());

        // Add total to counter
        $event->addToCounter($eventTypeKey, $rows);

        if (!$event->isEngagementCount()) {
            // Add the submissions to the event array
            foreach ($rows['results'] as $row) {
                // Convert to local from UTC
                $form       = $this->formModel->getEntity($row['form_id']);
                $submission = $submissionRepository->getEntity($row['id']);

                $event->addEvent(
                    [
                        'event'      => $eventTypeKey,
                        'eventId'    => $eventTypeKey.$row['id'],
                        'eventLabel' => [
                            'label' => $form->getName(),
                            'href'  => $this->router->generate('mautic_form_action', ['objectAction' => 'view', 'objectId' => $form->getId()]),
                        ],
                        'eventType' => $eventTypeName,
                        'timestamp' => $row['dateSubmitted'],
                        'extra'     => [
                            'submission' => $submission,
                            'form'       => $form,
                            'page'       => $this->pageModel->getEntity($row['page_id']),
                        ],
                        'contentTemplate' => 'MauticFormBundle:SubscribedEvents\Timeline:index.html.php',
                        'icon'            => 'fa-pencil-square-o',
                        'contactId'       => $row['lead_id'],
                    ]
                );
            }
        }
    }

    /**
     * @param LeadMergeEvent $event
     */
    public function onLeadMerge(LeadMergeEvent $event)
    {
        $this->em->getRepository('MauticFormBundle:Submission')->updateLead($event->getLoser()->getId(), $event->getVictor()->getId());
    }
}
