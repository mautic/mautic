<?php

namespace Mautic\NotificationBundle\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\GlobalSearchInterface;
use Mautic\CoreBundle\Model\TranslationModelTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\Entity\NotificationRepository;
use Mautic\NotificationBundle\Entity\Stat;
use Mautic\NotificationBundle\Entity\StatRepository;
use Mautic\NotificationBundle\Event\NotificationEvent;
use Mautic\NotificationBundle\Form\Type\MobileNotificationType;
use Mautic\NotificationBundle\Form\Type\NotificationType;
use Mautic\NotificationBundle\NotificationEvents;
use Mautic\PageBundle\Model\TrackableModel;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends FormModel<Notification>
 *
 * @implements AjaxLookupModelInterface<Notification>
 */
final class NotificationModel extends FormModel implements AjaxLookupModelInterface, GlobalSearchInterface
{
    use TranslationModelTrait;

    public static function getName(): string
    {
        return 'notification.notification';
    }

    private TrackableModel $pageTrackableModel;

    private NotificationRepository $notificationRepository;

    private StatRepository $statRepository;

    #[Required]
    public function autowireNotificationModel(
        TrackableModel $pageTrackableModel,
        NotificationRepository $notificationRepository,
        StatRepository $statRepository,
    ): void {
        $this->pageTrackableModel     = $pageTrackableModel;
        $this->notificationRepository = $notificationRepository;
        $this->statRepository         = $statRepository;
    }

    public function getRepository(): NotificationRepository
    {
        return $this->notificationRepository;
    }

    public function getPermissionBase(): string
    {
        return 'notification:notifications';
    }

    public function saveEntities($entities, bool $unlock = true): void
    {
        // iterate over the results so the events are dispatched on each delete
        $batchSize = 20;
        $i         = 0;
        foreach ($entities as $entity) {
            $isNew = !(bool) $entity->getId();

            // set some defaults
            $this->setTimestamps($entity, $isNew, $unlock);

            if ($dispatchEvent = $entity instanceof Notification) {
                $event = $this->dispatchEvent('pre_save', $entity, $isNew);
            }

            $this->notificationRepository->saveEntity($entity, false);

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
    public function createForm($entity, $action = null, $options = []): FormInterface
    {
        if (!$entity instanceof Notification) {
            throw new MethodNotAllowedHttpException(['Notification']);
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }

        $type = str_contains($action, 'mobile_') ? MobileNotificationType::class : NotificationType::class;

        return $this->formFactory->create($type, $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     */
    public function getEntity($id = null): ?Notification
    {
        if (null === $id) {
            return new Notification();
        }

        return parent::getEntity($id);
    }

    public function saveEntity($entity, bool $unlock = true): void
    {
        parent::saveEntity($entity, $unlock);

        $this->postTranslationEntitySave($entity);
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

        $this->statRepository->saveEntity($stat);
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, bool $isNew = false, ?Event $event = null): ?Event
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
            if (!$event instanceof Event) {
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
     * @param ?string $unit       {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param string  $dateFormat
     */
    public function getHitsLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, array $filter = [], bool $canViewOthers = true): array
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
        return $this->statRepository->getNotificationStatus($idHash);
    }

    /**
     * Search for an notification stat by notification and lead IDs.
     *
     * @return array
     */
    public function getNotificationStatByLeadId($notificationId, $leadId)
    {
        return $this->statRepository->findBy(
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

    public function getLookupResults(string $type, string|array $filter = '', int $limit = 10, int $start = 0, array $options = []): array
    {
        $results = [];
        switch ($type) {
            case 'notification':
                $entities = $this->notificationRepository->getNotificationList(
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
                $entities = $this->notificationRepository->getMobileNotificationList(
                    $filter,
                    $limit,
                    $start,
                    $this->security->isGranted($this->getPermissionBase().':viewother'),
                    $options
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
