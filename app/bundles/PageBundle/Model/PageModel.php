<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Model;

use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Entity\UtmTag;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\Event\PageEvent;
use Mautic\PageBundle\Event\PageHitEvent;
use Mautic\PageBundle\PageEvents;
use Mautic\CoreBundle\Helper\Chart\BarChart;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class PageModel
 */
class PageModel extends FormModel
{
    /**
     * @var bool
     */
    protected $catInUrl;

    /**
     * @var CookieHelper
     */
    protected $cookieHelper;

    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var FieldModel
     */
    protected $leadFieldModel;

    /**
     * @var RedirectModel
     */
    protected $pageRedirectModel;

    /**
     * @var TrackableModel
     */
    protected $pageTrackableModel;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * PageModel constructor.
     *
     * @param CookieHelper $cookieHelper
     * @param IpLookupHelper $ipLookupHelper
     * @param LeadModel $leadModel
     * @param FieldModel $leadFieldModel
     * @param RedirectModel $pageRedirectModel
     * @param TrackableModel $pageTrackableModel
     */
    public function __construct(
        CookieHelper $cookieHelper,
        IpLookupHelper $ipLookupHelper,
        LeadModel $leadModel,
        FieldModel $leadFieldModel,
        RedirectModel $pageRedirectModel,
        TrackableModel $pageTrackableModel
    )
    {
        $this->cookieHelper = $cookieHelper;
        $this->ipLookupHelper = $ipLookupHelper;
        $this->leadModel = $leadModel;
        $this->leadFieldModel = $leadFieldModel;
        $this->pageRedirectModel = $pageRedirectModel;
        $this->pageTrackableModel = $pageTrackableModel;
    }

    /**
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param $catInUrl
     */
    public function setCatInUrl($catInUrl)
    {
        $this->catInUrl = $catInUrl;
    }

    /**
     * @return \Mautic\PageBundle\Entity\PageRepository
     */
    public function getRepository ()
    {
        $repo = $this->em->getRepository('MauticPageBundle:Page');
        $repo->setCurrentUser($this->user);
        return $repo;
    }

