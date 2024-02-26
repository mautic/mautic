<?php

namespace Mautic\PageBundle\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\BuilderModelTrait;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\TranslationModelTrait;
use Mautic\CoreBundle\Model\VariantModelTrait;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\UtmTag;
use Mautic\LeadBundle\Helper\ContactRequestHelper;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\DeviceTracker;
use Mautic\MessengerBundle\Message\PageHitNotification;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\Event\PageEvent;
use Mautic\PageBundle\Event\PageHitEvent;
use Mautic\PageBundle\Form\Type\PageType;
use Mautic\PageBundle\PageEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<Page>
 */
class PageModel extends FormModel
{
    use TranslationModelTrait;
    use VariantModelTrait;
    use BuilderModelTrait;

    /**
     * @var bool
     */
    protected $catInUrl;

    protected \Mautic\CoreBundle\Helper\DateTimeHelper $dateTimeHelper;

    public function __construct(
        protected CookieHelper $cookieHelper,
        protected IpLookupHelper $ipLookupHelper,
        protected LeadModel $leadModel,
        protected FieldModel $leadFieldModel,
        protected RedirectModel $pageRedirectModel,
        protected TrackableModel $pageTrackableModel,
        private MessageBusInterface $messageBus,
        private CompanyModel $companyModel,
        private DeviceTracker $deviceTracker,
        private ContactTracker $contactTracker,
        CoreParametersHelper $coreParametersHelper,
        private ContactRequestHelper $contactRequestHelper,
        EntityManager $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger
    ) {
        $this->dateTimeHelper       = new DateTimeHelper();

        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    public function setCatInUrl($catInUrl): void
    {
        $this->catInUrl = $catInUrl;
    }

    /**
     * @return \Mautic\PageBundle\Entity\PageRepository
     */
    public function getRepository()
    {
        $repo = $this->em->getRepository(\Mautic\PageBundle\Entity\Page::class);
        $repo->setCurrentUser($this->userHelper->getUser());

        return $repo;
    }

    /**
     * @return \Mautic\PageBundle\Entity\HitRepository
     */
    public function getHitRepository()
    {
        return $this->em->getRepository(\Mautic\PageBundle\Entity\Hit::class);
    }

    public function getPermissionBase(): string
    {
        return 'page:pages';
    }

    public function getNameGetter(): string
    {
        return 'getTitle';
    }

    /**
     * @param Page $entity
     * @param bool $unlock
     */
    public function saveEntity($entity, $unlock = true): void
    {
        $pageIds = $entity->getRelatedEntityIds();

        if (empty($this->inConversion)) {
            $alias = $entity->getAlias();
            if (empty($alias)) {
                $alias = $entity->getTitle();
            }
            $alias = $this->cleanAlias($alias, '', 0, '-', ['_']);

            // make sure alias is not already taken
            $repo      = $this->getRepository();
            $testAlias = $alias;
            $count     = $repo->checkPageUniqueAlias($testAlias, $pageIds);
            $aliasTag  = 1;

            while ($count) {
                $testAlias = $alias.$aliasTag;
                $count     = $repo->checkPageUniqueAlias($testAlias, $pageIds);
                ++$aliasTag;
            }
            if ($testAlias != $alias) {
                $alias = $testAlias;
            }
            $entity->setAlias($alias);
        }

        // Set the author for new pages
        $isNew = $entity->isNew();
        if (!$isNew) {
            // increase the revision
            $revision = $entity->getRevision();
            ++$revision;
            $entity->setRevision($revision);
        }

        // Reset a/b test if applicable
        $variantStartDate = new \DateTime();
        $resetVariants    = $this->preVariantSaveEntity($entity, ['setVariantHits'], $variantStartDate);

        parent::saveEntity($entity, $unlock);

        $this->postVariantSaveEntity($entity, $resetVariants, $pageIds, $variantStartDate);
        $this->postTranslationEntitySave($entity);
    }

    /**
     * @param Page $entity
     */
    public function deleteEntity($entity): void
    {
        if ($entity->isVariant() && $entity->getIsPublished()) {
            $this->resetVariants($entity);
        }

        parent::deleteEntity($entity);
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof Page) {
            throw new MethodNotAllowedHttpException(['Page']);
        }

        $formClass = PageType::class;

        if (!empty($options['formName'])) {
            $formClass = $options['formName'];
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create($formClass, $entity, $options);
    }

    public function getEntity($id = null): ?Page
    {
        if (null === $id) {
            $entity = new Page();
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
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null): ?Event
    {
        if (!$entity instanceof Page) {
            throw new MethodNotAllowedHttpException(['Page']);
        }

        switch ($action) {
            case 'pre_save':
                $name = PageEvents::PAGE_PRE_SAVE;
                break;
            case 'post_save':
                $name = PageEvents::PAGE_POST_SAVE;
                break;
            case 'pre_delete':
                $name = PageEvents::PAGE_PRE_DELETE;
                break;
            case 'post_delete':
                $name = PageEvents::PAGE_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new PageEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        }

        return null;
    }

    /**
     * Get list of entities for autopopulate fields.
     *
     * @param string $type
     * @param string $filter
     * @param int    $limit
     *
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10)
    {
        $results = [];
        switch ($type) {
            case 'page':
                $viewOther = $this->security->isGranted('page:pages:viewother');
                $repo      = $this->getRepository();
                $repo->setCurrentUser($this->userHelper->getUser());
                $results = $repo->getPageList($filter, $limit, 0, $viewOther);
                break;
        }

        return $results;
    }

    /**
     * Generate URL for a page.
     *
     * @param Page  $entity
     * @param bool  $absolute
     * @param array $clickthrough
     *
     * @return string
     */
    public function generateUrl($entity, $absolute = true, $clickthrough = [])
    {
        // If this is a variant, then get the parent's URL
        $parent = $entity->getVariantParent();
        if (null != $parent) {
            $entity = $parent;
        }

        $slug = $this->generateSlug($entity);

        return $this->buildUrl('mautic_page_public', ['slug' => $slug], $absolute, $clickthrough);
    }

    /**
     * Generates slug string.
     */
    public function generateSlug($entity): string
    {
        $pageSlug = $entity->getAlias();

        // should the url include the category
        if ($this->catInUrl) {
            $category = $entity->getCategory();
            $catSlug  = (!empty($category))
                ? $category->getAlias()
                :
                $this->translator->trans('mautic.core.url.uncategorized');
        }

        $parent = $entity->getTranslationParent();
        $slugs  = [];
        if ($parent) {
            // multiple languages so tack on the language
            $slugs[] = $entity->getLanguage();
        }

        if (!empty($catSlug)) {
            // Insert category slug
            $slugs[] = $catSlug;
            $slugs[] = $pageSlug;
        } else {
            // Insert just the page slug
            $slugs[] = $pageSlug;
        }

        return implode('/', $slugs);
    }

    /**
     * @return array|mixed
     */
    protected function generateClickThrough(Hit $hit)
    {
        $query = $hit->getQuery();

        // Check for any clickthrough info
        $clickthrough = [];
        if (!empty($query['ct'])) {
            $clickthrough = $query['ct'];
            if (!is_array($clickthrough)) {
                $clickthrough = $this->decodeArrayFromUrl($clickthrough);
            }
        }

        return $clickthrough;
    }

    /**
     * @param string|int $code
     * @param array      $query
     *
     * @throws \Exception
     */
    public function hitPage(Redirect|Page|null $page, Request $request, $code = '200', Lead $lead = null, $query = []): void
    {
        // Don't skew results with user hits
        if (!$this->security->isAnonymous()) {
            return;
        }

        // Process the query
        if (empty($query) || !is_array($query)) {
            $query = $this->getHitQuery($request, $page);
        }

        // Get lead if required
        if (null == $lead) {
            $lead = $this->contactRequestHelper->getContactFromQuery($query);

            // company
            [$company, $leadAdded, $companyEntity] = IdentifyCompanyHelper::identifyLeadsCompany($query, $lead, $this->companyModel);
            $companyChangeLog                      = null;
            if ($leadAdded) {
                $companyChangeLog = $lead->addCompanyChangeLogEntry('form', 'Identify Company', 'Lead added to the company, '.$company['companyname'], $company['id']);
            } elseif ($companyEntity instanceof Company) {
                $this->companyModel->setFieldValues($companyEntity, $query);
                $this->companyModel->saveEntity($companyEntity);
            }

            if (!empty($company) and $companyEntity instanceof Company) {
                // Save after the lead in for new leads created through the API and maybe other places
                $this->companyModel->addLeadToCompany($companyEntity, $lead);
                $this->leadModel->setPrimaryCompany($companyEntity->getId(), $lead->getId());
            }

            if (null !== $companyChangeLog) {
                $this->companyModel->getCompanyLeadRepository()->detachEntity($companyChangeLog);
            }
        }

        if (!$lead || !$lead->getId()) {
            // Lead came from a non-trackable IP so ignore
            return;
        }

        $hit = new Hit();
        $hit->setDateHit(new \DateTime());
        $hit->setIpAddress($this->ipLookupHelper->getIpAddress());

        // Set info from request
        $hit->setQuery($query);
        $hit->setCode($code);

        $trackedDevice = $this->deviceTracker->createDeviceFromUserAgent($lead, $request->server->get('HTTP_USER_AGENT'));

        $hit->setTrackingId($trackedDevice->getTrackingId());
        $hit->setDeviceStat($trackedDevice);

        // Wrap in a try/catch to prevent deadlock errors on busy servers
        try {
            $this->em->persist($hit);
            $this->em->flush();
        } catch (\Exception $exception) {
            if (MAUTIC_ENV === 'dev') {
                throw $exception;
            } else {
                $this->logger->error(
                    $exception->getMessage(),
                    ['exception' => $exception]
                );
            }

            return;
        }

        // save hit to the cookie to use to update the exit time
        if ($hit) {
            $this->cookieHelper->setCookie(
                name: 'mautic_referer_id',
                value: $hit->getId() ?: null,
                sameSite: Cookie::SAMESITE_NONE
            );
        }

        $message = new PageHitNotification(
            $hit->getId(),
            $request,
            $this->deviceTracker->wasDeviceChanged(),
            $page instanceof Redirect,
            $page?->getId(),
            $lead->getId()
        );

        try {
            $this->messageBus->dispatch($message);
        } catch (\Exception $exception) {
            $this->logger->error('Failed to dispatch a message to messenger. '.$exception->getMessage());
            // Fallback measure
            $this->processPageHit($hit, $page, $request, $lead, $this->deviceTracker->wasDeviceChanged());
        }
    }

    /**
     * Process page hit.
     *
     * @throws \Exception
     */
    public function processPageHit(
        Hit $hit,
        Redirect|Page|null $page,
        Request $request,
        Lead $lead,
        bool $trackingNewlyGenerated,
        bool $activeRequest = true,
        \DateTimeInterface $hitDate = null
    ): void {
        // Store Page/Redirect association
        if ($page) {
            if ($page instanceof Page) {
                $hit->setPage($page);
            } else {
                $hit->setRedirect($page);
            }
        }

        // Check for any clickthrough info
        $clickthrough = $this->generateClickThrough($hit);
        if (!empty($clickthrough)) {
            if (!empty($clickthrough['channel'])) {
                if (1 === count($clickthrough['channel'])) {
                    $channelId = reset($clickthrough['channel']);
                    $channel   = key($clickthrough['channel']);
                } else {
                    $channel   = $clickthrough['channel'][0];
                    $channelId = (int) $clickthrough['channel'][1];
                }
                $hit->setSource($channel);
                $hit->setSourceId($channelId);
            } elseif (!empty($clickthrough['source'])) {
                $hit->setSource($clickthrough['source'][0]);
                $hit->setSourceId($clickthrough['source'][1]);
            }

            if (!empty($clickthrough['email'])) {
                $emailRepo = $this->em->getRepository(\Mautic\EmailBundle\Entity\Email::class);
                if ($emailEntity = $emailRepo->getEntity($clickthrough['email'])) {
                    $hit->setEmail($emailEntity);
                }
            }
        }

        $query = $hit->getQuery() ?: [];

        if (isset($query['timezone_offset']) && !$lead->getTimezone()) {
            // timezone_offset holds timezone offset in minutes. Multiply by 60 to get seconds.
            // Multiply by -1 because Firgerprint2 seems to have it the other way around.
            $timezone = (-1 * $query['timezone_offset'] * 60);
            $lead->setTimezone($this->dateTimeHelper->guessTimezoneFromOffset($timezone));
        }

        $query = $this->cleanQuery($query);

        if (isset($query['page_referrer'])) {
            $hit->setReferer($query['page_referrer']);
        }
        if (isset($query['page_language'])) {
            $hit->setPageLanguage($query['page_language']);
        }

        if ($pageTitle = $query['page_title'] ?? ($page instanceof Page ? $page->getTitle() : false)) {
            // Transliterate page titles.
            if ($this->coreParametersHelper->get('transliterate_page_title')) {
                $pageTitle = InputHelper::transliterate($pageTitle);
            }

            $query['page_title'] = $pageTitle;
            $hit->setUrlTitle($pageTitle);
        }

        $hit->setQuery($query);
        $hit->setUrl($query['page_url'] ?? $request->getRequestUri());

        // Add entry to contact log table
        $this->setLeadManipulator($page, $hit, $lead);

        // Store tracking ID
        $hit->setLead($lead);

        if (!$activeRequest) {
            // Queue is consuming this hit outside of the lead's active request so this must be set in order for listeners to know who the request belongs to
            $this->contactTracker->setSystemContact($lead);
        }
        $trackingId = $hit->getTrackingId();
        if (!$trackingNewlyGenerated) {
            $lastHit = $request->cookies->get('mautic_referer_id');
            if (!empty($lastHit)) {
                // this is not a new session so update the last hit if applicable with the date/time the user left
                $this->getHitRepository()->updateHitDateLeft($lastHit);
            }
        }

        // Check if this is a unique page hit
        $isUnique = $this->getHitRepository()->isUniquePageHit($page, $trackingId, $lead);

        if ($page instanceof Page) {
            $hit->setPageLanguage($page->getLanguage());

            $isVariant = ($isUnique) ? $page->getVariantStartDate() : false;

            try {
                $this->getRepository()->upHitCount($page->getId(), 1, $isUnique, !empty($isVariant));
            } catch (\Exception $exception) {
                $this->logger->error(
                    $exception->getMessage(),
                    ['exception' => $exception]
                );
            }
        } elseif ($page instanceof Redirect) {
            try {
                $this->pageRedirectModel->getRepository()->upHitCount($page->getId(), 1, $isUnique);

                // If this is a trackable, up the trackable counts as well
                if ($hit->getSource() && $hit->getSourceId()) {
                    $this->pageTrackableModel->getRepository()->upHitCount(
                        $page->getId(),
                        $hit->getSource(),
                        $hit->getSourceId(),
                        1,
                        $isUnique
                    );
                }
            } catch (\Exception $exception) {
                if (MAUTIC_ENV === 'dev') {
                    throw $exception;
                } else {
                    $this->logger->error(
                        $exception->getMessage(),
                        ['exception' => $exception]
                    );
                }
            }
        }

        // glean info from the IP address
        $ipAddress = $hit->getIpAddress();
        if ($details = $ipAddress->getIpDetails()) {
            $hit->setCountry($details['country']);
            $hit->setRegion($details['region']);
            $hit->setCity($details['city']);
            $hit->setIsp($details['isp']);
            $hit->setOrganization($details['organization']);
        }

        if (!$hit->getReferer()) {
            $hit->setReferer($request->server->get('HTTP_REFERER'));
        }

        $hit->setUserAgent($request->server->get('HTTP_USER_AGENT'));
        $hit->setRemoteHost($request->server->get('REMOTE_HOST'));

        $this->setUtmTags($hit, $lead);

        // get a list of the languages the user prefers
        $browserLanguages = $request->server->get('HTTP_ACCEPT_LANGUAGE');
        if (!empty($browserLanguages)) {
            $languages = explode(',', $browserLanguages);
            foreach ($languages as $k => $l) {
                if (($pos = strpos(';q=', $l)) !== false) {
                    // remove weights
                    $languages[$k] = substr($l, 0, $pos);
                }
            }
            $hit->setBrowserLanguages($languages);
        }

        // Wrap in a try/catch to prevent deadlock errors on busy servers
        try {
            $this->em->persist($hit);
            $this->em->flush();
        } catch (\Exception $exception) {
            if (MAUTIC_ENV === 'dev') {
                throw $exception;
            } else {
                $this->logger->error(
                    $exception->getMessage(),
                    ['exception' => $exception]
                );
            }
        }

        if ($this->dispatcher->hasListeners(PageEvents::PAGE_ON_HIT)) {
            $event = new PageHitEvent($hit, $request, $hit->getCode(), $clickthrough, $isUnique);
            $this->dispatcher->dispatch($event, PageEvents::PAGE_ON_HIT);
        }

        if (null !== $hitDate) {
            if (null === $lead->getLastActive() || $lead->getLastActive() < $hitDate) {
                try {
                    $this->leadModel->getRepository()->updateLastActive($lead->getId(), $hitDate);
                } catch (\Exception $e) {
                    $data = [
                        'unique'             => ($isUnique ? 'true' : 'false'),
                        'lead'               => $lead->getId(),
                        'page'               => $page->getId(),
                        'hit'                => $hit->getId(),
                        'lastActiveOriginal' => $lead->getLastActive(),
                        'newLastActive'      => $hitDate,
                    ];

                    $this->logger->error(
                        'Failed to update event time due to '.$e->getMessage(),
                        ['context' => $data, 'exception' => (array) $e]
                    );
                }
            }
        }
    }

    /**
     * @param Redirect|Page|null $page
     */
    public function getHitQuery(Request $request, $page = null): array
    {
        $get  = $request->query->all();
        $post = $request->request->all();

        $query = \array_merge($get, $post);

        // Set generated page url
        $query['page_url'] = $this->getPageUrl($request, $page);

        // get all params from the url (actual url or passed in as page_url)
        if (!empty($query['page_url'])) {
            $queryUrl = $this->getQueryFromUrl($query['page_url']);
            $query    = \array_merge($queryUrl, $query);
        }

        // Process clickthrough if applicable
        if (!empty($query['ct'])) {
            $query['ct'] = $this->decodeArrayFromUrl($query['ct']);
        }

        return $query;
    }

    /**
     * Get array of page builder tokens from bundles subscribed PageEvents::PAGE_ON_BUILD.
     *
     * @param array|string $requestedComponents all | tokens | abTestWinnerCriteria
     *
     * @return array
     */
    public function getBuilderComponents(Page $page = null, $requestedComponents = 'all', string $tokenFilter = '')
    {
        $event = new PageBuilderEvent($this->translator, $page, $requestedComponents, $tokenFilter);
        $this->dispatcher->dispatch($event, PageEvents::PAGE_ON_BUILD);

        return $this->getCommonBuilderComponents($requestedComponents, $event);
    }

    /**
     * Get number of page bounces.
     *
     * @return mixed[]
     */
    public function getBounces(Page $page, \DateTime $fromDate = null): array
    {
        return $this->getHitRepository()->getBounces($page->getId(), $fromDate);
    }

    /**
     * Joins the page table and limits created_by to currently logged in user.
     */
    public function limitQueryToCreator(QueryBuilder &$q): void
    {
        $q->join('t', MAUTIC_TABLE_PREFIX.'pages', 'p', 'p.id = t.page_id')
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

        if (!$flag || 'total_and_unique' == $flag) {
            $q = $query->prepareTimeDataQuery('page_hits', 'date_hit', $filter);

            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }

            $data = $query->loadAndBuildTimeData($q);
            $chart->setDataset($this->translator->trans('mautic.page.show.total.visits'), $data);
        }

        if ('unique' == $flag || 'total_and_unique' == $flag) {
            $q = $query->prepareTimeDataQuery(
                'page_hits',
                'date_hit',
                $filter,
                'distinct(t.lead_id)',
                true,
                false
            );

            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }

            $data = $query->loadAndBuildTimeData($q);
            $chart->setDataset($this->translator->trans('mautic.page.show.unique.visits'), $data);
        }

        return $chart->render();
    }

