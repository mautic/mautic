<?php

namespace Mautic\EmailBundle\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\ChannelBundle\Model\MessageQueueModel;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\Chart\BarChart;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\ThemeHelperInterface;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Mautic\CoreBundle\Model\BuilderModelTrait;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\TranslationModelTrait;
use Mautic\CoreBundle\Model\VariantModelTrait;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatDevice;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailEvent;
use Mautic\EmailBundle\Event\EmailOpenEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Exception\EmailCouldNotBeSentException;
use Mautic\EmailBundle\Exception\FailedToSendToContactException;
use Mautic\EmailBundle\Form\Type\EmailType;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Helper\StatsCollectionHelper;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\Stats\FetchOptions\EmailStatOptions;
use Mautic\EmailBundle\Stats\Helper\FilterTrait;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\DoNotContact as DNC;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\DeviceTracker;
use Mautic\PageBundle\Entity\RedirectRepository;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Entity\TrackableRepository;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\UserBundle\Model\UserModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<Email>
 *
 * @implements AjaxLookupModelInterface<Email>
 */
class EmailModel extends FormModel implements AjaxLookupModelInterface
{
    use VariantModelTrait;
    use TranslationModelTrait;
    use BuilderModelTrait;
    use FilterTrait;

    /**
     * @var bool
     */
    protected $updatingTranslationChildren = false;

    /**
     * @var array
     */
    protected $emailSettings = [];