    /**
     * @return \Mautic\PageBundle\Entity\HitRepository
     */
    public function getHitRepository()
    {
        return $this->em->getRepository('MauticPageBundle:Hit');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase ()
    {
        return 'page:pages';
    }

    /**
     * {@inheritdoc}
     */
    public function getNameGetter ()
    {
        return 'getTitle';
    }

    /**
     * {@inheritdoc}
     *
     * @param Page $entity
     * @param bool $unlock
     */
    public function saveEntity ($entity, $unlock = true)
    {
        if (empty($this->inConversion)) {
            $alias = $entity->getAlias();
            if (empty($alias)) {
                $alias = $entity->getTitle();
            }
            $alias = $this->cleanAlias($alias, '', false, '-');

            //make sure alias is not already taken
            $repo      = $this->getRepository();
            $testAlias = $alias;
            $count     = $repo->checkUniqueAlias($testAlias, $entity);
            $aliasTag  = $count;

            while ($count) {
                $testAlias = $alias . $aliasTag;
                $count     = $repo->checkUniqueAlias($testAlias, $entity);
                $aliasTag++;
            }
            if ($testAlias != $alias) {
                $alias = $testAlias;
            }
            $entity->setAlias($alias);
        }

        $now = new DateTimeHelper();

        //set the author for new pages
        $isNew = $entity->isNew();
        if (!$isNew) {
            //increase the revision
            $revision = $entity->getRevision();
            $revision++;
            $entity->setRevision($revision);
        }

        // Reset the variant hit and start date if there are any changes and if this is an A/B test
        // Do it here in addition to the blanket resetVariants call so that it's available to the event listeners
        $changes = $entity->getChanges();
        $parent  = $entity->getVariantParent();

        if ($parent !== null && !empty($changes) && empty($this->inConversion)) {
            $entity->setVariantHits(0);
            $entity->setVariantStartDate($now->getDateTime());
        }

        parent::saveEntity($entity, $unlock);

        // If parent, add this entity as a child of the parent so that it populates the list in the tab (due to Doctrine hanging on to entities in memory)
        if ($parent) {
            $parent->addVariantChild($entity);
        }
        if ($translationParent = $entity->getTranslationParent()) {
            $translationParent->addTranslationChild($entity);
        }

        // Reset associated variants if applicable due to changes
        if ($entity->isVariant() && !empty($changes) && empty($this->inConversion)) {
            $dateString = $now->toUtcString();
            $parentId = (!empty($parent)) ? $parent->getId() : $entity->getId();
            $this->getRepository()->resetVariants($parentId, $dateString);

            //if the parent was changed, then that parent/children must also be reset
            if (isset($changes['variantParent'])) {
                $this->getRepository()->resetVariants($changes['variantParent'][0], $dateString);
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm ($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Page) {
            throw new MethodNotAllowedHttpException(array('Page'));
        }

        if (!isset($options['formName'])) {
            $options['formName'] = 'page';
        }

        $params = (!empty($action)) ? array('action' => $action) : array();

        return $formFactory->create($options['formName'], $entity, $params);
    }

    /**
     * {@inheritdoc}
     *
     * @return null|Page
     */
    public function getEntity ($id = null)
    {
        if ($id === null) {
            $entity = new Page();
            $entity->setSessionId('new_' . hash('sha1', uniqid(mt_rand())));
        } else {
            $entity = parent::getEntity($id);
            if ($entity !== null) {
                $entity->setSessionId($entity->getId());
            }
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent ($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Page) {
            throw new MethodNotAllowedHttpException(array('Page'));
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

            $this->dispatcher->dispatch($name, $event);

            return $event;
        }

        return null;
    }

    /**
     * Get list of entities for autopopulate fields
     *
     * @param string $type
     * @param string $filter
     * @param int    $limit
     *
     * @return array
     */
    public function getLookupResults ($type, $filter = '', $limit = 10)
    {
        $results = array();
        switch ($type) {
            case 'page':
                $viewOther = $this->security->isGranted('page:pages:viewother');
                $repo      = $this->getRepository();
                $repo->setCurrentUser($this->user);
                $results = $repo->getPageList($filter, $limit, 0, $viewOther);
                break;
        }

        return $results;
    }

    /**
     * Generate URL for a page
     *
     * @param Page  $entity
     * @param bool  $absolute
     * @param array $clickthrough
     *
     * @return string
     */
    public function generateUrl ($entity, $absolute = true, $clickthrough = array())
    {
        // If this is a variant, then get the parent's URL
        $parent = $entity->getVariantParent();
        if ($parent != null) {
            $entity = $parent;
        }

        $slug = $this->generateSlug($entity);

        return $this->buildUrl('mautic_page_public', array('slug' => $slug), $absolute, $clickthrough);
    }

    /**
     * Generates slug string
     *
     * @param $entity
     *
     * @return string
     */
    public function generateSlug ($entity)
    {
        $pageSlug =  $entity->getAlias();

        //should the url include the category
        if ($this->catInUrl) {
            $category = $entity->getCategory();
            $catSlug  = (!empty($category)) ? $category->getAlias() :
                $this->translator->trans('mautic.core.url.uncategorized');
        }

        $parent = $entity->getTranslationParent();
        $slugs  = array();
        if ($parent) {
            //multiple languages so tack on the language
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
     * @param        $page
     * @param        $request
     * @param string $code
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Exception
     */
    public function hitPage($page, $request, $code = '200')
    {
        //don't skew results with in-house hits
        if (!$this->security->isAnonymous()) {
            return;
        }

        $hit = new Hit();
        $hit->setDateHit(new \Datetime());

        $utmTags = new UtmTag();
        $utmTags->setDateAdded(new \Datetime());

        //check for existing IP
        $ipAddress = $this->ipLookupHelper->getIpAddress();
        $hit->setIpAddress($ipAddress);

        //check for any clickthrough info
        $clickthrough = $request->get('ct', array());
        if (!empty($clickthrough)) {
            $clickthrough = $this->decodeArrayFromUrl($clickthrough);

            if (!empty($clickthrough['lead'])) {
                $lead = $this->leadModel->getEntity($clickthrough['lead']);
                if ($lead !== null) {
                    $this->leadModel->setLeadCookie($clickthrough['lead']);
                    $leadClickthrough = true;

                    $this->leadModel->setCurrentLead($lead);
                }
            }

            if (!empty($clickthrough['source'])) {
                $hit->setSource($clickthrough['source'][0]);
                $hit->setSourceId($clickthrough['source'][1]);
            }

            if (!empty($clickthrough['email'])) {
                $hit->setEmail($this->em->getReference('MauticEmailBundle:Email', $clickthrough['email']));
            }
        }

        if (empty($leadClickthrough)) {
            $lead = $this->leadModel->getCurrentLead();
        }

        if ($page instanceof Redirect) {
            //use the configured redirect URL
            $pageURL = $page->getUrl();
        } else {
            //use current URL

            // Tracking pixel is used
            if (strpos($request->server->get('REQUEST_URI'), '/mtracking.gif') !== false) {
                $pageURL = $request->server->get('HTTP_REFERER');

                // if additional data were sent with the tracking pixel
                if ($request->server->get('QUERY_STRING')) {
                    parse_str($request->server->get('QUERY_STRING'), $query);

                    // URL attr 'd' is encoded so let's decode it first.
                    $decoded = false;
                    if (isset($query['d'])) {
                        // parse_str auto urldecodes
                        $query   = unserialize(base64_decode($query['d']));
                        $decoded = true;
                    }

                    if (!empty($query)) {
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
                            $hit->setReferer($query['page_referrer']);
                            $utmTags->setReferer($query['page_referrer']);
                        }

                        if (isset($query['page_language'])) {
                            if (!$decoded) {
                                $query['page_language'] = urldecode($query['page_language']);
                            }
                            $hit->setPageLanguage($query['page_language']);
                        }

                        if (isset($query['page_title'])) {
                            if (!$decoded) {
                                $query['page_title'] = urldecode($query['page_title']);
                            }
                            $hit->setUrlTitle($query['page_title']);
                        }

                        // Update lead fields if some data were sent in the URL query
                        $availableLeadFields = $this->leadFieldModel->getFieldList(
                            false,
                            false,
                            array(
                                'isPublished'         => true,
                                'isPubliclyUpdatable' => true
                            )
                        );

                        $uniqueLeadFields    = $this->leadFieldModel->getUniqueIdentiferFields();
                        $uniqueLeadFieldData = array();
                        $inQuery             = array_intersect_key($query, $availableLeadFields);
                        foreach ($inQuery as $k => $v) {
                            if (empty($query[$k])) {
                                unset($inQuery[$k]);
                            }

                            if (array_key_exists($k, $uniqueLeadFields)) {
                                $uniqueLeadFieldData[$k] = $v;
                            }
                        }

                        $persistLead = false;
                        if (count($inQuery)) {
                            if (count($uniqueLeadFieldData)) {
                                $existingLeads = $this->em->getRepository('MauticLeadBundle:Lead')->getLeadsByUniqueFields(
                                    $uniqueLeadFieldData,
                                    $lead->getId()
                                );
                                if (!empty($existingLeads)) {
                                    $lead = $this->leadModel->mergeLeads($lead, $existingLeads[0]);
                                }
                                $leadIpAddresses = $lead->getIpAddresses();

                                if (!$leadIpAddresses->contains($ipAddress)) {
                                    $lead->addIpAddress($ipAddress);
                                }

                                $this->leadModel->setCurrentLead($lead);
                            }

                            $this->leadModel->setFieldValues($lead, $inQuery);

                            $persistLead = true;
                        }

                        if (isset($query['tags'])) {
                            if (!$decoded) {
                                $query['tags'] = urldecode($query['tags']);
                            }

                            $leadTags = $lead->getTags();

                            $tags = explode(',', $query['tags']);
                            array_walk($tags, create_function('&$val', '$val = trim($val); \Mautic\CoreBundle\Helper\InputHelper::clean($val);'));

                            // See which tags already exist
                            $foundTags = $this->leadModel->getTagRepository()->getTagsByName($tags);
                            foreach ($tags as $tag) {
                                if (strpos($tag, '-') === 0) {
                                    // Tag to be removed
                                    $tag = substr($tag, 1);

                                    if (array_key_exists($tag, $foundTags) && $leadTags->contains($foundTags[$tag])) {
                                        $lead->removeTag($foundTags[$tag]);
                                        $persistLead = true;
                                    }
                                } else {
                                    // Tag to be added
                                    if (!array_key_exists($tag, $foundTags)) {
                                        // New tag
                                        $newTag = new Tag();
                                        $newTag->setTag($tag);
                                        $lead->addTag($newTag);
                                        $persistLead = true;
                                    } elseif (!$leadTags->contains($foundTags[$tag])) {
                                        $lead->addTag($foundTags[$tag]);
                                        $persistLead = true;
                                    }
                                }
                            }
                        }

                        if ($persistLead) {
                            $this->leadModel->saveEntity($lead);
                        }
                    }
                }
            } else {
                $pageURL = 'http';
                if ($request->server->get('HTTPS') == 'on') {
                    $pageURL .= 's';
                }
                $pageURL .= '://';
                if ($request->server->get('SERVER_PORT') != '80') {
                    $pageURL .= $request->server->get('SERVER_NAME') . ':' . $request->server->get('SERVER_PORT') .
                        $request->server->get('REQUEST_URI');
                } else {
                    $pageURL .= $request->server->get('SERVER_NAME') . $request->server->get('REQUEST_URI');
                }
            }
        }

        $hit->setUrl($pageURL);
        $utmTags->setUrl($pageURL);

        // Store query array
        $query = $request->query->all();
        unset($query['d']);
        $hit->setQuery($query);
        $utmTags->setQuery($query);

        list($trackingId, $trackingNewlyGenerated) = $this->leadModel->getTrackingCookie();

        $hit->setTrackingId($trackingId);
        $hit->setLead($lead);
        $utmTags->setLead($lead);

        $isUnique = $trackingNewlyGenerated;
        if (!$trackingNewlyGenerated) {
            $lastHit = $request->cookies->get('mautic_referer_id');
            if (!empty($lastHit)) {
                //this is not a new session so update the last hit if applicable with the date/time the user left
                $this->getHitRepository()->updateHitDateLeft($lastHit);
            }

            // Check if this is a unique page hit
            $isUnique = $this->getHitRepository()->isUniquePageHit($page, $trackingId);
        }

        if (!empty($page)) {
            if ($page instanceof Page) {
                $hit->setPage($page);
                $hit->setPageLanguage($page->getLanguage());

                $isVariant = ($isUnique) ? $page->getVariantStartDate() : false;

                try {
                    $this->getRepository()->upHitCount($page->getId(), 1, $isUnique, !empty($isVariant));
                } catch (\Exception $exception) {
                    $this->logger->addError(
                        $exception->getMessage(),
                        array('exception' => $exception)
                    );
                }
            } elseif ($page instanceof Redirect) {
                $hit->setRedirect($page);

                try {

                    $this->pageRedirectModel->getRepository()->upHitCount($page->getId(), 1, $isUnique);

                    // If this is a trackable, up the trackable counts as well
                    if (!empty($clickthrough['channel'])) {
                        $channelId      = reset($clickthrough['channel']);
                        $channel        = key($clickthrough['channel']);

                        $this->pageTrackableModel->getRepository()->upHitCount($page->getId(), $channel, $channelId, 1, $isUnique);
                    }
                } catch (\Exception $exception) {
                    if (MAUTIC_ENV === 'dev') {

                        throw $exception;
                    } else {
                        $this->logger->addError(
                            $exception->getMessage(),
                            array('exception' => $exception)
                        );
                    }
                }
            }
        }

        //glean info from the IP address
        if ($details = $ipAddress->getIpDetails()) {
            $hit->setCountry($details['country']);
            $hit->setRegion($details['region']);
            $hit->setCity($details['city']);
            $hit->setIsp($details['isp']);
            $hit->setOrganization($details['organization']);
        }

        $hit->setCode($code);
        if (!$hit->getReferer()) {
            $hit->setReferer($request->server->get('HTTP_REFERER'));
        }

        if (!$utmTags->getReferer()) {
            $utmTags->setReferer($request->server->get('HTTP_REFERER'));
        }

        $hit->setUserAgent($request->server->get('HTTP_USER_AGENT'));
        $utmTags->setUserAgent($request->server->get('HTTP_USER_AGENT'));

        $hit->setRemoteHost($request->server->get('REMOTE_HOST'));
        $utmTags->setRemoteHost($request->server->get('REMOTE_HOST'));

        if (key_exists('utm_campaign',$query)){
            $utmTags->setUtmCampaign($query['utm_campaign']);
        }

        if (key_exists('utm_term',$query)){
            $utmTags->setUtmTerm($query['utm_term']);
        }
        if (key_exists('utm_content',$query)){
            $utmTags->setUtmConent($query['utm_content']);
        }
        if (key_exists('utm_medium',$query)){
            $utmTags->setUtmMedium($query['utm_medium']);
        }
        if (key_exists('utm_source',$query)){
            $utmTags->setUtmSource($query['utm_source']);
        }

        $repo = $this->em->getRepository('MauticLeadBundle:UtmTag');
        $repo->saveEntity($utmTags);

        $this->leadModel->setUtmTags($lead, $utmTags);

        //get a list of the languages the user prefers
        $browserLanguages = $request->server->get('HTTP_ACCEPT_LANGUAGE');
        if (!empty($browserLanguages)) {
            $languages = explode(',', $browserLanguages);
            foreach ($languages as $k => $l) {
                if ($pos = strpos(';q=', $l) !== false) {
                    //remove weights
                    $languages[$k] = substr($l, 0, $pos);
                }
            }
            $hit->setBrowserLanguages($languages);
        }

        // Wrap in a try/catch to prevent deadlock errors on busy servers
        try {
            $this->em->persist($hit);
            $this->em->flush($hit);
        } catch (\Exception $exception) {
            if (MAUTIC_ENV === 'dev') {

                throw $exception;
            } else {
                $this->logger->addError(
                    $exception->getMessage(),
                    array('exception' => $exception)
                );
            }
        }

        if ($this->dispatcher->hasListeners(PageEvents::PAGE_ON_HIT)) {
            $event = new PageHitEvent($hit, $request, $code, $clickthrough, $isUnique);
            $this->dispatcher->dispatch(PageEvents::PAGE_ON_HIT, $event);
        }

        //save hit to the cookie to use to update the exit time
        $this->cookieHelper->setCookie('mautic_referer_id', $hit->getId());
    }

    /**
     * Get array of page builder tokens from bundles subscribed PageEvents::PAGE_ON_BUILD
     *
     * @param null|Page    $page
     * @param array|string $requestedComponents all | tokens | tokenSections | abTestWinnerCriteria
     * @param null|string  $tokenFilter
     *
     * @return array
     */
    public function getBuilderComponents (Page $page = null, $requestedComponents = 'all', $tokenFilter = null)
    {
        $singleComponent = (!is_array($requestedComponents) && $requestedComponents != 'all');
        $components      = array();
        $event           = new PageBuilderEvent($this->translator, $page, $requestedComponents, $tokenFilter);
        $this->dispatcher->dispatch(PageEvents::PAGE_ON_BUILD, $event);

        if (!is_array($requestedComponents)) {
            $requestedComponents = array($requestedComponents);
        }

        foreach ($requestedComponents as $requested) {
            switch ($requested) {
                case 'tokens':
                    $components[$requested] = $event->getTokens();
                    break;
                case 'visualTokens':
                    $components[$requested] = $event->getVisualTokens();
                    break;
                case 'tokenSections':
                    $components[$requested] = $event->getTokenSections();
                    break;
                case 'abTestWinnerCriteria':
                    $components[$requested] = $event->getAbTestWinnerCriteria();
                    break;
                case 'slotTypes':
                    $components[$requested] = $event->getSlotTypes();
                    break;
                default:
                    $components['tokens']               = $event->getTokens();
                    $components['tokenSections']        = $event->getTokenSections();
                    $components['abTestWinnerCriteria'] = $event->getAbTestWinnerCriteria();
                    $components['slotTypes']            = $event->getSlotTypes();
                    break;
            }
        }

        return ($singleComponent) ? $components[$requestedComponents[0]] : $components;
    }

    /**
     * Get number of page bounces
     *
     * @param Page $page
     *
     * @return int
     */
    public function getBounces (Page $page)
    {
        return $this->getHitRepository()->getBounces($page->getId());
    }

    /**
     * Get the variant parent/children
     *
     * @param Page $page
     *
     * @return array
     */
    public function getVariants (Page $page)
    {
        $parent = $page->getVariantParent();

        if (!empty($parent)) {
            $children = $parent->getVariantChildren();
        } else {
            $parent   = $page;
            $children = $page->getVariantChildren();
        }

        if (empty($children)) {
            $children = array();
        }

        return array($parent, $children);
    }

    /**
     * Get translation parent/children
     *
     * @param Page $page
     *
     * @return array
     */
    public function getTranslations (Page $page)
    {
        $parent = $page->getTranslationParent();

        if (!empty($parent)) {
            $children = $parent->getTranslationChildren();
        } else {
            $parent   = $page;
            $children = $page->getTranslationChildren();
        }

        if (empty($children)) {
            $children = false;
        }

        return array($parent, $children);
    }

    /**
     * Converts a variant to the main page and the main page a variant
     *
     * @param Page $page
     */
    public function convertVariant (Page $page)
    {
        //let saveEntities() know it does not need to set variant start dates
        $this->inConversion = true;

        list($parent, $children) = $this->getVariants($page);

        $save = array();

        //set this page as the parent for the original parent and children
        if ($parent) {
            if ($parent->getId() != $page->getId()) {
                $parent->setIsPublished(false);
                $page->addVariantChild($parent);
                $parent->setVariantParent($page);
            }

            $parent->setVariantStartDate(null);
            $parent->setVariantHits(0);

            foreach ($children as $child) {
                //capture child before it's removed from collection
                $save[] = $child;

                $parent->removeVariantChild($child);
            }
        }

        if (count($save)) {
            foreach ($save as $child) {
                if ($child->getId() != $page->getId()) {
                    $child->setIsPublished(false);
                    $page->addVariantChild($child);
                    $child->setVariantParent($page);
                } else {
                    $child->removeVariantParent();
                }

                $child->setVariantHits(0);
                $child->setVariantStartDate(null);
            }
        }

        $save[] = $parent;
        $save[] = $page;

        //save the entities
        $this->saveEntities($save, false);
    }


    /**
     * Delete an entity
     *
     * @param object $entity
     *
     * @return void
     */
    public function deleteEntity($entity)
    {
        $this->getRepository()->nullParents($entity->getId());

        return parent::deleteEntity($entity);
    }

    /**
     * Delete an array of entities
     *
     * @param array $ids
     *
     * @return array
     */
    public function deleteEntities($ids)
    {
        $this->getRepository()->nullParents($ids);

        return parent::deleteEntities($ids);
    }

    /**
     * Joins the page table and limits created_by to currently logged in user
     *
     * @param QueryBuilder $q
     */
    public function limitQueryToCreator(QueryBuilder &$q)
    {
        $q->join('t', MAUTIC_TABLE_PREFIX.'pages', 'p', 'p.id = t.page_id')
            ->andWhere('p.created_by = :userId')
            ->setParameter('userId', $this->user->getId());
    }

    /**
     * Get line chart data of hits
     *
     * @param char      $unit   {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param string    $dateFormat
     * @param array     $filter
     * @param boolean   $canViewOthers
     *
     * @return array
     */
    public function getHitsLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = array(), $canViewOthers = true)
    {
        $flag = null;

        if (isset($filter['flag'])) {
            $flag = $filter['flag'];
            unset($filter['flag']);
        }

        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);

        if (!$flag || $flag == 'total_and_unique') {
            $q = $query->prepareTimeDataQuery('page_hits', 'date_hit', $filter);

            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }

            $data = $query->loadAndBuildTimeData($q);
            $chart->setDataset($this->translator->trans('mautic.page.show.total.visits'), $data);
        }

        if ($flag == 'unique' || $flag == 'total_and_unique') {
            $q = $query->prepareTimeDataQuery('page_hits', 'date_hit', $filter);
            $q->groupBy('t.lead_id, t.date_hit');

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
     * @param boolean   $canViewOthers
     *
     * @return array
     */
    public function getNewVsReturningPieChartData($dateFrom, $dateTo, $filters = array(), $canViewOthers = true)
    {
        $chart     = new PieChart();
        $query     = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $allQ      = $query->getCountQuery('page_hits', 'id', 'date_hit', $filters);
        $uniqueQ   = $query->getCountQuery('page_hits', 'lead_id', 'date_hit', $filters, array('getUnique' => true, 'selectAlso' => array('t.page_id')));

        if (!$canViewOthers) {
            $this->limitQueryToCreator($allQ);
            $this->limitQueryToCreator($uniqueQ);
        }

        $all       = $query->fetchCount($allQ);
        $unique    = $query->fetchCount($uniqueQ);
        $returning = $all - $unique;
        $chart->setDataset($this->translator->trans('mautic.page.unique'), $unique);
        $chart->setDataset($this->translator->trans('mautic.page.graph.pie.new.vs.returning.returning'), $returning);
        return $chart->render();
    }

    /**
     * Get pie chart data of dwell times
     *
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param array     $filters
     * @param boolean   $canViewOthers
     *
     * @return array
     */
    public function getDwellTimesPieChartData(\DateTime $dateFrom, \DateTime $dateTo, $filters = array(), $canViewOthers = true)
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
     * Get a list of popular (by hits) pages
     *
     * @param integer   $limit
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param array     $filters
     * @param boolean   $canViewOthers
     *
     * @return array
     */
    public function getPopularPages($limit = 10, \DateTime $dateFrom = null, \DateTime $dateTo = null, $filters = array(), $canViewOthers = true)
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
                ->setParameter('userId', $this->user->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_hit');

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * Get a list of pages created in a date range
     *
     * @param integer   $limit
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param array     $filters
     * @param boolean   $canViewOthers
     *
     * @return array
     */
    public function getPageList($limit = 10, \DateTime $dateFrom = null, \DateTime $dateTo = null, $filters = array(), $canViewOthers = true)
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('t.id, t.title AS name, t.date_added, t.date_modified')
            ->from(MAUTIC_TABLE_PREFIX.'pages', 't')
            ->setMaxResults($limit);

        if (!$canViewOthers) {
            $q->andWhere('t.created_by = :userId')
                ->setParameter('userId', $this->user->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        $results = $q->execute()->fetchAll();

        return $results;
    }
}