    /**
     * Get data for pie chart showing new vs returning leads.
     * Returning leads are even leads who visits 2 different page once.
     *
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param array     $filters
     * @param bool      $canViewOthers
     */
    public function getNewVsReturningPieChartData($dateFrom, $dateTo, $filters = [], $canViewOthers = true): array
    {
        $chart              = new PieChart();
        $query              = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $allQ               = $query->getCountQuery('page_hits', 'id', 'date_hit', $filters);
        $filters['lead_id'] = [
            'expression' => 'isNull',
        ];
        $returnQ            = $query->getCountQuery('page_hits', 'id', 'date_hit', $filters);

        if (!$canViewOthers) {
            $this->limitQueryToCreator($allQ);
            $this->limitQueryToCreator($returnQ);
        }

        $all       = $query->fetchCount($allQ);
        $returning = $query->fetchCount($returnQ);
        $unique    = $all - $returning;
        $chart->setDataset($this->translator->trans('mautic.page.unique'), $unique);
        $chart->setDataset($this->translator->trans('mautic.page.graph.pie.new.vs.returning.returning'), $returning);

        return $chart->render();
    }

    /**
     * Get pie chart data of dwell times.
     *
     * @param array $filters
     * @param bool  $canViewOthers
     */
    public function getDwellTimesPieChartData(\DateTime $dateFrom, \DateTime $dateTo, $filters = [], $canViewOthers = true): array
    {
        $timesOnSite = $this->getHitRepository()->getDwellTimeLabels();
        $chart       = new PieChart();
        $query       = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);

