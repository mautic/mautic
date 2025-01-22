<?php

namespace Mautic\NotificationBundle\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\GlobalSearchInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\Entity\Stat;
use Mautic\NotificationBundle\Event\NotificationEvent;
use Mautic\NotificationBundle\Form\Type\MobileNotificationType;
use Mautic\NotificationBundle\Form\Type\NotificationType;
use Mautic\NotificationBundle\NotificationEvents;
use Mautic\PageBundle\Model\TrackableModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<Notification>
 *
 * @implements AjaxLookupModelInterface<Notification>
 */
class NotificationModel extends FormModel implements AjaxLookupModelInterface, GlobalSearchInterface
{
    public function __construct(
        protected TrackableModel $pageTrackableModel,
        EntityManager $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    /**
     * @return \Mautic\NotificationBundle\Entity\NotificationRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(Notification::class);
    }

    /**
     * @return \Mautic\NotificationBundle\Entity\StatRepository
     */
    public function getStatRepository()
    {
        return $this->em->getRepository(Stat::class);
    }

    public function getPermissionBase(): string
    {
        return 'notification:notifications';
    }

    public function saveEntities($entities, $unlock = true): void
    {
        // iterate over the results so the events are dispatched on each delete
        $batchSize = 20;
        $i         = 0;
        foreach ($entities as $entity) {
            $isNew = ($entity->getId()) ? false : true;

            // set some defaults
            $this->setTimestamps($entity, $isNew, $unlock);

            if ($dispatchEvent = $entity instanceof Notification) {
                $event = $this->dispatchEvent('pre_save', $entity, $isNew);
            }

            $this->getRepository()->saveEntity($entity, false);

            if ($dispatchEvent) {
                $this->dispatchEvent('post_save', $entity, $isNew, $event);
            }

            if (0 === ++$i % $batchSize) {
                $this->em->flush();
            }
        }
        $this->em->flush();
    }

    /**
     * @param Notification|null $entity
     * @param string|null       $action
     * @param array             $options
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws MethodNotAllowedHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): FormInterface
    {
        if (!$entity instanceof Notification) {
            throw new MethodNotAllowedHttpException(['Notification']);
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }

        $type = str_contains($action, 'mobile_') ? MobileNotificationType::class : NotificationType::class;

        return $formFactory->create($type, $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     */
    public function getEntity($id = null): ?Notification
    {
        if (null === $id) {
            $entity = new Notification();
        } else {
            $entity = parent::getEntity($id);
        }

        return $entity;
    }

    /**
     * @param string $source
     * @param int    $sourceId
     */
    public function createStatEntry(Notification $notification, Lead $lead, $source = null, $sourceId = null): void
    {
        $stat = new Stat();
        $stat->setDateSent(new \DateTime());
        $stat->setLead($lead);
        $stat->setNotification($notification);
        $stat->setSource($source);
        $stat->setSourceId($sourceId);

        $this->getStatRepository()->saveEntity($stat);
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null): ?Event
    {
        if (!$entity instanceof Notification) {
            throw new MethodNotAllowedHttpException(['Notification']);
        }

        switch ($action) {
            case 'pre_save':
                $name = NotificationEvents::NOTIFICATION_PRE_SAVE;
                break;
            case 'post_save':
                $name = NotificationEvents::NOTIFICATION_POST_SAVE;
                break;
            case 'pre_delete':
                $name = NotificationEvents::NOTIFICATION_PRE_DELETE;
                break;
            case 'post_delete':
                $name = NotificationEvents::NOTIFICATION_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new NotificationEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        }

        return null;
    }

    /**
     * Joins the page table and limits created_by to currently logged in user.
     */
    public function limitQueryToCreator(QueryBuilder &$q): void
    {
        $q->join('t', MAUTIC_TABLE_PREFIX.'push_notifications', 'p', 'p.id = t.notification_id')
            ->andWhere('p.created_by = :userId')
            ->setParameter('userId', $this->userHelper->getUser()->getId());
    }

    /**
     * Get line chart data of hits.
     *
     * @param char   $unit          {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param string $dateFormat
     * @param array  $filter
     * @param bool   $canViewOthers
     */
    public function getHitsLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [], $canViewOthers = true): array
    {
        $flag = null;

        if (isset($filter['flag'])) {
            $flag = $filter['flag'];
            unset($filter['flag']);
        }

        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);

        if (!$flag || 'total_and_unique' === $flag) {
            $q = $query->prepareTimeDataQuery('push_notification_stats', 'date_sent', $filter);

            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }

            $data = $query->loadAndBuildTimeData($q);
            $chart->setDataset($this->translator->trans('mautic.notification.show.total.sent'), $data);
        }

        return $chart->render();
    }

    /**
     * @return Stat
     */
    public function getNotificationStatus($idHash)
    {
        return $this->getStatRepository()->getNotificationStatus($idHash);
    }

    /**
     * Search for an notification stat by notification and lead IDs.
     *
     * @return array
     */
    public function getNotificationStatByLeadId($notificationId, $leadId)
    {
        return $this->getStatRepository()->findBy(
            [
                'notification' => (int) $notificationId,
                'lead'         => (int) $leadId,
            ],
            ['dateSent' => 'DESC']
        );
    }

    /**
     * Get an array of tracked links.
     */
    public function getNotificationClickStats($notificationId): array
    {
        return $this->pageTrackableModel->getTrackableList('notification', $notificationId);
    }

    /**
     * @param string $filter
     * @param int    $limit
     * @param int    $start
     * @param array  $options
     */
    public function getLookupResults($type, $filter = '', $limit = 10, $start = 0, $options = []): array
    {
        $results = [];
        switch ($type) {
            case 'notification':
                $entities = $this->getRepository()->getNotificationList(
                    $filter,
                    $limit,
                    $start,
                    $this->security->isGranted($this->getPermissionBase().':viewother'),
                    $options['notification_type'] ?? null
                );

                foreach ($entities as $entity) {
                    $results[$entity['language']][$entity['id']] = $entity['name'];
                }

                // sort by language
                ksort($results);

                break;
            case 'mobile_notification':
                $entities = $this->getRepository()->getMobileNotificationList(
                    $filter,
                    $limit,
                    $start,
                    $this->security->isGranted($this->getPermissionBase().':viewother'),
                    $options['notification_type'] ?? null
                );

                foreach ($entities as $entity) {
                    $results[$entity['language']][$entity['id']] = $entity['name'];
                }

                // sort by language
                ksort($results);

                break;
        }

        return $results;
    }
}