    public function __construct(
        protected IpLookupHelper $ipLookupHelper,
        protected ThemeHelperInterface $themeHelper,
        protected Mailbox $mailboxHelper,
        protected MailHelper $mailHelper,
        protected LeadModel $leadModel,
        protected CompanyModel $companyModel,
        protected TrackableModel $pageTrackableModel,
        protected UserModel $userModel,
        protected MessageQueueModel $messageQueueModel,
        protected SendEmailToContact $sendModel,
        private DeviceTracker $deviceTracker,
        private RedirectRepository $redirectRepository,
        private CacheStorageHelper $cacheStorageHelper,
        private ContactTracker $contactTracker,
        private DNC $doNotContact,
        private StatsCollectionHelper $statsCollectionHelper,
        CorePermissions $security,
        EntityManagerInterface $em,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper,
        private EmailStatModel $emailStatModel
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    public function getRepository(): EmailRepository
    {
        return $this->em->getRepository(Email::class);
    }

    public function getStatRepository(): StatRepository
    {
        return $this->emailStatModel->getRepository();
    }

    /**
     * @return \Mautic\EmailBundle\Entity\CopyRepository
     */
    public function getCopyRepository()
    {
        return $this->em->getRepository(\Mautic\EmailBundle\Entity\Copy::class);
    }

    /**
     * @return \Mautic\EmailBundle\Entity\StatDeviceRepository
     */
    public function getStatDeviceRepository()
    {
        return $this->em->getRepository(StatDevice::class);
    }

    public function getPermissionBase(): string
    {
        return 'email:emails';
    }

    /**
     * @param Email $entity
     */
    public function saveEntity($entity, $unlock = true): void
    {
        $type = $entity->getEmailType();
        if (empty($type)) {
            // Just in case JS failed
            $entity->setEmailType('template');
        }

        // Ensure that list emails are published
        if ('list' == $entity->getEmailType()) {
            // Ensure that this email has the same lists assigned as the translated parent if applicable
            if ($translationParent = $entity->getTranslationParent()) {
                \assert($translationParent instanceof Email);
                $parentLists = $translationParent->getLists()->toArray();
                $entity->setLists($parentLists);
            }
        } else {
            // Ensure that all lists are been removed in case of a clone
            $entity->setLists([]);
        }

        if (!$this->updatingTranslationChildren) {
            if (!$entity->isNew()) {
                // increase the revision
                $revision = $entity->getRevision();
                ++$revision;
                $entity->setRevision($revision);
            }

            // Reset a/b test if applicable
            if ($isVariant = $entity->isVariant()) {
                $variantStartDate = new \DateTime();
                $resetVariants    = $this->preVariantSaveEntity($entity, ['setVariantSentCount', 'setVariantReadCount'], $variantStartDate);
            }

            parent::saveEntity($entity, $unlock);

            if ($isVariant) {
                $emailIds = $entity->getRelatedEntityIds();
                $this->postVariantSaveEntity($entity, $resetVariants, $emailIds, $variantStartDate);
            }

            $this->postTranslationEntitySave($entity);

            // Force translations for this entity to use the same segments
            if ('list' == $entity->getEmailType() && $entity->hasTranslations()) {
                $translations                      = $entity->getTranslationChildren()->toArray();
                $this->updatingTranslationChildren = true;
                foreach ($translations as $translation) {
                    $this->saveEntity($translation);
                }
                $this->updatingTranslationChildren = false;
            }
        } else {
            parent::saveEntity($entity, false);
        }
    }

    /**
     * Save an array of entities.
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

            if ($dispatchEvent = $entity instanceof Email) {
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
     * @param Email $entity
     */
    public function deleteEntity($entity): void
    {
        if ($entity->isVariant() && $entity->getIsPublished()) {
            $this->resetVariants($entity);
        }

        parent::deleteEntity($entity);
    }

    /**
     * @param string|null $action
     * @param array       $options
     *
     * @return FormInterface<Email>
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): FormInterface
    {
        if (!$entity instanceof Email) {
            throw new MethodNotAllowedHttpException(['Email']);
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(EmailType::class, $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     */
    public function getEntity($id = null): ?Email
    {
        if (null === $id) {
            $entity = new Email();
            $entity->setSessionId('new_'.hash('sha1', uniqid(mt_rand())));
        } else {
            $entity = parent::getEntity($id);
            if (null !== $entity) {
                $entity->setSessionId($entity->getId());
            }
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
            $queued  = $this->cacheStorageHelper->get(sprintf('%s|%s|%s', 'email', $entity->getId(), 'queued'));
            $pending = $this->cacheStorageHelper->get(sprintf('%s|%s|%s', 'email', $entity->getId(), 'pending'));

            if (false !== $queued) {
                $entity->setQueuedCount($queued);
            }

            if (false !== $pending) {
                $entity->setPendingCount($pending);
            }
        }

        return $entities;
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null): ?Event
    {
        if (!$entity instanceof Email) {
            throw new MethodNotAllowedHttpException(['Email']);
        }

        switch ($action) {
            case 'pre_save':
                $name = EmailEvents::EMAIL_PRE_SAVE;
                break;
            case 'post_save':
                $name = EmailEvents::EMAIL_POST_SAVE;
                break;
            case 'pre_delete':
                $name = EmailEvents::EMAIL_PRE_DELETE;
                break;
            case 'post_delete':
                $name = EmailEvents::EMAIL_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new EmailEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param Stat|string|null $stat                    The null is just for BC reasons, should be Stat|string
     * @param bool             $throwDoctrineExceptions in asynchronous processing; we do not wish to ignore the error, rather let the messenger do the handling
     *
     * @throws OptimisticLockException|\Exception
     */
    public function hitEmail(
        $stat,
        Request $request,
        bool $viaBrowser = false,
        bool $activeRequest = true,
        \DateTimeInterface $hitDateTime = null,
        bool $throwDoctrineExceptions = false
    ): void {
        // check for existing IP
        $ipAddress     = $this->ipLookupHelper->getIpAddress();
        $isIpTrackable = $ipAddress->isTrackable();

        if (!$isIpTrackable) {
            return;
        }

        if (!$stat instanceof Stat) {
            $stat = $this->getEmailStatus($stat);
        }

        if (!$stat) {
            trigger_deprecation('mautic/mautic', '5.0', 'Calls to hitEmail without a stat are deprecated');

            return;
        }

        $email = $stat->getEmail();

        if ((int) $stat->isRead()) {
            if ($viaBrowser && !$stat->getViewedInBrowser()) {
                // opened via browser so note it
                $stat->setViewedInBrowser($viaBrowser);
            }
        }

        $readDateTime = new DateTimeHelper($hitDateTime ?? '');
        $stat->setLastOpened($readDateTime->getDateTime());

        $lead = $stat->getLead();
        if (null !== $lead) {
            // Set the lead as current lead
            if ($activeRequest) {
                $this->contactTracker->setTrackedContact($lead);
            } else {
                $this->contactTracker->setSystemContact($lead);
            }
        }

        $firstTime = false;
        if (!$stat->getIsRead()) {
            $firstTime = true;
            $stat->setIsRead(true);
            $stat->setDateRead($readDateTime->getDateTime());
        }

        if ($viaBrowser) {
            $stat->setViewedInBrowser($viaBrowser);
        }

        $stat->addOpenDetails(
            [
                'datetime'  => $readDateTime->toUtcString(),
                'useragent' => $request->server->get('HTTP_USER_AGENT'),
                'inBrowser' => $viaBrowser,
            ]
        );
        $stat->setIpAddress($ipAddress);

        if ($this->dispatcher->hasListeners(EmailEvents::EMAIL_ON_OPEN)) {
            $event = new EmailOpenEvent($stat, $request, $firstTime);
            $this->dispatcher->dispatch($event, EmailEvents::EMAIL_ON_OPEN);
        }

        // Only up counts if associated with both an email and lead
        if ($firstTime && $email && $lead) {
            try {
                $this->getRepository()->incrementRead($email->getId(), $stat->getId(), $email->isVariant());
            } catch (\Exception $exception) {
                error_log($exception);
            }
        }
        if ($email) {
            $this->em->persist($email);
        }

        $this->emailStatModel->saveEntity($stat);

        // Flush the email stat entity in different transactions than the device stat entity to avoid deadlocks.
        if ($throwDoctrineExceptions) {
            $this->em->flush();
        } else {
            $this->flushAndCatch();
        }

        if ($lead) {
            $trackedDevice = $this->deviceTracker->createDeviceFromUserAgent(
                $lead,
                $request->server->get('HTTP_USER_AGENT')
            );

            // As the entity might be cached, present in EM, but not attached, we need to reload it
            if ($trackedDevice->getId()) {
                $trackedDevice = $this->em->getRepository(LeadDevice::class)->find($trackedDevice->getId());
            }

            $emailOpenStat = new StatDevice();
            $emailOpenStat->setIpAddress($ipAddress);
            $emailOpenStat->setDevice($trackedDevice);
            $emailOpenStat->setDateOpened($readDateTime->toUtcString());
            $emailOpenStat->setStat($stat);

            $this->em->persist($emailOpenStat);
            if ($throwDoctrineExceptions) {
                $this->em->flush();
            } else {
                $this->flushAndCatch();
            }

            if (null !== $hitDateTime && $lead->getLastActive() < $hitDateTime) { // We need to perform the update after all is saved
                $this->leadModel->getRepository()->updateLastActive($lead->getId(), $hitDateTime);
            }
        }
    }

    public function saveEmailStat(Stat $stat): void
    {
        $this->emailStatModel->saveEntity($stat);
    }

    /**
     * Get array of page builder tokens from bundles subscribed PageEvents::PAGE_ON_BUILD.
     *
     * @param array|string $requestedComponents all | tokens | abTestWinnerCriteria
     *
     * @return array
     */
    public function getBuilderComponents(Email $email = null, $requestedComponents = 'all', string $tokenFilter = '')
    {
        $event = new EmailBuilderEvent($this->translator, $email, $requestedComponents, $tokenFilter);
        $this->dispatcher->dispatch($event, EmailEvents::EMAIL_ON_BUILD);

        return $this->getCommonBuilderComponents($requestedComponents, $event);
    }

    /**
     * @param array    $options
     * @param int|null $companyId
     * @param int|null $campaignId
     * @param int|null $segmentId
     */
    public function getSentEmailToContactData($limit, \DateTime $dateFrom, \DateTime $dateTo, $options = [], $companyId = null, $campaignId = null, $segmentId = null): array
    {
        $createdByUserId = null;
        $canViewOthers   = empty($options['canViewOthers']) ? false : $options['canViewOthers'];

        if (!$canViewOthers) {
            $createdByUserId = $this->userHelper->getUser()->getId();
        }

        $stats = $this->getStatRepository()->getSentEmailToContactData($limit, $dateFrom, $dateTo, $createdByUserId, $companyId, $campaignId, $segmentId);
        $data  = [];

        foreach ($stats as $stat) {
            $statId = $stat['id'];

            if (empty($stat['segment_id']) && !empty($stat['campaign_id'])) {
                // Let's fetch the segment based on current campaign/segment membership
                $segmentMembership = $this->em->getRepository(\Mautic\CampaignBundle\Entity\Campaign::class)
                    ->getContactSingleSegmentByCampaign($stat['lead_id'], $stat['campaign_id']);

                if ($segmentMembership) {
                    $stat['segment_id']   = $segmentMembership['id'];
                    $stat['segment_name'] = $segmentMembership['name'];
                }
            }

            $item = [
                'contact_id'    => $stat['lead_id'],
                'contact_email' => $stat['email_address'],
                'open'          => $stat['is_read'],
                'click'         => $stat['link_hits'] ?? 0,
                'links_clicked' => [],
                'email_id'      => (string) $stat['email_id'],
                'email_name'    => (string) $stat['email_name'],
                'segment_id'    => (string) $stat['segment_id'],
                'segment_name'  => (string) $stat['segment_name'],
                'company_id'    => (string) $stat['company_id'],
                'company_name'  => (string) $stat['company_name'],
                'campaign_id'   => (string) $stat['campaign_id'],
                'campaign_name' => (string) $stat['campaign_name'],
                'date_sent'     => $stat['date_sent'],
                'date_read'     => $stat['date_read'],
            ];

            if ($item['click'] && $item['email_id'] && $item['contact_id']) {
                $item['links_clicked'] = $this->getStatRepository()->getUniqueClickedLinksPerContactAndEmail($item['contact_id'], $item['email_id']);
            }

            $data[$statId] = $item;
        }

        return $data;
    }

    /**
     * @param int      $limit
     * @param array    $options
     * @param int|null $companyId
     * @param int|null $campaignId
     * @param int|null $segmentId
     */
    public function getMostHitEmailRedirects($limit, \DateTime $dateFrom, \DateTime $dateTo, $options = [], $companyId = null, $campaignId = null, $segmentId = null): array
    {
        $createdByUserId = null;
        $canViewOthers   = empty($options['canViewOthers']) ? false : $options['canViewOthers'];

        if (!$canViewOthers) {
            $createdByUserId = $this->userHelper->getUser()->getId();
        }

        $redirects = $this->redirectRepository->getMostHitEmailRedirects($limit, $dateFrom, $dateTo, $createdByUserId, $companyId, $campaignId, $segmentId);
        $data      = [];
        foreach ($redirects as $redirect) {
            $data[] = [
                'url'         => (string) $redirect['url'],
                'unique_hits' => (string) $redirect['unique_hits'],
                'hits'        => (string) $redirect['hits'],
                'email_id'    => (string) $redirect['email_id'],
                'email_name'  => (string) $redirect['email_name'],
            ];
        }

        return $data;
    }

    /**
     * @return Stat|null
     */
    public function getEmailStatus($idHash)
    {
        return $this->getStatRepository()->getEmailStatus($idHash);
    }

    /**
     * Search for an email stat by email and lead IDs.
     *
     * @return array
     */
    public function getEmailStati($emailId, $leadId)
    {
        return $this->getStatRepository()->findBy(
            [
                'email' => (int) $emailId,
                'lead'  => (int) $leadId,
            ],
            ['dateSent' => 'DESC']
        );
    }

    /**
     * @return array<string, array<int, array<string, int|string>>>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getCountryStats(Email $entity, \DateTimeImmutable $dateFrom, \DateTimeImmutable $dateTo, bool $includeVariants = false): array
    {
        $emailIds = ($includeVariants && ($entity->isVariant() || $entity->isTranslation())) ? $entity->getRelatedEntityIds() : [$entity->getId()];

        $emailStats            = $this->getStatRepository()->getStatsSummaryByCountry($dateFrom, $dateTo, $emailIds);
        $results['read_count'] = $results['clicked_through_count'] = [];

        foreach ($emailStats as $e) {
            $results['read_count'][]            = array_intersect_key($e, array_flip(['country', 'read_count']));
            $results['clicked_through_count'][] = array_intersect_key($e, array_flip(['country', 'clicked_through_count']));
        }

        return $results;
    }

    /**
     * Get a stats for email by list.
     *
     * @param bool $includeVariants
     */
    public function getEmailListStats($email, $includeVariants = false, \DateTime $dateFrom = null, \DateTime $dateTo = null): array
    {
        if (!$email instanceof Email) {
            $email = $this->getEntity($email);
        }

        $emailIds = ($includeVariants && ($email->isVariant() || $email->isTranslation())) ? $email->getRelatedEntityIds() : [$email->getId()];

        $lists     = $email->getLists();
        $listCount = count($lists);
        $chart     = new BarChart(
            [
                $this->translator->trans('mautic.email.sent'),
                $this->translator->trans('mautic.email.read'),
                $this->translator->trans('mautic.email.failed'),
                $this->translator->trans('mautic.email.clicked'),
                $this->translator->trans('mautic.email.unsubscribed'),
                $this->translator->trans('mautic.email.bounced'),
            ]
        );

        $statRepo = $this->getStatRepository();

        /** @var \Mautic\LeadBundle\Entity\DoNotContactRepository $dncRepo */
        $dncRepo = $this->em->getRepository(DoNotContact::class);

        /** @var TrackableRepository $trackableRepo */
        $trackableRepo = $this->em->getRepository(Trackable::class);
        $query         = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $key           = ($listCount > 1) ? 1 : 0;

        if ($listCount > 1) {
            $sentCounts         = $statRepo->getSentCount($emailIds, $lists->getKeys(), $query);
            $readCounts         = $statRepo->getReadCount($emailIds, $lists->getKeys(), $query);
            $failedCounts       = $statRepo->getFailedCount($emailIds, $lists->getKeys(), $query);
            $clickCounts        = $trackableRepo->getCount('email', $emailIds, $lists->getKeys(), $query, false, 'DISTINCT ph.lead_id');
            $unsubscribedCounts = $dncRepo->getCount('email', $emailIds, DoNotContact::UNSUBSCRIBED, $lists->getKeys(), $query);
            $bouncedCounts      = $dncRepo->getCount('email', $emailIds, DoNotContact::BOUNCED, $lists->getKeys(), $query);

            foreach ($lists as $l) {
                $sentCount         = $sentCounts[$l->getId()] ?? 0;
                $readCount         = $readCounts[$l->getId()] ?? 0;
                $failedCount       = $failedCounts[$l->getId()] ?? 0;
                $clickCount        = $clickCounts[$l->getId()] ?? 0;
                $unsubscribedCount = $unsubscribedCounts[$l->getId()] ?? 0;
                $bouncedCount      = $bouncedCounts[$l->getId()] ?? 0;

                $chart->setDataset(
                    $l->getName(),
                    [
                        $sentCount,
                        $readCount,
                        $failedCount,
                        $clickCount,
                        $unsubscribedCount,
                        $bouncedCount,
                    ],
                    $key
                );

                ++$key;
            }
        }

        if ($listCount) {
            $combined = [
                $statRepo->getSentCount($emailIds, null, $query),
                $statRepo->getReadCount($emailIds, null, $query),
                $statRepo->getFailedCount($emailIds, null, $query),
                $trackableRepo->getCount('email', $emailIds, null, $query, true, 'DISTINCT ph.lead_id'),
                $dncRepo->getCount('email', $emailIds, DoNotContact::UNSUBSCRIBED, null, $query),
                $dncRepo->getCount('email', $emailIds, DoNotContact::BOUNCED, null, $query),
            ];

            if ($listCount > 1) {
                $chart->setDataset(
                    $this->translator->trans('mautic.email.lists.combined'),
                    $combined,
                    0
                );
            } else {
                $chart->setDataset(
                    $lists->first()->getName(),
                    $combined,
                    0
                );
            }
        }

        return $chart->render();
    }

    /**
     * Get a stats for email by list.
     *
     * @param Email|int $email
     * @param bool      $includeVariants
     */
    public function getEmailDeviceStats($email, $includeVariants = false, $dateFrom = null, $dateTo = null): array
    {
        if (!$email instanceof Email) {
            $email = $this->getEntity($email);
        }

        $emailIds      = ($includeVariants) ? $email->getRelatedEntityIds() : [$email->getId()];
        $templateEmail = 'template' === $email->getEmailType();
        $results       = $this->getStatDeviceRepository()->getDeviceStats($emailIds, $dateFrom, $dateTo);

        // Organize by list_id (if a segment email) and/or device
        $stats   = [];
        $devices = [];
        foreach ($results as $result) {
            if (empty($result['device'])) {
                $result['device'] = $this->translator->trans('mautic.core.unknown');
            } else {
                $result['device'] = mb_substr($result['device'], 0, 12);
            }
            $devices[$result['device']] = $result['device'];

            if ($templateEmail) {
                // List doesn't matter
                $stats[$result['device']] = $result['count'];
            } elseif (null !== $result['list_id']) {
                if (!isset($stats[$result['list_id']])) {
                    $stats[$result['list_id']] = [];
                }

                if (!isset($stats[$result['list_id']][$result['device']])) {
                    $stats[$result['list_id']][$result['device']] = (int) $result['count'];
                } else {
                    $stats[$result['list_id']][$result['device']] += (int) $result['count'];
                }
            }
        }

        $listCount = 0;
        if (!$templateEmail) {
            $lists     = $email->getLists();
            $listNames = [];
            foreach ($lists as $l) {
                $listNames[$l->getId()] = $l->getName();
            }
            $listCount = count($listNames);
        }

        natcasesort($devices);
        $chart = new BarChart(array_values($devices));

        if ($templateEmail) {
            // Populate the data
            $chart->setDataset(
                null,
                array_values($stats),
                0
            );
        } else {
            $combined = [];
            $key      = ($listCount > 1) ? 1 : 0;
            foreach ($listNames as $id => $name) {
                // Fill in missing devices
                $listStats = [];
                foreach ($devices as $device) {
                    $listStat    = (!isset($stats[$id][$device])) ? 0 : $stats[$id][$device];
                    $listStats[] = $listStat;

                    if (!isset($combined[$device])) {
                        $combined[$device] = 0;
                    }

                    $combined[$device] += $listStat;
                }

                // Populate the data
                $chart->setDataset(
                    $name,
                    $listStats,
                    $key
                );

                ++$key;
            }

            if ($listCount > 1) {
                $chart->setDataset(
                    $this->translator->trans('mautic.email.lists.combined'),
                    array_values($combined),
                    0
                );
            }
        }

        return $chart->render();
    }

    /**
     * @param bool $includeVariants
     *
     * @throws \Mautic\EmailBundle\Stats\Exception\InvalidStatHelperException
     */
    public function getEmailGeneralStats($email, $includeVariants, $unit, \DateTime $dateFrom, \DateTime $dateTo): array
    {
        if (!$email instanceof Email) {
            $email = $this->getEntity($email);
        }

        $ids = ($includeVariants) ? $email->getRelatedEntityIds() : [$email->getId()];

        $chart = new LineChart($unit, $dateFrom, $dateTo);

        $fetchOptions = new EmailStatOptions();
        $fetchOptions->setEmailIds($ids);
        $fetchOptions->setCanViewOthers($this->security->isGranted('email:emails:viewother'));
        $fetchOptions->setUnit($chart->getUnit());

        $chart->setDataset(
            $this->translator->trans('mautic.email.sent.emails'),
            $this->statsCollectionHelper->fetchSentStats($dateFrom, $dateTo, $fetchOptions)
        );

        $chart->setDataset(
            $this->translator->trans('mautic.email.read.emails'),
            $this->statsCollectionHelper->fetchOpenedStats($dateFrom, $dateTo, $fetchOptions)
        );

        $chart->setDataset(
            $this->translator->trans('mautic.email.failed.emails'),
            $this->statsCollectionHelper->fetchFailedStats($dateFrom, $dateTo, $fetchOptions)
        );

        $chart->setDataset(
            $this->translator->trans('mautic.email.clicked'),
            $this->statsCollectionHelper->fetchClickedStats($dateFrom, $dateTo, $fetchOptions)
        );

        $chart->setDataset(
            $this->translator->trans('mautic.email.unsubscribed'),
            $this->statsCollectionHelper->fetchUnsubscribedStats($dateFrom, $dateTo, $fetchOptions)
        );

        $chart->setDataset(
            $this->translator->trans('mautic.email.bounced'),
            $this->statsCollectionHelper->fetchBouncedStats($dateFrom, $dateTo, $fetchOptions)
        );

        return $chart->render();
    }

    /**
     * Get an array of tracked links.
     */
    public function getEmailClickStats($emailId): array
    {
        return $this->pageTrackableModel->getTrackableList('email', $emailId);
    }

    /**
     * Get the number of leads this email will be sent to.
     *
     * @param mixed $listId          Leads for a specific lead list
     * @param bool  $countOnly       If true, return count otherwise array of leads
     * @param int   $limit           Max number of leads to retrieve
     * @param bool  $includeVariants If false, emails sent to a variant will not be included
     * @param int   $minContactId    Filter by min contact ID
     * @param int   $maxContactId    Filter by max contact ID
     * @param bool  $countWithMaxMin Add min_id and max_id info to the count result
     * @param bool  $storeToCache    Whether to store the result to the cache
     *
     * @return int|array
     */
    public function getPendingLeads(
        Email $email,
        $listId = null,
        $countOnly = false,
        $limit = null,
        $includeVariants = true,
        $minContactId = null,
        $maxContactId = null,
        $countWithMaxMin = false,
        $storeToCache = true,
        int $maxThreads = null,
        int $threadId = null
    ) {
        $variantIds = ($includeVariants) ? $email->getRelatedEntityIds() : null;
        $total      = $this->getRepository()->getEmailPendingLeads(
            $email->getId(),
            $variantIds,
            $listId,
            $countOnly,
            $limit,
            $minContactId,
            $maxContactId,
            $countWithMaxMin,
            $maxThreads,
            $threadId
        );

        if ($storeToCache) {
            if ($countOnly && $countWithMaxMin) {
                $toStore = $total['count'];
            } elseif ($countOnly) {
                $toStore = $total;
            } else {
                $toStore = count($total);
            }

            $this->cacheStorageHelper->set(sprintf('%s|%s|%s', 'email', $email->getId(), 'pending'), $toStore);
        }

        return $total;
    }

    /**
     * @param bool $includeVariants
     */
    public function getQueuedCounts(Email $email, $includeVariants = true): int
    {
        $ids = ($includeVariants) ? $email->getRelatedEntityIds() : null;
        if (!in_array($email->getId(), $ids)) {
            $ids[] = $email->getId();
        }

        $queued = (int) $this->messageQueueModel->getQueuedChannelCount('email', $ids);
        $this->cacheStorageHelper->set(sprintf('%s|%s|%s', 'email', $email->getId(), 'queued'), $queued);

        return $queued;
    }

    public function getDeliveredCount(Email $email, bool $includeVariants = false): int
    {
        $emailIds = ($includeVariants && ($email->isVariant() || $email->isTranslation())) ? $email->getRelatedEntityIds() : [$email->getId()];

        $statRepo = $this->getStatRepository();

        /** @var \Mautic\LeadBundle\Entity\DoNotContactRepository $dncRepo */
        $dncRepo = $this->em->getRepository(DoNotContact::class);

        $failedCount    = (int) $statRepo->getFailedCount($emailIds);
        $bouncedCount   = (int) $dncRepo->getCount('email', $emailIds, DoNotContact::BOUNCED);
        $sentCount      = (int) $email->getSentCount($includeVariants);
        $deliveredCount = $sentCount - $failedCount - $bouncedCount;

        // we never want to display a negative number of delivered emails
        return max($deliveredCount, 0);
    }

    /**
     * Send an email to lead lists.
     *
     * @param array    $lists
     * @param int|null $limit
     * @param int|null $batch        True to process and batch all pending leads
     * @param int      $minContactId
     * @param int      $maxContactId
     *
     * @return array array(int $sentCount, int $failedCount, array $failedRecipientsByList)
     */
    public function sendEmailToLists(
        Email $email,
        $lists = null,
        $limit = null,
        $batch = null,
        OutputInterface $output = null,
        $minContactId = null,
        $maxContactId = null,
        int $maxThreads = null,
        int $threadId = null
    ): array {
        // get the leads
        if (empty($lists)) {
            $lists = $email->getLists();
        }

        // Safety check
        if ('list' !== $email->getEmailType()) {
            return [0, 0, []];
        }

        // Doesn't make sense to send unpublished emails. Probably a user error.
        // @todo throw an exception in Mautic 3 here.
        if (!$email->isPublished()) {
            return [0, 0, []];
        }

        $options = [
            'source'        => ['email', $email->getId()],
            'allowResends'  => false,
            'customHeaders' => [
                'Precedence' => 'Bulk',
                'X-EMAIL-ID' => $email->getId(),
            ],
        ];

        $failedRecipientsByList = [];
        $sentCount              = 0;
        $failedCount            = 0;

        $progress = false;
        if ($batch && $output) {
            $progressCounter = 0;
            $totalLeadCount  = $this->getPendingLeads($email, null, true, null, true, $minContactId, $maxContactId, false, false, $maxThreads, $threadId);
            if (!$totalLeadCount) {
                return [0, 0, []];
            }

            // Broadcast send through CLI
            $output->writeln("\n<info>".$email->getName().'</info>');
            $progress = new ProgressBar($output, $totalLeadCount);
        }

        foreach ($lists as $list) {
            if (!$batch && null !== $limit && $limit <= 0) {
                // Hit the max for this batch
                break;
            }

            $options['listId'] = $list->getId();
            $leads             = $this->getPendingLeads($email, $list->getId(), false, $batch ?: $limit, true, $minContactId, $maxContactId, false, false, $maxThreads, $threadId);
            $leadCount         = count($leads);

            while ($leadCount) {
                if (null != $limit) {
                    // Only retrieve the difference between what has already been sent and the limit
                    $limit -= $leadCount;

                    // recalculate
                    if ($limit < 0) {
                        $leads     = array_slice($leads, 0, $limit);
                        $leadCount = count($leads);
                        $limit     = 0;
                    }
                }

                $sentCount += $leadCount;

                $listErrors = $this->sendEmail($email, $leads, $options);

                if (!empty($listErrors)) {
                    $listFailedCount = count($listErrors);

                    $sentCount -= $listFailedCount;
                    $failedCount += $listFailedCount;

                    $failedRecipientsByList[$options['listId']] = $listErrors;
                }

                if (null !== $limit && 0 == $limit) {
                    break;
                }

                if ($batch) {
                    if ($progress) {
                        $progressCounter += $leadCount;
                        $progress->setProgress($progressCounter);
                    }

                    // Get the next batch of leads
                    $leads     = $this->getPendingLeads($email, $list->getId(), false, $batch, true, $minContactId, $maxContactId, false, false, $maxThreads, $threadId);
                    $leadCount = count($leads);
                } else {
                    $leadCount = 0;
                }
            }
        }

        if ($progress) {
            $progress->finish();
        }

        return [$sentCount, $failedCount, $failedRecipientsByList];
    }

    /**
     * Gets template, stats, weights, etc for an email in preparation to be sent.
     *
     * @param bool $includeVariants
     *
     * @return array
     */
    public function &getEmailSettings(Email $email, $includeVariants = true)
    {
        if (empty($this->emailSettings[$email->getId()])) {
            // used to house slots so they don't have to be fetched over and over for same template
            // BC for Mautic v1 templates
            $slots = [];
            if ($template = $email->getTemplate()) {
                $slots[$template] = $this->themeHelper->getTheme($template)->getSlots('email');
            }

            // store the settings of all the variants in order to properly disperse the emails
            // set the parent's settings
            $emailSettings = [
                $email->getId() => [
                    'template'     => $email->getTemplate(),
                    'slots'        => $slots,
                    'sentCount'    => $email->getSentCount(),
                    'variantCount' => $email->getVariantSentCount(),
                    'isVariant'    => null !== $email->getVariantStartDate(),
                    'entity'       => $email,
                    'translations' => $email->getTranslations(true),
                    'languages'    => ['default' => $email->getId()],
                ],
            ];

            if ($emailSettings[$email->getId()]['translations']) {
                // Add in the sent counts for translations of this email
                /** @var Email $translation */
                foreach ($emailSettings[$email->getId()]['translations'] as $translation) {
                    if ($translation->isPublished()) {
                        $emailSettings[$email->getId()]['sentCount'] += $translation->getSentCount();
                        $emailSettings[$email->getId()]['variantCount'] += $translation->getVariantSentCount();

                        // Prevent empty key due to misconfiguration - pretty much ignored
                        if (!$language = $translation->getLanguage()) {
                            $language = 'unknown';
                        }
                        $core = $this->getTranslationLocaleCore($language);
                        if (!isset($emailSettings[$email->getId()]['languages'][$core])) {
                            $emailSettings[$email->getId()]['languages'][$core] = [];
                        }
                        $emailSettings[$email->getId()]['languages'][$core][$language] = $translation->getId();
                    }
                }
            }

            if ($includeVariants && $email->isVariant()) {
                // get a list of variants for A/B testing
                $childrenVariant = $email->getVariantChildren();

                if (count($childrenVariant)) {
                    $variantWeight = 0;
                    $totalSent     = $emailSettings[$email->getId()]['variantCount'];

                    foreach ($childrenVariant as $child) {
                        if ($child->isPublished()) {
                            $useSlots = [];
                            if ($template = $child->getTemplate()) {
                                if (isset($slots[$template])) {
                                    $useSlots = $slots[$template];
                                } else {
                                    $slots[$template] = $this->themeHelper->getTheme($template)->getSlots('email');
                                    $useSlots         = $slots[$template];
                                }
                            }
                            $variantSettings                = $child->getVariantSettings();
                            $emailSettings[$child->getId()] = [
                                'template'     => $child->getTemplate(),
                                'slots'        => $useSlots,
                                'sentCount'    => $child->getSentCount(),
                                'variantCount' => $child->getVariantSentCount(),
                                'isVariant'    => null !== $email->getVariantStartDate(),
                                'weight'       => ($variantSettings['weight'] / 100),
                                'entity'       => $child,
                                'translations' => $child->getTranslations(true),
                                'languages'    => ['default' => $child->getId()],
                            ];

                            $variantWeight += $variantSettings['weight'];

                            if ($emailSettings[$child->getId()]['translations']) {
                                // Add in the sent counts for translations of this email
                                /** @var Email $translation */
                                foreach ($emailSettings[$child->getId()]['translations'] as $translation) {
                                    if ($translation->isPublished()) {
                                        $emailSettings[$child->getId()]['sentCount'] += $translation->getSentCount();
                                        $emailSettings[$child->getId()]['variantCount'] += $translation->getVariantSentCount();

                                        // Prevent empty key due to misconfiguration - pretty much ignored
                                        if (!$language = $translation->getLanguage()) {
                                            $language = 'unknown';
                                        }
                                        $core = $this->getTranslationLocaleCore($language);
                                        if (!isset($emailSettings[$child->getId()]['languages'][$core])) {
                                            $emailSettings[$child->getId()]['languages'][$core] = [];
                                        }
                                        $emailSettings[$child->getId()]['languages'][$core][$language] = $translation->getId();
                                    }
                                }
                            }

                            $totalSent += $emailSettings[$child->getId()]['variantCount'];
                        }
                    }

                    // set parent weight
                    $emailSettings[$email->getId()]['weight'] = ((100 - $variantWeight) / 100);
                } else {
                    $emailSettings[$email->getId()]['weight'] = 1;
                }
            }

            $this->emailSettings[$email->getId()] = $emailSettings;
        }

        if ($includeVariants && $email->isVariant()) {
            // now find what percentage of current leads should receive the variants
            if (!isset($totalSent)) {
                $totalSent = 0;
                foreach ($this->emailSettings[$email->getId()] as $details) {
                    $totalSent += $details['variantCount'];
                }
            }

            foreach ($this->emailSettings[$email->getId()] as &$details) {
                // Determine the deficit for email ordering
                if ($totalSent) {
                    $details['weight_deficit'] = $details['weight'] - ($details['variantCount'] / $totalSent);
                    $details['send_weight']    = ($details['weight'] - ($details['variantCount'] / $totalSent)) + $details['weight'];
                } else {
                    $details['weight_deficit'] = $details['weight'];
                    $details['send_weight']    = $details['weight'];
                }
            }

            // Reorder according to send_weight so that campaigns which currently send one at a time alternate
            uasort($this->emailSettings[$email->getId()], function ($a, $b): int {
                if ($a['weight_deficit'] === $b['weight_deficit']) {
                    if ($a['variantCount'] === $b['variantCount']) {
                        return 0;
                    }

                    // if weight is the same - sort by least number sent
                    return ($a['variantCount'] < $b['variantCount']) ? -1 : 1;
                }

                // sort by the one with the greatest deficit first
                return ($a['weight_deficit'] > $b['weight_deficit']) ? -1 : 1;
            });
        }

        return $this->emailSettings[$email->getId()];
    }

    /**
     * Send an email to lead(s).
     *
     * @param $options = array()
     *                 array source array('model', 'id')
     *                 array emailSettings
     *                 int   listId
     *                 bool  allowResends     If false, exact emails (by id) already sent to the lead will not be resent
     *                 bool  ignoreDNC        If true, emails listed in the do not contact table will still get the email
     *                 array assetAttachments Array of optional Asset IDs to attach
     *
     * @return string[]|bool|string|null
     */
    public function sendEmail(Email $email, $leads, $options = [])
    {
        $listId              = ArrayHelper::getValue('listId', $options);
        $ignoreDNC           = ArrayHelper::getValue('ignoreDNC', $options, false);
        $tokens              = ArrayHelper::getValue('tokens', $options, []);
        $assetAttachments    = ArrayHelper::getValue('assetAttachments', $options, []);
        $customHeaders       = ArrayHelper::getValue('customHeaders', $options, []);
        $emailType           = ArrayHelper::getValue('email_type', $options, '');
        $isMarketing         = (in_array($emailType, [MailHelper::EMAIL_TYPE_MARKETING]) || !empty($listId));
        $emailAttempts       = ArrayHelper::getValue('email_attempts', $options, 3);
        $emailPriority       = ArrayHelper::getValue('email_priority', $options, MessageQueue::PRIORITY_NORMAL);
        $messageQueue        = ArrayHelper::getValue('resend_message_queue', $options);
        $returnErrorMessages = ArrayHelper::getValue('return_errors', $options, false);
        $channel             = ArrayHelper::getValue('channel', $options);
        $dncAsError          = ArrayHelper::getValue('dnc_as_error', $options, false);
        $errors              = [];

        if (empty($channel)) {
            $channel = $options['source'] ?? [];
        }

        if (!$email->getId()) {
            return false;
        }

        // Ensure $sendTo is indexed by lead ID
        $leadIds     = [];
        $singleEmail = false;
        if (isset($leads['id'])) {
            $singleEmail           = $leads['id'];
            $leadIds[$leads['id']] = $leads['id'];
            $leads                 = [$leads['id'] => $leads];
            $sendTo                = $leads;
        } else {
            $sendTo = [];
            foreach ($leads as $lead) {
                $sendTo[$lead['id']]  = $lead;
                $leadIds[$lead['id']] = $lead['id'];
            }
        }

        /** @var EmailRepository $emailRepo */
        $emailRepo = $this->getRepository();

        // get email settings such as templates, weights, etc
        $emailSettings = &$this->getEmailSettings($email);

        if (!$ignoreDNC) {
            $dnc = $emailRepo->getDoNotEmailList($leadIds);

            foreach ($dnc as $removeMeId => $removeMeEmail) {
                if ($dncAsError) {
                    $errors[$removeMeId] = $this->translator->trans('mautic.email.dnc');
                }
                unset($sendTo[$removeMeId]);
                unset($leadIds[$removeMeId]);
            }
        }

        // Process frequency rules for email
        if ($isMarketing && count($sendTo)) {
            $campaignEventId = (is_array($channel) && !empty($channel) && 'campaign.event' === $channel[0] && !empty($channel[1])) ? $channel[1]
                : null;
            $this->messageQueueModel->processFrequencyRules(
                $sendTo,
                'email',
                $email->getId(),
                $campaignEventId,
                $emailAttempts,
                $emailPriority,
                $messageQueue
            );
        }

        // get a count of leads
        $count = count($sendTo);

        // no one to send to so bail or if marketing email from a campaign has been put in a queue
        if (empty($count)) {
            if ($returnErrorMessages) {
                return $singleEmail && isset($errors[$singleEmail]) ? $errors[$singleEmail] : $errors;
            }

            return $singleEmail ? true : $errors;
        }

        // Hydrate contacts with company profile fields
        $this->getContactCompanies($sendTo);

        foreach ($emailSettings as $eid => $details) {
            if (isset($details['send_weight'])) {
                $emailSettings[$eid]['limit'] = ceil($count * $details['send_weight']);
            } else {
                $emailSettings[$eid]['limit'] = $count;
            }
        }

        // Randomize the contacts for statistic purposes
        shuffle($sendTo);

        // Organize the contacts according to the variant and translation they are to receive
        $groupedContactsByEmail = [];
        $offset                 = 0;
        foreach ($emailSettings as $eid => $details) {
            if (empty($details['limit'])) {
                continue;
            }
            $groupedContactsByEmail[$eid] = [];
            if ($details['limit']) {
                // Take a chunk of contacts based on variant weights
                if ($batchContacts = array_slice($sendTo, $offset, $details['limit'])) {
                    $offset += $details['limit'];

                    // Group contacts by preferred locale
                    foreach ($batchContacts as $key => $contact) {
                        if (!empty($contact['preferred_locale'])) {
                            $locale     = $contact['preferred_locale'];
                            $localeCore = $this->getTranslationLocaleCore($locale);

                            if (isset($details['languages'][$localeCore])) {
                                if (isset($details['languages'][$localeCore][$locale])) {
                                    // Exact match
                                    $translatedId                                  = $details['languages'][$localeCore][$locale];
                                    $groupedContactsByEmail[$eid][$translatedId][] = $contact;
                                } else {
                                    // Grab the closest match
                                    $bestMatch                                     = array_keys($details['languages'][$localeCore])[0];
                                    $translatedId                                  = $details['languages'][$localeCore][$bestMatch];
                                    $groupedContactsByEmail[$eid][$translatedId][] = $contact;
                                }

                                unset($batchContacts[$key]);
                            }
                        }
                    }

                    // If there are any contacts left over, assign them to the default
                    if (count($batchContacts)) {
                        $translatedId                                = $details['languages']['default'];
                        $groupedContactsByEmail[$eid][$translatedId] = $batchContacts;
                    }
                }
            }
        }

        foreach ($groupedContactsByEmail as $parentId => $translatedEmails) {
            $useSettings = $emailSettings[$parentId];
            foreach ($translatedEmails as $translatedId => $contacts) {
                $emailEntity = ($translatedId === $parentId) ? $useSettings['entity'] : $useSettings['translations'][$translatedId];

                $this->sendModel->setEmail($emailEntity, $channel, $customHeaders, $assetAttachments, $emailType)
                    ->setListId($listId);

                foreach ($contacts as $contact) {
                    try {
                        $this->sendModel->setContact($contact, $tokens)
                            ->send();

                        // Update $emailSetting so campaign a/b tests are handled correctly
                        ++$emailSettings[$parentId]['sentCount'];

                        if (!empty($emailSettings[$parentId]['isVariant'])) {
                            ++$emailSettings[$parentId]['variantCount'];
                        }
                    } catch (FailedToSendToContactException) {
                        // move along to the next contact
                    }
                }
            }
        }

        // Flush the queue and store pending email stats
        $this->sendModel->finalFlush();

        // Get the errors to return

        // Don't use array_merge or it will reset contact ID based keys
        $errorMessages  = $errors + $this->sendModel->getErrors();
        $failedContacts = $this->sendModel->getFailedContacts();

        // Get sent counts to update email stats
        $sentCounts = $this->sendModel->getSentCounts();

        // Reset the model for the next send
        $this->sendModel->reset();

        // Update sent counts
        foreach ($sentCounts as $emailId => $count) {
            // Retry a few times in case of deadlock errors
            $strikes = 3;
            while ($strikes >= 0) {
                try {
                    $this->getRepository()->upCountSent($emailId, (int) $count, (bool) $emailSettings[$emailId]['isVariant']);
                    break;
                } catch (\Exception $exception) {
                    error_log($exception);
                }
                --$strikes;
            }
        }

        unset($emailSettings, $options, $sendTo);

        $success = empty($failedContacts);
        if (!$success && $returnErrorMessages) {
            return $singleEmail ? $errorMessages[$singleEmail] : $errorMessages;
        }

        return $singleEmail ? $success : $failedContacts;
    }

    /**
     * Send an email to lead(s).
     *
     * @param array|int $users
     * @param bool      $saveStat
     *
     * @return bool|string[]
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function sendEmailToUser(
        Email $email,
        $users,
        array $lead = null,
        array $tokens = [],
        array $assetAttachments = [],
        $saveStat = false,
        array $to = [],
        array $cc = [],
        array $bcc = []
    ) {
        if (!$emailId = $email->getId()) {
            return false;
        }

        // In case only user ID was provided
        if (!is_array($users)) {
            $users = [['id' => $users]];
        }

        // Get email settings
        $emailSettings = &$this->getEmailSettings($email, false);

        // No one to send to so bail
        if (empty($users) && empty($to)) {
            return false;
        }

        $mailer            = $this->mailHelper->getMailer();
        if (!isset($lead['companies'])) {
            $lead['companies'] = $this->companyModel->getRepository()->getCompaniesByLeadId($lead['id']);
        }
        $mailer->setLead($lead, true);
        $mailer->setTokens($tokens);
        $mailer->setEmail($email, false, $emailSettings[$emailId]['slots'], $assetAttachments, !$saveStat);
        $mailer->setCc($cc);
        $mailer->setBcc($bcc);

        $errors = [];

        $firstMail = true;
        foreach ($to as $toAddress) {
            $idHash = uniqid();
            $mailer->setIdHash($idHash, $saveStat);

            if (!$mailer->addTo($toAddress)) {
                $errors[] = "{$toAddress}: ".$this->translator->trans('mautic.email.bounce.reason.bad_email');
                continue;
            }

            if (!$mailer->queue(true)) {
                $errorArray = $mailer->getErrors();
                unset($errorArray['failures']);
                $errors[] = "{$toAddress}: ".implode('; ', $errorArray);
            }

            if ($saveStat) {
                $saveEntities[] = $mailer->createEmailStat(false, $toAddress);
            }

            // If this is the first message, flush the queue. This process clears the cc and bcc.
            if (true === $firstMail) {
                try {
                    $this->flushQueue($mailer);
                } catch (EmailCouldNotBeSentException $e) {
                    $errors[] = $e->getMessage();
                }
                $firstMail = false;
            }
        }

        foreach ($users as $user) {
            $idHash = uniqid();
            $mailer->setIdHash($idHash, $saveStat);

            if (!is_array($user)) {
                $id   = $user;
                $user = ['id' => $id];
            } else {
                $id = $user['id'];
            }

            if (!isset($user['email'])) {
                $userEntity = $this->userModel->getEntity($id);

                if (null === $userEntity) {
                    continue;
                }

                $user['email']     = $userEntity->getEmail();
                $user['firstname'] = $userEntity->getFirstName();
                $user['lastname']  = $userEntity->getLastName();
            }

            if (!$mailer->setTo($user['email'], $user['firstname'].' '.$user['lastname'])) {
                $errors[] = "{$user['email']}: ".$this->translator->trans('mautic.email.bounce.reason.bad_email');
                continue;
            }

            if (!$mailer->queue(true)) {
                $errorArray = $mailer->getErrors();
                unset($errorArray['failures']);
                $errors[] = "{$user['email']}: ".implode('; ', $errorArray);
            }

            if ($saveStat) {
                $saveEntities[] = $mailer->createEmailStat(false, $user['email']);
            }

            // If this is the first message, flush the queue. This process clears the cc and bcc.
            if (true === $firstMail) {
                try {
                    $this->flushQueue($mailer);
                } catch (EmailCouldNotBeSentException $e) {
                    $errors[] = $e->getMessage();
                }
                $firstMail = false;
            }
        }

        try {
            $this->flushQueue($mailer);
        } catch (EmailCouldNotBeSentException $e) {
            $errors[] = $e->getMessage();
        }

        if (isset($saveEntities)) {
            $this->emailStatModel->saveEntities($saveEntities);
        }

        // save some memory
        unset($mailer);

        return $errors;
    }

    /**
     * @throws EmailCouldNotBeSentException
     */
    private function flushQueue(MailHelper $mailer): void
    {
        if (!$mailer->flushQueue()) {
            $errorArray = $mailer->getErrors();
            unset($errorArray['failures']);

            throw new EmailCouldNotBeSentException(implode('; ', $errorArray));
        }
    }

    /**
     * Dispatches EmailSendEvent so you could get tokens form it or tokenized content.
     *
     * @param string $idHash
     */
    public function dispatchEmailSendEvent(Email $email, array $leadFields = [], $idHash = null, array $tokens = []): EmailSendEvent
    {
        $event = new EmailSendEvent(
            null,
            [
                'content'      => $email->getCustomHtml(),
                'email'        => $email,
                'idHash'       => $idHash,
                'tokens'       => $tokens,
                'internalSend' => true,
                'lead'         => $leadFields,
            ]
        );

        $this->dispatcher->dispatch($event, EmailEvents::EMAIL_ON_DISPLAY);

        return $event;
    }

    /**
     * @param int  $reason
     * @param bool $flush
     *
     * @return bool|DoNotContact
     */
    public function setDoNotContact(Stat $stat, $comments, $reason = DoNotContact::BOUNCED, $flush = true)
    {
        $lead = $stat->getLead();

        if ($lead instanceof Lead) {
            $email   = $stat->getEmail();
            $channel = ($email) ? ['email' => $email->getId()] : 'email';

            return $this->doNotContact->addDncForContact($lead->getId(), $channel, $reason, $comments, $flush);
        }

        return false;
    }

    public function setDoNotContactLead(Lead $lead, string $comments, int $reason = DoNotContact::BOUNCED, bool $flush = true): false|DoNotContact
    {
        return $this->doNotContact->addDncForContact($lead->getId(), 'email', $reason, $comments, $flush);
    }

    /**
     * Remove a Lead's EMAIL DNC entry.
     *
     * @param string $email
     */
    public function removeDoNotContact($email): void
    {
        /** @var \Mautic\LeadBundle\Entity\LeadRepository $leadRepo */
        $leadRepo = $this->em->getRepository(Lead::class);
        $leadId   = (array) $leadRepo->getLeadByEmail($email, true);

        /** @var \Mautic\LeadBundle\Entity\Lead[] $leads */
        $leads = [];

        foreach ($leadId as $lead) {
            $leads[] = $leadRepo->getEntity($lead['id']);
        }

        foreach ($leads as $lead) {
            $this->doNotContact->removeDncForContact($lead->getId(), 'email');
        }
    }

    /**
     * @param int    $reason
     * @param string $comments
     * @param bool   $flush
     */
    public function setEmailDoNotContact($email, $reason = DoNotContact::BOUNCED, $comments = '', $flush = true, $leadId = null): array
    {
        /** @var \Mautic\LeadBundle\Entity\LeadRepository $leadRepo */
        $leadRepo = $this->em->getRepository(Lead::class);

        if (null === $leadId) {
            $leadId = (array) $leadRepo->getLeadByEmail($email, true);
        } elseif (!is_array($leadId)) {
            $leadId = [$leadId];
        }

        $dnc = [];
        foreach ($leadId as $lead) {
            $dnc[] = $this->doNotContact->addDncForContact(
                $this->em->getReference(Lead::class, $lead),
                'email',
                $reason,
                $comments,
                $flush
            );
        }

        return $dnc;
    }

    /**
     * Get the settings for a monitored mailbox or false if not enabled.
     *
     * @return bool|array
     */
    public function getMonitoredMailbox($bundleKey, $folderKey)
    {
        if ($this->mailboxHelper->isConfigured($bundleKey, $folderKey)) {
            return $this->mailboxHelper->getMailboxSettings();
        }

        return false;
    }

    /**
     * Joins the email table and limits created_by to currently logged in user.
     */
    public function limitQueryToCreator(QueryBuilder &$q): void
    {
        $q->join('t', MAUTIC_TABLE_PREFIX.'emails', 'e', 'e.id = t.email_id')
            ->andWhere('e.created_by = :userId')
            ->setParameter('userId', $this->userHelper->getUser()->getId());
    }

    /**
     * @param string $column
     * @param bool   $canViewOthers
     */
    public function getBestHours(
        $column,
        \DateTime $dateFrom,
        \DateTime $dateTo,
        array $filter = [],
        $canViewOthers = true,
        $timeFormat = 24
    ): array {
        $companyId  = ArrayHelper::pickValue('companyId', $filter);
        $campaignId = ArrayHelper::pickValue('campaignId', $filter);
        $segmentId  = ArrayHelper::pickValue('segmentId', $filter);

        $format = '%H:00';
        if (12 == $timeFormat) {
            $format = '%h %p';
        }

        $query      = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);

        $q                     = $query->prepareTimeDataQuery('email_stats', $column, $filter);
        $columnWithTimezone    = 't.'.$column;
        $defaultTimezoneOffset = (new DateTimeHelper())->getLocalTimezoneOffset();
        $columnName            = "CONVERT_TZ($columnWithTimezone, '+00:00', '{$defaultTimezoneOffset}')";
        $q->select('CONCAT(TIME_FORMAT('.$columnName.', \''.$format.'\'),\'-\',TIME_FORMAT('.$columnName.' + INTERVAL 1 HOUR, \''.$format.'\'),\'\') as hour, COUNT(t.id) AS count')
        ->groupBy('hour')
        ->orderBy('count', 'DESC')
        ->setMaxResults(24);

        if (!$canViewOthers) {
            $this->limitQueryToCreator($q);
        }

        $this->addCompanyFilter($q, $companyId);
        $this->addCampaignFilter($q, $campaignId);
        $this->addSegmentFilter($q, $segmentId);

        $result = $q->executeQuery()->fetchAllAssociative();

        $chart  = new BarChart(array_column($result, 'hour'));
        $counts = array_column($result, 'count');
        $total  = array_sum($counts);

        array_walk($counts, function (&$percentage) use ($total): void {
            $percentage = round(($percentage / $total) * 100, 1);
        });

        $chart->setDataset($this->translator->trans('mautic.widget.emails.best.hours.reads_total', ['%reads%'=>$total]), $counts);

        return $chart->render();
    }

    /**
     * Get line chart data of emails sent and read.
     *
     * @param string|null $unit          {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param string|null $dateFormat
     * @param bool        $canViewOthers
     *
     * @throws \Mautic\EmailBundle\Stats\Exception\InvalidStatHelperException
     */
    public function getEmailsLineChartData(
        $unit,
        \DateTime $dateFrom,
        \DateTime $dateTo,
        $dateFormat = null,
        array $filter = [],
        $canViewOthers = true
    ): array {
        $fetchOptions = new EmailStatOptions();
        $fetchOptions->setCanViewOthers($canViewOthers);

        $flag    = ArrayHelper::pickValue('flag', $filter, false);
        $dataset = ArrayHelper::pickValue('dataset', $filter, []);

        if (!is_null($companyId = ArrayHelper::pickValue('companyId', $filter, null))) {
            $fetchOptions->setCompanyId((int) $companyId);
        }

        if (!is_null($campaignId = ArrayHelper::pickValue('campaignId', $filter, null))) {
            $fetchOptions->setCampaignId((int) $campaignId);
        }

        if (!is_null($segmentId = ArrayHelper::pickValue('segmentId', $filter, null))) {
            $fetchOptions->setSegmentId((int) $segmentId);
        }

        if (!is_null($emailId = ArrayHelper::pickValue('email_id', $filter, null))) {
            $fetchOptions->setEmailIds([(int) $emailId]);
        }

        // Set anything left over to be passed to prepareTimeDataQuery
        $fetchOptions->setFilters($filter);

        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        if (in_array($flag, ['all', 'sent_and_opened_and_failed', 'sent_and_opened']) || !$flag || in_array('sent', $dataset)) {
            $chart->setDataset(
                $this->translator->trans('mautic.email.sent.emails'),
                $this->statsCollectionHelper->fetchSentStats($dateFrom, $dateTo, $fetchOptions)
            );
        }

        if (in_array($flag, ['all', 'sent_and_opened_and_failed', 'sent_and_opened', 'opened']) || in_array('opened', $dataset)) {
            $chart->setDataset(
                $this->translator->trans('mautic.email.read.emails'),
                $this->statsCollectionHelper->fetchOpenedStats($dateFrom, $dateTo, $fetchOptions)
            );
        }

        if (in_array($flag, ['all', 'sent_and_opened_and_failed', 'failed']) || in_array('failed', $dataset)) {
            $chart->setDataset(
                $this->translator->trans('mautic.email.failed.emails'),
                $this->statsCollectionHelper->fetchFailedStats($dateFrom, $dateTo, $fetchOptions)
            );
        }

        if (in_array($flag, ['all', 'clicked']) || in_array('clicked', $dataset)) {
            $chart->setDataset(
                $this->translator->trans('mautic.email.clicked'),
                $this->statsCollectionHelper->fetchClickedStats($dateFrom, $dateTo, $fetchOptions)
            );
        }

        if (in_array($flag, ['all', 'unsubscribed']) || in_array('unsubscribed', $dataset)) {
            $chart->setDataset(
                $this->translator->trans('mautic.email.unsubscribed'),
                $this->statsCollectionHelper->fetchUnsubscribedStats($dateFrom, $dateTo, $fetchOptions)
            );
        }

        if (in_array($flag, ['all', 'bounced']) || in_array('bounced', $dataset)) {
            $chart->setDataset(
                $this->translator->trans('mautic.email.bounced'),
                $this->statsCollectionHelper->fetchBouncedStats($dateFrom, $dateTo, $fetchOptions)
            );
        }

        return $chart->render();
    }

    /**
     * Get pie chart data of ignored vs opened emails.
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $filters
     * @param bool   $canViewOthers
     */
    public function getIgnoredVsReadPieChartData($dateFrom, $dateTo, $filters = [], $canViewOthers = true): array
    {
        $chart = new PieChart();
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);

        $readFilters                = $filters;
        $readFilters['is_read']     = true;
        $failedFilters              = $filters;
        $failedFilters['is_failed'] = true;

        $sentQ   = $query->getCountQuery('email_stats', 'id', 'date_sent', $filters);
        $readQ   = $query->getCountQuery('email_stats', 'id', 'date_sent', $readFilters);
        $failedQ = $query->getCountQuery('email_stats', 'id', 'date_sent', $failedFilters);

        if (!$canViewOthers) {
            $this->limitQueryToCreator($sentQ);
            $this->limitQueryToCreator($readQ);
            $this->limitQueryToCreator($failedQ);
        }

        $sent   = $query->fetchCount($sentQ);
        $read   = $query->fetchCount($readQ);
        $failed = $query->fetchCount($failedQ);

        $chart->setDataset($this->translator->trans('mautic.email.graph.pie.ignored.read.failed.ignored'), $sent - $read - $failed);
        $chart->setDataset($this->translator->trans('mautic.email.graph.pie.ignored.read.failed.read'), $read);
        $chart->setDataset($this->translator->trans('mautic.email.graph.pie.ignored.read.failed.failed'), $failed);

        return $chart->render();
    }

    /**
     * Get pie chart data of ignored vs opened emails.
     */
    public function getDeviceGranularityPieChartData($dateFrom, $dateTo): array
    {
        $chart = new PieChart();

        $deviceStats = $this->getStatDeviceRepository()->getDeviceStats(
            null,
            $dateFrom,
            $dateTo
        );

        if (empty($deviceStats)) {
            $deviceStats[] = [
                'count'   => 0,
                'device'  => $this->translator->trans('mautic.report.report.noresults'),
                'list_id' => 0,
            ];
        }

        foreach ($deviceStats as $device) {
            $chart->setDataset(
                $device['device'] ?: $this->translator->trans('mautic.core.unknown'),
                $device['count']
            );
        }

        return $chart->render();
    }

    /**
     * Get a list of emails in a date range, grouped by a stat date count.
     *
     * @param int   $limit
     * @param array $filters
     * @param array $options
     *
     * @return array
     */
    public function getEmailStatList($limit = 10, \DateTime $dateFrom = null, \DateTime $dateTo = null, $filters = [], $options = [])
    {
        $canViewOthers = empty($options['canViewOthers']) ? false : $options['canViewOthers'];
        $q             = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(DISTINCT t.id) AS count, e.id, e.name')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 't')
            ->join('t', MAUTIC_TABLE_PREFIX.'emails', 'e', 'e.id = t.email_id')
            ->orderBy('count', 'DESC')
            ->groupBy('e.id')
            ->setMaxResults($limit);

        if (!$canViewOthers) {
            $q->andWhere('e.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);

        if (isset($options['groupBy']) && 'sends' == $options['groupBy']) {
            $chartQuery->applyDateFilters($q, 'date_sent');
        }

        if (isset($options['groupBy']) && 'reads' == $options['groupBy']) {
            $chartQuery->applyDateFilters($q, 'date_read');
        }

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * Get a list of emails in a date range.
     *
     * @param int   $limit
     * @param array $filters
     * @param array $options
     *
     * @return array
     */
    public function getEmailList($limit = 10, \DateTime $dateFrom = null, \DateTime $dateTo = null, $filters = [], $options = [])
    {
        $canViewOthers = empty($options['canViewOthers']) ? false : $options['canViewOthers'];
        $q             = $this->em->getConnection()->createQueryBuilder();
        $q->select('t.id, t.name, t.date_added, t.date_modified')
            ->from(MAUTIC_TABLE_PREFIX.'emails', 't')
            ->setMaxResults($limit);

        if (!$canViewOthers) {
            $q->andWhere('t.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * Get a list of upcoming emails.
     *
     * @param int  $limit
     * @param bool $canViewOthers
     */
    public function getUpcomingEmails($limit = 10, $canViewOthers = true): array
    {
        /** @var \Mautic\CampaignBundle\Entity\LeadEventLogRepository $leadEventLogRepository */
        $leadEventLogRepository = $this->em->getRepository(\Mautic\CampaignBundle\Entity\LeadEventLog::class);
        $leadEventLogRepository->setCurrentUser($this->userHelper->getUser());

        return $leadEventLogRepository->getUpcomingEvents(
            [
                'type'          => 'email.send',
                'limit'         => $limit,
                'canViewOthers' => $canViewOthers,
            ]
        );
    }

    /**
     * @param string $type
     * @param string $filter
     * @param int    $limit
     * @param int    $start
     * @param array  $options
     */
    public function getLookupResults($type, $filter = '', $limit = 10, $start = 0, $options = []): array
    {
        $results = [];
        switch ($type) {
            case 'email':
                $emailRepo = $this->getRepository();
                $emailRepo->setCurrentUser($this->userHelper->getUser());
                $emails = $emailRepo->getEmailList(
                    $filter,
                    $limit,
                    $start,
                    $this->security->isGranted('email:emails:viewother'),
                    $options['top_level'] ?? false,
                    $options['email_type'] ?? null,
                    $options['ignore_ids'] ?? [],
                    $options['variant_parent'] ?? null
                );

                foreach ($emails as $email) {
                    if (empty($options['name_is_key'])) {
                        $results[$email['language']][$email['id']] = $email['name'];
                    } else {
                        $results[$email['language']][$email['name']] = $email['id'];
                    }
                }

                // sort by language
                ksort($results);

                break;
        }

        return $results;
    }

    private function getContactCompanies(array &$sendTo): void
    {
        $fetchCompanies = [];
        foreach ($sendTo as $key => $contact) {
            if (!isset($contact['companies'])) {
                $fetchCompanies[$contact['id']] = $key;
                $sendTo[$key]['companies']      = [];
            }
        }

        if (!empty($fetchCompanies)) {
            // Simple dbal query that fetches lead_id IN $fetchCompanies and returns as array
            $companies = $this->companyModel->getRepository()->getCompaniesForContacts(array_keys($fetchCompanies));

            foreach ($companies as $contactId => $contactCompanies) {
                $key                       = $fetchCompanies[$contactId];
                $sendTo[$key]['companies'] = $contactCompanies;
            }
        }
    }

    /**
     * Send an email to lead(s).
     *
     * @param array                   $tokens
     * @param array                   $assetAttachments
     * @param array<string>|Lead|null $leadFields
     * @param bool                    $saveStat
     *
     * @return bool|string[]
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function sendSampleEmailToUser(
        $email,
        $users,
        $leadFields = null,
        $tokens = [],
        $assetAttachments = [],
        $saveStat = true
    ) {
        if (!$emailId = $email->getId()) {
            return false;
        }

        if (!is_array($users)) {
            $user  = ['id' => $users];
            $users = [$user];
        }

        // get email settings
        $emailSettings = &$this->getEmailSettings($email, false);

        // noone to send to so bail
        if (empty($users)) {
            return false;
        }

        // Generate and replace tokens
        $event = new EmailSendEvent(
            null,
            [
                'content'      => $email->getCustomHtml(),
                'email'        => $email,
                'idHash'       => 'xxxxxxxxxxxxxx', // bogus ID
                'tokens'       => ['{tracking_pixel}' => ''], // Override tracking_pixel
                'internalSend' => true,
                'lead'         => $leadFields,
            ]
        );
        $this->dispatcher->dispatch($event, EmailEvents::EMAIL_ON_DISPLAY);

        $mailer = $this->mailHelper->getSampleMailer();
        $mailer->setLead($leadFields, true);
        $mailer->setTokens($tokens);
        $mailer->setEmail($email, false, $emailSettings[$emailId]['slots'], $assetAttachments, !$saveStat);

        $errors = [];
        foreach ($users as $user) {
            $idHash = uniqid();
            $mailer->setIdHash($idHash, $saveStat);

            if (!is_array($user)) {
                $id   = $user;
                $user = ['id' => $id];
            } else {
                $id = $user['id'];
            }

            if (!isset($user['email'])) {
                $userEntity        = $this->userModel->getEntity($id);
                $user['email']     = $userEntity->getEmail();
                $user['firstname'] = $userEntity->getFirstName();
                $user['lastname']  = $userEntity->getLastName();
            }

            if (!$mailer->setTo($user['email'], $user['firstname'].' '.$user['lastname'])) {
                $errors[] = "{$user['email']}: ".$this->translator->trans('mautic.email.bounce.reason.bad_email');
            } else {
                if (!$mailer->queue(true)) {
                    $errorArray = $mailer->getErrors();
                    unset($errorArray['failures']);
                    $errors[] = "{$user['email']}: ".implode('; ', $errorArray);
                }

                if ($saveStat) {
                    $saveEntities[] = $mailer->createEmailStat(false, $user['email']);
                }
            }
        }

        // flush the message
        if (!$mailer->flushQueue()) {
            $errorArray = $mailer->getErrors();
            unset($errorArray['failures']);
            $errors[] = implode('; ', $errorArray);
        }

        if (isset($saveEntities)) {
            $this->emailStatModel->saveEntities($saveEntities);
        }

        // save some memory
        unset($mailer);

        return $errors;
    }

    public function getEmailsIdsWithDependenciesOnSegment($segmentId): array
    {
        $entities =  $this->getEntities(
            [
                'filter'         => [
                    'force' => [
                        [
                            'column' => 'l.id',
                            'expr'   => 'eq',
                            'value'  => $segmentId,
                        ],
                    ],
                ],
            ]
        );

        $ids = [];
        foreach ($entities as $entity) {
            $ids[] = $entity->getId();
        }

        return $ids;
    }

    public function isUpdatingTranslationChildren(): bool
    {
        return $this->updatingTranslationChildren;
    }

    /**
     * @param string                                   $route
     * @param array<string, string>|array<string, int> $routeParams
     * @param bool                                     $absolute
     * @param array<array<string>>                     $clickthrough
     *
     * @return string
     */
    public function buildUrl($route, $routeParams = [], $absolute = true, $clickthrough = [])
    {
        $parts = parse_url($this->coreParametersHelper->get('site_url') ?: '');

        $context         = $this->router->getContext();
        $original_host   = $context->getHost();
        $original_scheme = $context->getScheme();

        if (!empty($parts['host'])) {
            $this->router->getContext()->setHost($parts['host']);
        }
        if (!empty($parts['scheme'])) {
            $this->router->getContext()->setScheme($parts['scheme']);
        }

        $url = parent::buildUrl($route, $routeParams, $absolute, $clickthrough);

        $context->setHost($original_host);
        $context->setScheme($original_scheme);

        return $url;
    }
}
