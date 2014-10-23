<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\EventListener;

use Mautic\ApiBundle\Event\RouteEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\FormEvents;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Event\LeadTimelineEvent;

/**
 * Class FormSubscriber
 *
 * @package Mautic\FormBundle\EventListener
 */
class FormSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            CoreEvents::GLOBAL_SEARCH        => array('onGlobalSearch', 0),
            CoreEvents::BUILD_COMMAND_LIST   => array('onBuildCommandList', 0),
            FormEvents::FORM_POST_SAVE       => array('onFormPostSave', 0),
            FormEvents::FORM_POST_DELETE     => array('onFormDelete', 0),
            LeadEvents::TIMELINE_ON_GENERATE => array('onTimelineGenerate', 0)
        );
    }

    /**
     * @param MauticEvents\GlobalSearchEvent $event
     */
    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event)
    {
        $str = $event->getSearchString();
        if (empty($str)) {
            return;
        }

        $security   = $this->security;
        $filter     = array("string" => $str, "force" => '');

        $permissions = $security->isGranted(
            array('form:forms:viewown', 'form:forms:viewother'),
            'RETURN_ARRAY'
        );
        if ($permissions['form:forms:viewown'] || $permissions['form:forms:viewother']) {
            //only show own forms if the user does not have permission to view others
            if (!$permissions['form:forms:viewother']) {
                $filter['force'] = array(
                    array('column' => 'f.createdBy', 'expr' => 'eq', 'value' => $this->factory->getUser())
                );
            }

            $forms = $this->factory->getModel('form.form')->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $filter
                ));

            if (count($forms) > 0) {
                $formResults = array();
                $dateForm = $this->factory->getParameter('date_format_full');
                foreach ($forms as $form) {
                    $formResults[] = $this->templating->renderResponse(
                        'MauticFormBundle:Search:form.html.php',
                        array('form' => $form)
                    )->getContent();
                }
                if (count($forms) > 5) {
                    $formResults[] = $this->templating->renderResponse(
                        'MauticFormBundle:Search:form.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($forms) - 5)
                        )
                    )->getContent();
                }
                $formResults['count'] = count($forms);
                $event->addResults('mautic.form.form.header.index', $formResults);
            }
        }
    }

    /**
     * @param MauticEvents\CommandListEvent $event
     */
    public function onBuildCommandList(MauticEvents\CommandListEvent $event)
    {
        if ($this->security->isGranted(array('form:forms:viewown', 'form:forms:viewother'), "MATCH_ONE")) {
            $event->addCommands(
                'mautic.form.form.header.index',
                $this->factory->getModel('form.form')->getCommandList()
            );
        }
    }

    /**
     * Add an entry to the audit log
     *
     * @param Events\FormEvent $event
     */
    public function onFormPostSave(Events\FormEvent $event)
    {
        $form = $event->getForm();
        if ($details = $event->getChanges()) {
            $log = array(
                "bundle"    => "form",
                "object"    => "form",
                "objectId"  => $form->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details,
                "ipAddress" => $this->request->server->get('REMOTE_ADDR')
            );
            $this->factory->getModel('core.auditLog')->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log
     *
     * @param Events\FormEvent $event
     */
    public function onFormDelete(Events\FormEvent $event)
    {
        $form = $event->getForm();
        $log = array(
            "bundle"     => "form",
            "object"     => "form",
            "objectId"   => $form->deletedId,
            "action"     => "delete",
            "details"    => array('name' => $form->getName()),
            "ipAddress"  => $this->request->server->get('REMOTE_ADDR')
        );
        $this->factory->getModel('core.auditLog')->writeToLog($log);
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

        // Decide if those events are filtered
        $filter = $event->getEventFilter();
        $loadAllEvents = empty($filter);
        $eventFilterExists = in_array($eventTypeKey, $filter);

        if (!$loadAllEvents && !$eventFilterExists) {
            return;
        }

        $lead    = $event->getLead();
        $options = array('ipIds' => array(), 'filters' => $filter);

        /** @var \Mautic\CoreBundle\Entity\IpAddress $ip */
        foreach ($lead->getIpAddresses() as $ip) {
            $options['ipIds'][] = $ip->getId();
        }

        /** @var \Mautic\FormBundle\Entity\SubmissionRepository $submissionRepository */
        $submissionRepository = $this->factory->getEntityManager()->getRepository('MauticFormBundle:Submission');

        $rows = $submissionRepository->getSubmissions($options);

        $pageModel = $this->factory->getModel('page.page');
        $formModel = $this->factory->getModel('form.form');

        // Add the submissions to the event array
        foreach ($rows as $row) {
            $event->addEvent(array(
                'event'     => $eventTypeKey,
                'eventLabel' => $eventTypeName,
                'timestamp' => new \DateTime($row['date_submitted']),
                'extra'     => array(
                    'form'  => $formModel->getEntity($row['form_id']),
                    'page'  => $pageModel->getEntity($row['page_id'])
                ),
                'contentTemplate' => 'MauticFormBundle:Timeline:index.html.php'
            ));
        }
    }
}
