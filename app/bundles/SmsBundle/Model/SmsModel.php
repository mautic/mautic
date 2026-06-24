<?php

namespace Mautic\SmsBundle\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\GlobalSearchInterface;
use Mautic\CoreBundle\Model\TranslationModelTrait;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\DoNotContactRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\SmsBundle\Collection\RecipientCollection;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Entity\Stat;
use Mautic\SmsBundle\Event\DncEvent;
use Mautic\SmsBundle\Event\FilterEvent;
use Mautic\SmsBundle\Event\QueueEvent;
use Mautic\SmsBundle\Event\SmsEvent;
use Mautic\SmsBundle\Event\SmsSendEvent;
use Mautic\SmsBundle\Exception\PrimaryTransportNotEnabledException;
use Mautic\SmsBundle\Form\Type\SmsType;
use Mautic\SmsBundle\Helper\DTO\SmsRecipientDTO;
use Mautic\SmsBundle\Sms\TransportChain;
use Mautic\SmsBundle\SmsEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends FormModel<Sms>
 *
 * @implements AjaxLookupModelInterface<Sms>
 */
class SmsModel extends FormModel implements AjaxLookupModelInterface, GlobalSearchInterface
{
    use TranslationModelTrait;

    public function __construct(
        protected TrackableModel $pageTrackableModel,
        protected LeadModel $leadModel,
        protected TransportChain $transport,
        private CacheStorageHelper $cacheStorageHelper,
        EntityManagerInterface $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        TranslatorInterface $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    /**
     * @return \Mautic\SmsBundle\Entity\SmsRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(Sms::class);
    }

    /**
     * @return \Mautic\SmsBundle\Entity\StatRepository
     */
    public function getStatRepository()
    {
        return $this->em->getRepository(Stat::class);
    }

    public function getDoNotContactRepository(): DoNotContactRepository
    {
        return $this->em->getRepository(DoNotContact::class);
    }

    public function getPermissionBase(): string
    {
        return 'sms:smses';
    }

    public function saveEntity($entity, $unlock = true): void
    {
        parent::saveEntity($entity, $unlock);

        $this->postTranslationEntitySave($entity);
    }

    /**
     * Save an array of entities.
     *
     * @param iterable<Sms> $entities
     */
    public function saveEntities($entities, $unlock = true): void
    {
        // iterate over the results so the events are dispatched on each delete
        $batchSize = 20;
        $i         = 0;
        foreach ($entities as $entity) {
            $isNew = ($entity->getId()) ? false : true;

            // set some defaults
            $this->setTimestamps($entity, $isNew, $unlock);

            if ($dispatchEvent = $entity instanceof Sms) {
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
     * @param mixed[] $options
     *
     * @throws MethodNotAllowedHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): FormInterface
    {
        if (!$entity instanceof Sms) {
            throw new MethodNotAllowedHttpException(['Sms']);
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(SmsType::class, $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     */
    public function getEntity($id = null): ?Sms
    {
        if (null === $id) {
            $entity = new Sms();
        } else {
            $entity = parent::getEntity($id);
        }

        return $entity;
    }

    /**
     * Return a list of entities.
     *
     * @param array $args [start, limit, filter, orderBy, orderByDir]
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator|array
     */
    public function getEntities(array $args = [])
    {
        $entities = parent::getEntities($args);

        foreach ($entities as $entity) {
            $pending = $this->cacheStorageHelper->get(sprintf('%s|%s|%s', 'sms', $entity->getId(), 'pending'));

            if (false !== $pending) {
                $entity->setPendingCount($pending);
            }
        }

        return $entities;
    }

    /**
     * @param Lead|int|array<Lead>|array<int> $sendTo
     * @param array                           $options
     * @param array<int, Lead>                $contacts
     */
    public function sendSms(Sms $sms, $sendTo, $options = [], array &$contacts = []): array
    {
        $channel = $options['channel'] ?? null;
        $listId  = $options['listId'] ?? null;
        $sendTo  = is_array($sendTo) ? $sendTo : [$sendTo];

        $sentCount       = [];
        $stats           = [];
        $results         = [];
        $contacts        = []; // Shall we reset the passed contacts param here?
        $fetchContacts   = [];
        foreach ($sendTo as $contact) {
            if ($contact instanceof Lead) {
                $contacts[$contact->getId()] = $contact;
            } else {
                $fetchContacts[] = $contact;
            }
        }

        if ($fetchContacts) {
            /** @var Lead[] $foundContacts */
            $foundContacts = $this->leadModel->getEntities(['ids' => $fetchContacts]);

            foreach ($foundContacts as $contact) {
                $contacts[$contact->getId()] = $contact;
            }
        }

        if (!$sms->isPublished()) {
            foreach ($contacts as $contactId => $contact) {
                $results[$contactId] = [
                    'sent'   => false,
                    'status' => 'mautic.sms.campaign.failed.unpublished',
                ];
            }

            return $results;
        }

        $dncEvent = new DncEvent($contacts);
        $this->dispatcher->dispatch($dncEvent, SmsEvents::DNC_FILTER_CONTACTS_ON_SEND);

        foreach ($dncEvent->getRemovedContacts() as $contactId) {
            $results[$contactId] = [
                'sent'   => false,
                'status' => 'mautic.sms.campaign.failed.not_contactable',
            ];
        }

        $contacts = $dncEvent->getContacts(); // The contacts param is reset here too, no?

        // Check if any contacts remain. If not, return early.
        if (empty($contacts)) {
            return $results;
        }

        $queueEvent = new QueueEvent($contacts, array_merge($options, ['sms_id' => $sms->getId()]));
        $this->dispatcher->dispatch($queueEvent, SmsEvents::QUEUE_FILTER_CONTACTS_ON_SEND);

        foreach ($queueEvent->getQueuedContacts() as $contactId) {
            $results[$contactId] = [
                'sent'   => false,
                'status' => 'mautic.sms.timeline.status.scheduled',
            ];
        }

        $contacts = $queueEvent->getContacts();

        // Check if any contacts remain. If not, return early.
        if (!$contacts) {
            return $results;
        }

        $filterEvent = new FilterEvent($contacts);
        $this->dispatcher->dispatch($filterEvent, SmsEvents::FILTER_CONTACTS_ON_SEND);

        foreach ($filterEvent->getRemovedContacts() as $contactId) {
            $results[$contactId] = [
                'sent'   => false,
                'status' => 'mautic.sms.campaign.failed.missing_number',
            ];
        }

        $contacts = $filterEvent->getContacts();

        if (!$contacts) {
            return $results;
        }

        $recipientCollections = [];

        /** @var array<int, Stat> $stats */
        $stats = [];

        /** @var Lead $contact */
        foreach ($contacts as $contact) {
            [, $translatedSms] = $this->getTranslatedEntity($sms, $contact);
            \assert($translatedSms instanceof Sms);

            $stat    = $this->createStatEntry($translatedSms, $contact, $channel, false, $listId);
            $stats[] = $stat;

            $smsEvent = new SmsSendEvent($translatedSms->getMessage(), $contact);
            $smsEvent->setSmsId($translatedSms->getId());
            $this->dispatcher->dispatch($smsEvent, SmsEvents::SMS_ON_SEND);

            $tokenEvent = $this->dispatcher->dispatch(
                new TokenReplacementEvent(
                    $smsEvent->getContent(),
                    $contact,
                    [
                        'channel' => [
                            'sms',          // Keep BC pre 2.14.1
                            $translatedSms->getId(),  // Keep BC pre 2.14.1
                            'sms' => $translatedSms->getId(),
                        ],
                        'stat'    => $stat->getTrackingHash(),
                    ]
                ),
                SmsEvents::TOKEN_REPLACEMENT
            );

            if (!isset($recipientCollections[$translatedSms->getId()])) {
                $recipientCollections[$translatedSms->getId()] = new RecipientCollection($translatedSms);
            }
            $recipientCollections[$translatedSms->getId()]->append(new SmsRecipientDTO($contact, $tokenEvent->getTokens(), $tokenEvent->getContent()));

            unset($smsEvent, $tokenEvent);
        }

        foreach ($recipientCollections as $recipientCollection) {
            $translatedSms = $recipientCollection->getSms();
            $media         = $translatedSms->getMedia();

            try {
                // assumption made that the Sms message is same for all contacts
                $message = $translatedSms->getMessage();
                if ($media) {
                    $this->transport->sendMMS($recipientCollection, $media);
                } else {
                    $this->transport->sendBatchSms($recipientCollection, $message);
                }
            } catch (PrimaryTransportNotEnabledException $e) {
                $this->logger->warning($e->getMessage());

                return $results;
            }

            $defaultSendResult = [
                'sent'    => false,
                'type'    => 'mautic.sms.sms',
                'status'  => 'mautic.sms.timeline.status.delivered',
                'id'      => $recipientCollection->getSms()->getId(),
                'name'    => $recipientCollection->getSms()->getName(),
            ];

            foreach ($recipientCollection as $recipient) {
                $defaultSendResult['content'] = $recipient->getFinalMessage();
                if (true !== $recipient->getResult()) {
                    $defaultSendResult['sent']   = false;
                    $defaultSendResult['status'] = $recipient->getResult();
                    unset($stats[$recipient->getKey()]);
                } else {
                    $defaultSendResult['sent']          = true;
                    $defaultSendResult['status']        = 'mautic.sms.timeline.status.delivered';
                    $sentCount[$translatedSms->getId()] = ($sentCount[$translatedSms->getId()] ?? 0) + 1;
                }

                $results[$recipient->getKey()] = $defaultSendResult;
            }
        }

        if ($sentCount) {
            $repo = $this->getRepository();
            foreach ($sentCount as $id => $count) {
                $repo->upCount($id, 'sent', $count);
            }
        }

        if (count($stats)) {
            $this->getStatRepository()->saveEntities($stats);

            foreach ($stats as $stat) {
                if (!$stat->isFailed()) {
                    $results[$stat->getLead()->getId()]['statId'] = $stat->getId();
                }

                $this->getRepository()->detachEntity($stat);
            }
        }

        return $results;
    }

    /**
     * @param bool $persist
     *
     * @throws \Exception
     */
    public function createStatEntry(Sms $sms, Lead $lead, $source = null, $persist = true, $listId = null): Stat
    {
        $stat = new Stat();
        $stat->setDateSent(new \DateTime());
        $stat->setLead($lead);
        $stat->setSms($sms);
        if (null !== $listId) {
            $stat->setList($this->leadModel->getLeadListRepository()->getEntity($listId));
        }
        if (is_array($source)) {
            $stat->setSourceId($source[1]);
            $source = $source[0];
        }
        $stat->setSource($source);
        $stat->setTrackingHash(str_replace('.', '', uniqid('', true)));

        if ($persist) {
            $this->getStatRepository()->saveEntity($stat);
        }

        return $stat;
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, ?Event $event = null): ?Event
    {
        if (!$entity instanceof Sms) {
            throw new MethodNotAllowedHttpException(['Sms']);
        }

        switch ($action) {
            case 'pre_save':
                $name = SmsEvents::SMS_PRE_SAVE;
                break;
            case 'post_save':
                $name = SmsEvents::SMS_POST_SAVE;
                break;
            case 'pre_delete':
                $name = SmsEvents::SMS_PRE_DELETE;
                break;
            case 'post_delete':
                $name = SmsEvents::SMS_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (!$event instanceof Event) {
                $event = new SmsEvent($entity, $isNew);
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
        $q->join('t', MAUTIC_TABLE_PREFIX.'sms_messages', 's', 's.id = t.sms_id')
            ->andWhere('s.created_by = :userId')
            ->setParameter('userId', $this->userHelper->getUser()->getId());
    }

    /**
     * Get line chart data of hits.
     *
     * @param ?string $unit          {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param string  $dateFormat
     * @param array   $filter
     * @param bool    $canViewOthers
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
            $filter['is_failed'] = 0;
            $q                   = $query->prepareTimeDataQuery('sms_message_stats', 'date_sent', $filter);

            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }

            $data = $query->loadAndBuildTimeData($q);
            $chart->setDataset($this->translator->trans('mautic.sms.show.total.sent'), $data);
        }

        if (!$flag || 'failed' === $flag) {
            $filter['is_failed'] = 1;
            $q                   = $query->prepareTimeDataQuery('sms_message_stats', 'date_sent', $filter);
            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }

            $data = $query->loadAndBuildTimeData($q);
            $chart->setDataset($this->translator->trans('mautic.sms.show.failed'), $data);
        }

        return $chart->render();
    }

    /**
     * @return Stat
     */
    public function getSmsStatus($idHash)
    {
        return $this->getStatRepository()->getSmsStatus($idHash);
    }

    /**
     * Search for an sms stat by sms and lead IDs.
     *
     * @return array
     */
    public function getSmsStatByLeadId($smsId, $leadId)
    {
        return $this->getStatRepository()->findBy(
            [
                'sms'  => (int) $smsId,
                'lead' => (int) $leadId,
            ],
            ['dateSent' => 'DESC']
        );
    }

    /**
     * Get an array of tracked links.
     */
    public function getSmsClickStats($smsId): array
    {
        return $this->pageTrackableModel->getTrackableList('sms', $smsId);
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
            case 'sms':
            case SmsType::class:
                $entities = $this->getRepository()->getSmsList(
                    $filter,
                    $limit,
                    $start,
                    $this->security->isGranted($this->getPermissionBase().':viewother'),
                    $options['sms_type'] ?? null,
                    $options['top_level'] ?? '',
                    $options['ignore_ids'] ?? [],
                );

                foreach ($entities as $entity) {
                    $mms                                         = !empty($entity['media']) ? '['.$this->translator->trans('mautic.sms.form.mms').'] ' : '';
                    $results[$entity['language']][$entity['id']] = $mms.$entity['name'];
                }

                // sort by language
                ksort($results);

                break;
        }

        return $results;
    }
}