        foreach ($timesOnSite as $time) {
            $q = $query->getCountDateDiffQuery('page_hits', 'date_hit', 'date_left', $time['from'], $time['till'], $filters);

            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }

            $data = $query->fetchCountDateDiff($q);
            $chart->setDataset($time['label'], $data);
        }

        return $chart->render();
    }

    /**
     * Get bar chart data of hits.
     */
    public function getDeviceGranularityData(\DateTime $dateFrom, \DateTime $dateTo, $filters = [], $canViewOthers = true): array
    {
        $q = $this->em->getConnection()->createQueryBuilder();

        $q->select('count(h.id) as count, ds.device as device')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'h')
            ->join('h', MAUTIC_TABLE_PREFIX.'lead_devices', 'ds', 'ds.id=h.device_id')
            ->orderBy('device', 'DESC')
            ->andWhere($q->expr()->gte('h.date_hit', ':date_from'))
            ->setParameter('date_from', $dateFrom->format('Y-m-d'))
            ->andWhere($q->expr()->lte('h.date_hit', ':date_to'))
            ->setParameter('date_to', $dateTo->format('Y-m-d 23:59:59'));
        $q->groupBy('ds.device');

        $results = $q->executeQuery()->fetchAllAssociative();

        $chart   = new PieChart();

        if (empty($results)) {
            $results[] = [
                'device' => $this->translator->trans('mautic.report.report.noresults'),
                'count'  => 0,
            ];
        }

        foreach ($results as $result) {
            $label = empty($result['device']) ? $this->translator->trans('mautic.core.no.info') : $result['device'];

            $chart->setDataset($label, $result['count']);
        }

        return $chart->render();
    }

    /**
     * Get a list of popular (by hits) pages.
     *
     * @param int   $limit
     * @param array $filters
     * @param bool  $canViewOthers
     *
     * @return array
     */
    public function getPopularPages($limit = 10, \DateTime $dateFrom = null, \DateTime $dateTo = null, $filters = [], $canViewOthers = true)
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(DISTINCT t.id) AS hits, p.id, p.title, p.alias')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 't')
            ->join('t', MAUTIC_TABLE_PREFIX.'pages', 'p', 'p.id = t.page_id')
            ->orderBy('hits', 'DESC')
            ->groupBy('p.id')
            ->setMaxResults($limit);

        if (!$canViewOthers) {
            $q->andWhere('p.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_hit');

        return $q->execute()->fetchAllAssociative();
    }

    /**
     * Get a list of pages created in a date range.
     *
     * @param int   $limit
     * @param array $filters
     * @param bool  $canViewOthers
     *
     * @return array
     */
    public function getPageList($limit = 10, \DateTime $dateFrom = null, \DateTime $dateTo = null, $filters = [], $canViewOthers = true)
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('t.id, t.title AS name, t.date_added, t.date_modified')
            ->from(MAUTIC_TABLE_PREFIX.'pages', 't')
            ->setMaxResults($limit);

        if (!$canViewOthers) {
            $q->andWhere('t.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        return $q->execute()->fetchAllAssociative();
    }

    /**
     * Get all params (e.g. UTM tags) from a url.
     */
    private function getQueryFromUrl(string $pageUrl): array
    {
        $query             = [];
        $urlQuery          = parse_url($pageUrl, PHP_URL_QUERY);

        if (is_string($urlQuery)) {
            parse_str($urlQuery, $urlQueryArray);

            foreach ($urlQueryArray as $key => $value) {
                if (is_string($value)) {
                    $key         = strtolower($key);
                    $query[$key] = urldecode($value);
                }
            }
        }

        return $query;
    }

    /**
     * Set UTM Tags based on the query of a page hit.
     */
    private function setUtmTags(Hit $hit, Lead $lead): void
    {
        // Add UTM tags entry if a UTM tag exist
        $queryHasUtmTags = false;
        $query           = $hit->getQuery();
        foreach ($query as $key => $value) {
            if (str_contains($key, 'utm_')) {
                $queryHasUtmTags = true;
                break;
            }
        }

        if ($queryHasUtmTags && $lead) {
            $utmTags = new UtmTag();
            $utmTags->setDateAdded($hit->getDateHit());
            $utmTags->setUrl($hit->getUrl());
            $utmTags->setReferer($hit->getReferer());
            $utmTags->setQuery($hit->getQuery());
            $utmTags->setUserAgent($hit->getUserAgent());
            $utmTags->setRemoteHost($hit->getRemoteHost());
            $utmTags->setLead($lead);

            if (array_key_exists('utm_campaign', $query)) {
                $utmTags->setUtmCampaign($query['utm_campaign']);
            }
            if (array_key_exists('utm_term', $query)) {
                $utmTags->setUtmTerm($query['utm_term']);
            }
            if (array_key_exists('utm_content', $query)) {
                $utmTags->setUtmContent($query['utm_content']);
            }
            if (array_key_exists('utm_medium', $query)) {
                $utmTags->setUtmMedium($query['utm_medium']);
            }
            if (array_key_exists('utm_source', $query)) {
                $utmTags->setUtmSource($query['utm_source']);
            }

            $repo = $this->em->getRepository(\Mautic\LeadBundle\Entity\UtmTag::class);
            $repo->saveEntity($utmTags);

            $this->leadModel->setUtmTags($lead, $utmTags);
        }
    }

    private function setLeadManipulator($page, Hit $hit, Lead $lead): void
    {
        // Only save the lead and dispatch events if needed
        $source   = 'hit';
        $sourceId = $hit->getId();
        if ($page) {
            $source   = $page instanceof Page ? 'page' : 'redirect';
            $sourceId = $page->getId();
        }

        $lead->setManipulator(
            new LeadManipulator(
                'page',
                $source,
                $sourceId,
                $hit->getUrl()
            )
        );

        $this->leadModel->saveEntity($lead);
    }

    /**
     * @return mixed|string
     */
    private function getPageUrl(Request $request, $page)
    {
        // Default to page_url set in the query from tracking pixel and/or contactfield token
        if ($pageURL = $request->get('page_url')) {
            return $pageURL;
        }

        if ($page instanceof Redirect) {
            // use the configured redirect URL
            return $page->getUrl();
        }

        // Use the current URL
        $isPageEvent = false;
        if (str_contains($request->server->get('REQUEST_URI'), $this->router->generate('mautic_page_tracker'))) {
            // Tracking pixel is used
            if ($request->server->get('QUERY_STRING')) {
                parse_str($request->server->get('QUERY_STRING'), $query);
                $isPageEvent = true;
            }
        } elseif (str_contains($request->server->get('REQUEST_URI'), $this->router->generate('mautic_page_tracker_cors'))) {
            $query       = $request->request->all();
            $isPageEvent = true;
        }

        if ($isPageEvent) {
            $pageURL = $request->server->get('HTTP_REFERER');

            // if additional data were sent with the tracking pixel
            if (isset($query)) {
                // URL attr 'd' is encoded so let's decode it first.
                $decoded = false;
                if (isset($query['d'])) {
                    // parse_str auto urldecodes
                    $query   = $this->decodeArrayFromUrl($query['d'], false);
                    $decoded = true;
                }

                if (is_array($query) && !empty($query)) {
                    if (isset($query['page_url'])) {
                        $pageURL = $query['page_url'];
                        if (!$decoded) {
                            $pageURL = urldecode($pageURL);
                        }
                    }

                    if (isset($query['page_referrer'])) {
                        if (!$decoded) {
                            $query['page_referrer'] = urldecode($query['page_referrer']);
                        }
                    }

                    if (isset($query['page_language'])) {
                        if (!$decoded) {
                            $query['page_language'] = urldecode($query['page_language']);
                        }
                    }

                    if (isset($query['page_title'])) {
                        if (!$decoded) {
                            $query['page_title'] = urldecode($query['page_title']);
                        }
                    }

                    if (isset($query['tags'])) {
                        if (!$decoded) {
                            $query['tags'] = urldecode($query['tags']);
                        }
                    }
                }
            }

            return $pageURL;
        }

        $pageURL = 'http';
        if ('on' == $request->server->get('HTTPS')) {
            $pageURL .= 's';
        }
        $pageURL .= '://';

        if (!in_array((int) $request->server->get('SERVER_PORT', 80), [80, 8080, 443])) {
            return $pageURL.$request->server->get('SERVER_NAME').':'.$request->server->get('SERVER_PORT').
                $request->server->get('REQUEST_URI');
        }

        return $pageURL.$request->server->get('SERVER_NAME').$request->server->get('REQUEST_URI');
    }

    /*
     * Cleans query params saving url values.
     *
     * @param $query array
     *
     * @return array
     */
    private function cleanQuery(array $query): array
    {
        foreach ($query as $key => $value) {
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                $query[$key] = InputHelper::url($value);
            } else {
                $query[$key] = InputHelper::clean($value);
            }
        }

        return $query;
    }
}
