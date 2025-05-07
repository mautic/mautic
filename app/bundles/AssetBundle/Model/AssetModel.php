<?php

namespace Mautic\AssetBundle\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use Mautic\AssetBundle\AssetEvents;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\Download;
use Mautic\AssetBundle\Event\AssetEvent;
use Mautic\AssetBundle\Event\AssetLoadEvent;
use Mautic\AssetBundle\Form\Type\AssetType;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FileHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\GlobalSearchInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory\DeviceDetectorFactoryInterface;
use Mautic\LeadBundle\Tracker\Service\DeviceCreatorService\DeviceCreatorServiceInterface;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<Asset>
 */
class AssetModel extends FormModel implements GlobalSearchInterface
{
    /**
     * @var int
     */
    protected $maxAssetSize;

    public function __construct(
        protected LeadModel $leadModel,
        protected CategoryModel $categoryModel,
        private RequestStack $requestStack,
        protected IpLookupHelper $ipLookupHelper,
        private DeviceCreatorServiceInterface $deviceCreatorService,
        private DeviceDetectorFactoryInterface $deviceDetectorFactory,
        private DeviceTrackingServiceInterface $deviceTrackingService,
        private ContactTracker $contactTracker,
        EntityManager $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $logger,
        CoreParametersHelper $coreParametersHelper,
    ) {
        $this->maxAssetSize           = $coreParametersHelper->get('max_size');

        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $logger, $coreParametersHelper);
    }

    public function saveEntity($entity, $unlock = true): void
    {
        if (empty($this->inConversion)) {
            $alias = $entity->getAlias();
            if (empty($alias)) {
                $alias = $entity->getTitle();
            }
            $alias = $this->cleanAlias($alias, '', 0, '-');

            // make sure alias is not already taken
            $repo      = $this->getRepository();
            $testAlias = $alias;
            $count     = $repo->checkUniqueAlias($testAlias, $entity);
            $aliasTag  = $count;

            while ($count) {
                $testAlias = $alias.$aliasTag;
                $count     = $repo->checkUniqueAlias($testAlias, $entity);
                ++$aliasTag;
            }
            if ($testAlias != $alias) {
                $alias = $testAlias;
            }
            $entity->setAlias($alias);
        }

        if (!$entity->isNew()) {
            // increase the revision
            $revision = $entity->getRevision();
            ++$revision;
            $entity->setRevision($revision);
        }

        parent::saveEntity($entity, $unlock);
    }

    /**
     * @param array $systemEntry
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Exception
     */
    public function trackDownload($asset, $request = null, int $code = 200, $systemEntry = []): void
    {
        // Don't skew results with in-house downloads
        if (empty($systemEntry) && !$this->security->isAnonymous()) {
            return;
        }

        if (null == $request) {
            $request = $this->requestStack->getCurrentRequest();
        }

        if (!($request instanceof Request)) {
            // likely this download came via a cron (no request), do not bother logging the download.
            // https://github.com/mautic/mautic/issues/13577
            return;
        }

        $download = new Download();
        $download->setDateDownload(new \DateTime());
        $download->setUtmCampaign($request->get('utm_campaign'));
        $download->setUtmContent($request->get('utm_content'));
        $download->setUtmMedium($request->get('utm_medium'));
        $download->setUtmSource($request->get('utm_source'));
        $download->setUtmTerm($request->get('utm_term'));

        // Download triggered by lead
        if (empty($systemEntry)) {
            // check for any clickthrough info
            $clickthrough = $request->get('ct', false);
            if (!empty($clickthrough)) {
                $clickthrough = $this->decodeArrayFromUrl($clickthrough);

                if (!empty($clickthrough['lead'])) {
                    $lead = $this->leadModel->getEntity($clickthrough['lead']);
                    if (null !== $lead) {
                        $wasTrackedAlready                    = $this->deviceTrackingService->isTracked();
                        $deviceDetector                       = $this->deviceDetectorFactory->create($request->server->get('HTTP_USER_AGENT'));
                        $deviceDetector->parse();
                        $currentDevice                             = $this->deviceCreatorService->getCurrentFromDetector($deviceDetector, $lead);
                        $trackedDevice                             = $this->deviceTrackingService->trackCurrentDevice($currentDevice, false);
                        $trackingId                                = $trackedDevice->getTrackingId();
                        $trackingNewlyGenerated                    = !$wasTrackedAlready;
                        $leadClickthrough                          = true;

                        $this->contactTracker->setTrackedContact($lead);
                    }
                }
                if (!empty($clickthrough['channel'])) {
                    if (1 === count($clickthrough['channel'])) {
                        $channelId = reset($clickthrough['channel']);
                        $channel   = key($clickthrough['channel']);
                    } else {
                        $channel   = $clickthrough['channel'][0];
                        $channelId = (int) $clickthrough['channel'][1];
                    }
                    $download->setSource($channel);
                    $download->setSourceId($channelId);
                } elseif (!empty($clickthrough['source'])) {
                    $download->setSource($clickthrough['source'][0]);
                    $download->setSourceId($clickthrough['source'][1]);
                }

                if (!empty($clickthrough['email'])) {
                    $emailRepo = $this->em->getRepository(Email::class);
                    if ($emailEntity = $emailRepo->getEntity($clickthrough['email'])) {
                        $download->setEmail($emailEntity);
                    }
                }
            }

            if (empty($leadClickthrough)) {
                $wasTrackedAlready         = $this->deviceTrackingService->isTracked();
                $lead                      = $this->contactTracker->getContact();
                $trackedDevice             = $this->deviceTrackingService->getTrackedDevice();
                $trackingId                = null;
                $trackingNewlyGenerated    = false;
                if (null !== $trackedDevice) {
                    $trackingId             = $trackedDevice->getTrackingId();
                    $trackingNewlyGenerated = !$wasTrackedAlready;
                }
            }

            $download->setLead($lead);
        } else {
            $trackingId = '';

            if (isset($systemEntry['lead'])) {
                $lead = $systemEntry['lead'];
                if (!$lead instanceof Lead) {
                    $leadId = is_array($lead) ? $lead['id'] : $lead;
                    $lead   = $this->em->getReference(Lead::class, $leadId);
                }

                $download->setLead($lead);
            }

            if (!empty($systemEntry['source'])) {
                $download->setSource($systemEntry['source'][0]);
                $download->setSourceId($systemEntry['source'][1]);
            }

            if (isset($systemEntry['email'])) {
                $email = $systemEntry['email'];
                if (!$email instanceof Email) {
                    $emailId = is_array($email) ? $email['id'] : $email;
                    $email   = $this->em->getReference(Email::class, $emailId);
                }

                $download->setEmail($email);
            }

            if (isset($systemEntry['tracking_id'])) {
                $trackingId             = $systemEntry['tracking_id'];
                $trackingNewlyGenerated = false;
            } elseif ($this->security->isAnonymous() && !defined('IN_MAUTIC_CONSOLE')) {
                // If the session is anonymous and not triggered via CLI, assume the lead did something to trigger the
                // system forced download such as an email
                $deviceWasTracked       = $this->deviceTrackingService->isTracked();
                $deviceDetector         = $this->deviceDetectorFactory->create($request->server->get('HTTP_USER_AGENT'));
                $deviceDetector->parse();
                $currentDevice          = $this->deviceCreatorService->getCurrentFromDetector($deviceDetector, $lead);
                $trackedDevice          = $this->deviceTrackingService->trackCurrentDevice($currentDevice, false);
                $trackingId             = $trackedDevice->getTrackingId();
                $trackingNewlyGenerated = !$deviceWasTracked;
            }
        }

        $isUnique = true;
        if (!empty($trackingNewlyGenerated)) {
            // Cookie was just generated so this is definitely a unique download
            $isUnique = $trackingNewlyGenerated;
        } elseif (!empty($trackingId)) {
            // Determine if this is a unique download
            $isUnique = $this->getDownloadRepository()->isUniqueDownload($asset->getId(), $trackingId);
        }

        $download->setTrackingId($trackingId);

        if (empty($systemEntry)) {
            $download->setAsset($asset);

            $this->getRepository()->upDownloadCount($asset->getId(), 1, $isUnique);
        }

        // check for existing IP
        $ipAddress = $this->ipLookupHelper->getIpAddress();

        $download->setCode($code);
        $download->setIpAddress($ipAddress);
        $download->setReferer($request->server->get('HTTP_REFERER'));

        // Dispatch event
        if ($this->dispatcher->hasListeners(AssetEvents::ASSET_ON_LOAD)) {
            $event = new AssetLoadEvent($download, $isUnique);
            $this->dispatcher->dispatch($event, AssetEvents::ASSET_ON_LOAD);
        }

        // Wrap in a try/catch to prevent deadlock errors on busy servers
        try {
            $this->em->persist($download);
            $this->em->flush();
        } catch (\Exception $e) {
            if (MAUTIC_ENV === 'dev') {
                throw $e;
            } else {
                error_log($e);
            }
        }

        $this->em->detach($download);
    }

    /**
     * Increase the download count.
     *
     * @param int        $increaseBy
     * @param bool|false $unique
     */
    public function upDownloadCount($asset, $increaseBy = 1, $unique = false): void
    {
        $id = ($asset instanceof Asset) ? $asset->getId() : (int) $asset;

        $this->getRepository()->upDownloadCount($id, $increaseBy, $unique);
    }

    /**
     * @return \Mautic\AssetBundle\Entity\AssetRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(Asset::class);
    }

    /**
     * @return \Mautic\AssetBundle\Entity\DownloadRepository
     */
    public function getDownloadRepository()
    {
        return $this->em->getRepository(Download::class);
    }

    public function getPermissionBase(): string
    {
        return 'asset:assets';
    }

    public function getNameGetter(): string
    {
        return 'getTitle';
    }

    /**
     * @throws NotFoundHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof Asset) {
            throw new MethodNotAllowedHttpException(['Asset']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(AssetType::class, $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     */
    public function getEntity($id = null): ?Asset
    {
        if (null === $id) {
            $entity = new Asset();
        } else {
            $entity = parent::getEntity($id);
        }

        return $entity;
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null): ?Event
    {
        if (!$entity instanceof Asset) {
            throw new MethodNotAllowedHttpException(['Asset']);
        }

        switch ($action) {
            case 'pre_save':
                $name = AssetEvents::ASSET_PRE_SAVE;
                break;
            case 'post_save':
                $name = AssetEvents::ASSET_POST_SAVE;
                break;
            case 'pre_delete':
                $name = AssetEvents::ASSET_PRE_DELETE;
                break;
            case 'post_delete':
                $name = AssetEvents::ASSET_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new AssetEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * Get list of entities for autopopulate fields.
     *
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10)
    {
        $results = [];
        switch ($type) {
            case 'asset':
                $viewOther = $this->security->isGranted('asset:assets:viewother');
                $request   = $this->requestStack->getCurrentRequest();
                $repo      = $this->getRepository();
                $repo->setCurrentUser($this->userHelper->getUser());
                // During the form submit & edit, make sure that the data is checked against available assets
                if ('mautic_segment_action' === $request->get('_route')
                    && (Request::METHOD_POST === $request->getMethod() || 'edit' === $request->get('objectAction'))
                ) {
                    $limit = 0;
                }
                $results = $repo->getAssetList($filter, $limit, 0, $viewOther);
                break;
            case 'category':
                $results = $this->categoryModel->getRepository()->getCategoryList($filter, $limit, 0);
                break;
        }

        return $results;
    }

    /**
     * Generate url for an asset.
     *
     * @param Asset $entity
     * @param bool  $absolute
     * @param array $clickthrough
     *
     * @return string
     */
    public function generateUrl($entity, $absolute = true, $clickthrough = [])
    {
        $assetSlug = $entity->getId().':'.$entity->getAlias();

        $slugs = [
            'slug' => $assetSlug,
        ];

        return $this->buildUrl('mautic_asset_download', $slugs, $absolute, $clickthrough);
    }

    /**
     * Determine the max upload size based on PHP restrictions and config.
     *
     * @param string     $unit          If '', determine the best unit based on the number
     * @param bool|false $humanReadable Return as a human readable filesize
     *
     * @return float
     */
    public function getMaxUploadSize($unit = 'M', $humanReadable = false)
    {
        $maxAssetSize  = $this->maxAssetSize;
        $maxAssetSize  = (-1 == $maxAssetSize || 0 === $maxAssetSize) ? PHP_INT_MAX : FileHelper::convertMegabytesToBytes($maxAssetSize);
        $maxPostSize   = Asset::getIniValue('post_max_size');
        $maxUploadSize = Asset::getIniValue('upload_max_filesize');
        $memoryLimit   = Asset::getIniValue('memory_limit');
        $maxAllowed    = min(array_filter([$maxAssetSize, $maxPostSize, $maxUploadSize, $memoryLimit]));

        if ($humanReadable) {
            $number = Asset::convertBytesToHumanReadable($maxAllowed);
        } else {
            [$number, $unit] = Asset::convertBytesToUnit($maxAllowed, $unit);
        }

        return $number;
    }

    /**
     * @return int|string
     */
    public function getTotalFilesize($assets)
    {
        $firstAsset = is_array($assets) ? reset($assets) : false;
        if ($assets instanceof PersistentCollection || is_object($firstAsset)) {
            $assetIds = [];
            foreach ($assets as $asset) {
                $assetIds[] = $asset->getId();
            }
            $assets = $assetIds;
        }

        if (!is_array($assets)) {
            $assets = [$assets];
        }

        if (empty($assets)) {
            return 0;
        }

        $repo = $this->getRepository();
        $size = $repo->getAssetSize($assets);

        if ($size) {
            $size = Asset::convertBytesToHumanReadable($size);
        }

        return $size;
    }

    /**
     * Get line chart data of downloads.
     *
     * @param string|null $unit          {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param string      $dateFormat
     * @param array       $filter
     * @param bool        $canViewOthers
     */
    public function getDownloadsLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [], $canViewOthers = true): array
    {
        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $q     = $query->prepareTimeDataQuery('asset_downloads', 'date_download', $filter);

        if (!$canViewOthers) {
            $q->join('t', MAUTIC_TABLE_PREFIX.'assets', 'a', 'a.id = t.asset_id')
                ->andWhere('a.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $data = $query->loadAndBuildTimeData($q);

        $chart->setDataset($this->translator->trans('mautic.asset.downloadcount'), $data);

        return $chart->render();
    }

    /**
     * Get pie chart data of unique vs repetitive downloads.
     * Repetitive in this case mean if a lead downloaded any of the assets more than once.
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $filters
     * @param bool   $canViewOthers
     */
    public function getUniqueVsRepetitivePieChartData($dateFrom, $dateTo, $filters = [], $canViewOthers = true): array
    {
        $chart   = new PieChart();
        $query   = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $allQ    = $query->getCountQuery('asset_downloads', 'id', 'date_download', $filters);
        $uniqueQ = $query->getCountQuery('asset_downloads', 'lead_id', 'date_download', $filters, ['getUnique' => true]);

        if (!$canViewOthers) {
            $allQ->join('t', MAUTIC_TABLE_PREFIX.'assets', 'a', 'a.id = t.asset_id')
                ->andWhere('a.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
            $uniqueQ->join('t', MAUTIC_TABLE_PREFIX.'assets', 'a', 'a.id = t.asset_id')
                ->andWhere('a.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $all    = $query->fetchCount($allQ);
        $unique = $query->fetchCount($uniqueQ);

        $repetitive = $all - $unique;
        $chart->setDataset($this->translator->trans('mautic.asset.unique'), $unique);
        $chart->setDataset($this->translator->trans('mautic.asset.repetitive'), $repetitive);

        return $chart->render();
    }

    /**
     * Get a list of popular (by downloads) assets.
     *
     * @param int    $limit
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $filters
     * @param bool   $canViewOthers
     */
    public function getPopularAssets($limit = 10, $dateFrom = null, $dateTo = null, $filters = [], $canViewOthers = true): array
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(DISTINCT t.id) AS download_count, a.id, a.title')
            ->from(MAUTIC_TABLE_PREFIX.'asset_downloads', 't')
            ->join('t', MAUTIC_TABLE_PREFIX.'assets', 'a', 'a.id = t.asset_id')
            ->orderBy('download_count', 'DESC')
            ->groupBy('a.id')
            ->setMaxResults($limit);

        if (!$canViewOthers) {
            $q->andWhere('a.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_download');

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * Get a list of assets in a date range.
     *
     * @param int   $limit
     * @param array $filters
     * @param array $options
     */
    public function getAssetList($limit = 10, \DateTime $dateFrom = null, \DateTime $dateTo = null, $filters = [], $options = []): array
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('t.id, t.title as name, t.date_added, t.date_modified')
            ->from(MAUTIC_TABLE_PREFIX.'assets', 't')
            ->setMaxResults($limit);

        if (!empty($options['canViewOthers'])) {
            $q->andWhere('t.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        return $q->executeQuery()->fetchAllAssociative();
    }
}
