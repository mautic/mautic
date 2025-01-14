<?php

namespace Mautic\PageBundle\EventListener;

use Doctrine\DBAL\Connection;
use Mautic\CalendarBundle\CalendarEvents;
use Mautic\CalendarBundle\Event\CalendarGeneratorEvent;
use Mautic\CalendarBundle\Event\EventGeneratorEvent;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    /**
     * @var PageModel
     */
    private $pageModel;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var CorePermissions
     */
    private $security;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(
        PageModel $pageModel,
        Connection $connection,
        CorePermissions $security,
        TranslatorInterface $translator,
        RouterInterface $router
    ) {
        $this->pageModel  = $pageModel;
        $this->connection = $connection;
        $this->security   = $security;
        $this->translator = $translator;
        $this->router     = $router;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CalendarEvents::CALENDAR_ON_GENERATE       => ['onCalendarGenerate', 0],
            CalendarEvents::CALENDAR_EVENT_ON_GENERATE => ['onCalendarEventGenerate', 0],
        ];
    }

    /**
     * Adds events to the calendar.
     *
     * @todo   This method is only a model and should be removed when actual data is being populated
     */
    public function onCalendarGenerate(CalendarGeneratorEvent $event)
    {
        $dates = $event->getDates();

        $commonSelect = 'p.title, p.id as page_id, c.color';
        $eventTypes   = [
            'publish.up'   => ['dateName' => 'publish_up', 'setter' => 'PublishUp'],
            'publish.down' => ['dateName' => 'publish_down', 'setter' => 'PublishDown'],
        ];

        $query = $this->connection->createQueryBuilder();
        $query->from(MAUTIC_TABLE_PREFIX.'pages', 'p')
            ->leftJoin('p', MAUTIC_TABLE_PREFIX.'categories', 'c', 'c.id = p.category_id AND c.bundle=:bundle')
            ->setParameter('bundle', 'page')
            ->setParameter('start', $dates['start_date'])
            ->setParameter('end', $dates['end_date'])
            ->setFirstResult(0)
            ->setMaxResults(50);

        foreach ($eventTypes as $eventKey => $eventType) {
            $query->select($commonSelect.', '.$eventType['dateName'].' AS start')
                ->where($query->expr()->andX(
                    $query->expr()->gte('p.'.$eventType['dateName'], ':start'),
                    $query->expr()->lte('p.'.$eventType['dateName'], ':end')
                ));

            $results = $query->execute()->fetchAll();

            // We need to convert the date to a ISO8601 compliant string
            foreach ($results as &$object) {
                $date                 = new DateTimeHelper($object['start']);
                $eventTitle           = $this->translator->trans('mautic.page.event.'.$eventKey, ['%page%' => $object['title']]);
                $object['start']      = $date->toLocalString(\DateTime::ISO8601);
                $object['setter']     = $eventType['setter'];
                $object['entityId']   = $object['page_id'];
                $object['entityType'] = 'page';
                $object['editable']   = true;
                $object['url']        = $this->router->generate('mautic_calendar_action', [
                    'objectAction' => 'edit',
                    'source'       => 'page',
                    'objectId'     => $object['page_id'],
                    'startDate'    => $date->toLocalString(),
                ], true);
                $object['viewUrl'] = $this->router->generate('mautic_page_action', ['objectAction' => 'view', 'objectId' => $object['page_id']], true);
                $object['attr']    = [
                    'data-toggle' => 'ajaxmodal',
                    'data-target' => '#CalendarEditModal',
                    'data-header' => $eventTitle,
                ];
                $object['description'] = $this->translator->trans('mautic.page.event.'.$eventKey.'.description', ['%page%' => $object['title']]);
                $object['title']       = $eventTitle;
            }

            $event->addEvents($results);
        }
    }

    /**
     * Let the calendar to edit / create new entities.
     */
    public function onCalendarEventGenerate(EventGeneratorEvent $event)
    {
        $source = $event->getSource();

        if ('page' != $source) {
            return;
        }

        $entityId = $event->getEntityId();
        $entity   = $this->pageModel->getEntity($entityId);

        $event->setModel($this->pageModel);
        $event->setEntity($entity);
        $event->setContentTemplate('MauticPageBundle:SubscribedEvents\Calendar:modal.html.php');
        $event->setFormName('page_publish_dates');
        $event->setAccess($this->security->hasEntityAccess(
            'page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy()));
    }
}
