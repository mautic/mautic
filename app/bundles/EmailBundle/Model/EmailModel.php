<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Model;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\Swiftmailer\Exception\BatchQueueMaxException;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailEvent;
use Mautic\EmailBundle\Event\EmailOpenEvent;
use Mautic\EmailBundle\EmailEvents;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\BarChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class EmailModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class EmailModel extends FormModel
{
    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * @var ThemeHelper
     */
    protected $themeHelper;

    /**
     * @var Mailbox
     */
    protected $mailboxHelper;

    /**
     * @var MailHelper
     */
    protected $mailHelper;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var TrackableModel
     */
    protected $pageTrackableModel;

    /**
     * @var UserModel
     */
    protected $userModel;

    /**
     * EmailModel constructor.
     *
     * @param IpLookupHelper $ipLookupHelper
     * @param ThemeHelper    $themeHelper
     * @param Mailbox        $mailboxHelper
     * @param MailHelper     $mailHelper
     * @param LeadModel      $leadModel
     * @param TrackableModel $pageTrackableModel
     * @param UserModel      $userModel
     */
    public function __construct(
        IpLookupHelper $ipLookupHelper,
        ThemeHelper $themeHelper,
        Mailbox $mailboxHelper,
        MailHelper $mailHelper,
        LeadModel $leadModel,
        TrackableModel $pageTrackableModel,
        UserModel $userModel
    ) {
        $this->ipLookupHelper     = $ipLookupHelper;
        $this->themeHelper        = $themeHelper;
        $this->mailboxHelper      = $mailboxHelper;
        $this->mailHelper         = $mailHelper;
        $this->leadModel          = $leadModel;
        $this->pageTrackableModel = $pageTrackableModel;
        $this->userModel          = $userModel;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\EmailBundle\Entity\EmailRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticEmailBundle:Email');
    }

    /**
     * @return \Mautic\EmailBundle\Entity\StatRepository
     */
    public function getStatRepository()
    {
        return $this->em->getRepository('MauticEmailBundle:Stat');
    }

    /**
     * @return \Mautic\EmailBundle\Entity\CopyRepository
     */
    public function getCopyRepository()
    {
        return $this->em->getRepository('MauticEmailBundle:Copy');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'email:emails';
    }

    /**
     * {@inheritdoc}
     *
     * @param Email $entity
     * @param       $unlock
     *
     * @return mixed
     */
    public function saveEntity($entity, $unlock = true)
    {
        $now = new DateTimeHelper();

        $type = $entity->getEmailType();
        if (empty($type)) {
            // Just in case JS failed
            $entity->setEmailType('template');
        }

        // Ensure that list emails are published
        if ($entity->getEmailType() == 'list') {
            $entity->setIsPublished(true);
            $entity->setPublishDown(null);
            $entity->setPublishUp(null);
        }

        //set the author for new pages
        if (!$entity->isNew()) {
            //increase the revision
            $revision = $entity->getRevision();
            $revision++;
            $entity->setRevision($revision);
        }

        // Ensure links in template content don't have encoded ampersands
        if ($entity->getTemplate()) {
            $content = $entity->getContent();

            foreach ($content as $key => $value) {
                $content[$key] = $this->cleanUrlsInContent($value);
            }

            $entity->setContent($content);
        } else {
            // Ensure links in HTML don't have encoded ampersands
            $htmlContent = $this->cleanUrlsInContent($entity->getCustomHtml());
            $entity->setCustomHtml($htmlContent);
        }

        // Ensure links in PLAIN TEXT don't have encoded ampersands
        $plainContent = $this->cleanUrlsInContent($entity->getPlainText());
        $entity->setPlainText($plainContent);

        // Reset the variant hit and start date if there are any changes and if this is an A/B test
        // Do it here in addition to the blanket resetVariants call so that it's available to the event listeners
        $changes = $entity->getChanges();
        $parent  = $entity->getVariantParent();

        if ($parent !== null && !empty($changes) && empty($this->inConversion)) {
            $entity->setVariantSentCount(0);
            $entity->setVariantReadCount(0);
            $entity->setVariantStartDate($now->getDateTime());
        }

        parent::saveEntity($entity, $unlock);

        // If parent, add this entity as a child of the parent so that it populates the list in the tab (due to Doctrine hanging on to entities in memory)
        if ($parent) {
            $parent->addVariantChild($entity);
        }

        // Reset associated variants if applicable due to changes
        if ($entity->isVariant() && !empty($changes) && empty($this->inConversion)) {
            $dateString = $now->toUtcString();
            $parentId   = (!empty($parent)) ? $parent->getId() : $entity->getId();
            $this->getRepository()->resetVariants($parentId, $dateString);

            //if the parent was changed, then that parent/children must also be reset
            if (isset($changes['variantParent'])) {
                $this->getRepository()->resetVariants($changes['variantParent'][0], $dateString);
            }
        }
    }

    /**
     * Save an array of entities
     *
     * @param  $entities
     * @param  $unlock
     *
     * @return array
     */
    public function saveEntities($entities, $unlock = true)
    {
        //iterate over the results so the events are dispatched on each delete
        $batchSize = 20;
        foreach ($entities as $k => $entity) {
            $isNew = ($entity->getId()) ? false : true;

            //set some defaults
            $this->setTimestamps($entity, $isNew, $unlock);

            if ($dispatchEvent = $entity instanceof Email) {
                $event = $this->dispatchEvent("pre_save", $entity, $isNew);
            }

            $this->getRepository()->saveEntity($entity, false);

            if ($dispatchEvent) {
                $this->dispatchEvent("post_save", $entity, $isNew, $event);
            }

            if ((($k + 1) % $batchSize) === 0) {
                $this->em->flush();
            }
        }
        $this->em->flush();
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
        $this->getRepository()->nullVariantParent($entity->getId());

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
        $this->getRepository()->nullVariantParent($ids);

        return parent::deleteEntities($ids);
    }

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $formFactory
     * @param null  $action
     * @param array $options
     *
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof Email) {
            throw new MethodNotAllowedHttpException(['Email']);
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create('emailform', $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     *
     * @return null|Email
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            $entity = new Email();
            $entity->setSessionId('new_'.hash('sha1', uniqid(mt_rand())));
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
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Email) {
            throw new MethodNotAllowedHttpException(['Email']);
        }

        switch ($action) {
            case "pre_save":
                $name = EmailEvents::EMAIL_PRE_SAVE;
                break;
            case "post_save":
                $name = EmailEvents::EMAIL_POST_SAVE;
                break;
            case "pre_delete":
                $name = EmailEvents::EMAIL_PRE_DELETE;
                break;
            case "post_delete":
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

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param string|Stat $stat
     * @param             $request
     * @param bool        $viaBrowser
     */
    public function hitEmail($stat, $request, $viaBrowser = false)
    {
        if (!$stat instanceof Stat) {
            $stat = $this->getEmailStatus($stat);
        }

        if (!$stat) {
            return;
        }

        $email = $stat->getEmail();

        if ((int) $stat->isRead()) {
            if ($viaBrowser && !$stat->getViewedInBrowser()) {
                //opened via browser so note it
                $stat->setViewedInBrowser($viaBrowser);
            }
        }

        $readDateTime = new DateTimeHelper;
        $stat->setLastOpened($readDateTime->getDateTime());

        $lead = $stat->getLead();
        if ($lead !== null) {
            // Set the lead as current lead
            $this->leadModel->setCurrentLead($lead);
        }

        $firstTime = false;
        if (!$stat->getIsRead()) {
            $firstTime = true;
            $stat->setIsRead(true);
            $stat->setDateRead($readDateTime->getDateTime());

            // Only up counts if associated with both an email and lead
            if ($email && $lead) {
                try {
                    $this->getRepository()->upCount($email->getId(), 'read', 1, $email->isVariant());
                } catch (\Exception $exception) {
                    error_log($exception);
                }
            }
        }

        if ($viaBrowser) {
            $stat->setViewedInBrowser($viaBrowser);
        }

        $stat->addOpenDetails(
            [
                'datetime'  => $readDateTime->toUtcString(),
                'useragent' => $request->server->get('HTTP_USER_AGENT'),
                'inBrowser' => $viaBrowser
            ]
        );

        //check for existing IP
        $ipAddress = $this->ipLookupHelper->getIpAddress();
        $stat->setIpAddress($ipAddress);

        if ($this->dispatcher->hasListeners(EmailEvents::EMAIL_ON_OPEN)) {
            $event = new EmailOpenEvent($stat, $request, $firstTime);
            $this->dispatcher->dispatch(EmailEvents::EMAIL_ON_OPEN, $event);
        }

        if ($email) {
            $this->em->persist($email);
        }

        $this->em->persist($stat);
        $this->em->flush();
    }

    /**
     * Get array of page builder tokens from bundles subscribed PageEvents::PAGE_ON_BUILD
     *
     * @param null|Email   $email
     * @param array|string $requestedComponents all | tokens | tokenSections | abTestWinnerCriteria
     * @param null|string  $tokenFilter
     *
     * @return array
     */
    public function getBuilderComponents (Email $email = null, $requestedComponents = 'all', $tokenFilter = null, $withBC = true)
    {
        $singleComponent = (!is_array($requestedComponents) && $requestedComponents != 'all');
        $components      = [];
        $event           = new EmailBuilderEvent($this->translator, $email, $requestedComponents, $tokenFilter);
        $this->dispatcher->dispatch(EmailEvents::EMAIL_ON_BUILD, $event);

        if (!is_array($requestedComponents)) {
            $requestedComponents = [$requestedComponents];
        }

        foreach ($requestedComponents as $requested) {
            switch ($requested) {
                case 'tokens':
                    $components[$requested] = $event->getTokens($withBC);
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
                    $components['tokens']               = $event->getTokens($withBC);
                    $components['tokenSections']        = $event->getTokenSections();
                    $components['abTestWinnerCriteria'] = $event->getAbTestWinnerCriteria();
                    $components['slotTypes']            = $event->getSlotTypes();
                    break;
            }
        }

        return ($singleComponent) ? $components[$requestedComponents[0]] : $components;
    }

    /**
     * @param $idHash
     *
     * @return Stat
     */
    public function getEmailStatus($idHash)
    {
        return $this->getStatRepository()->getEmailStatus($idHash);
    }

    /**
     * Search for an email stat by email and lead IDs
     *
     * @param $emailId
     * @param $leadId
     *
     * @return array
     */
    public function getEmailStati($emailId, $leadId)
    {
        return $this->getStatRepository()->findBy(
            [
                'email' => (int) $emailId,
                'lead'  => (int) $leadId
            ],
            ['dateSent' => 'DESC']
        );
    }

    /**
     * Get the variant parent/children
     *
     * @param Email $email
     *
     * @return array
     */
    public function getVariants(Email $email)
    {
        $parent = $email->getVariantParent();

        if (!empty($parent)) {
            $children = $parent->getVariantChildren();
        } else {
            $parent   = $email;
            $children = $email->getVariantChildren();
        }

        if (empty($children)) {
            $children = [];
        }

        return [$parent, $children];
    }


    /**
     * Converts a variant to the main page and the main page a variant
     *
     * @param Email $email
     */
    public function convertVariant(Email $email)
    {
        //let saveEntities() know it does not need to set variant start dates
        $this->inConversion = true;

        list($parent, $children) = $this->getVariants($email);

        $save = [];

        //set this email as the parent for the original parent and children
        if ($parent) {
            if ($parent->getId() != $email->getId()) {
                $parent->setIsPublished(false);
                $email->addVariantChild($parent);
                $parent->setVariantParent($email);
            }

            $parent->setVariantStartDate(null);
            $parent->setVariantSentCount(0);

            foreach ($children as $child) {
                //capture child before it's removed from collection
                $save[] = $child;

                $parent->removeVariantChild($child);
            }
        }

        if (count($save)) {
            foreach ($save as $child) {
                if ($child->getId() != $email->getId()) {
                    $child->setIsPublished(false);
                    $email->addVariantChild($child);
                    $child->setVariantParent($email);
                } else {
                    $child->removeVariantParent();
                }

                $child->setVariantSentCount(0);
                $child->setVariantStartDate(null);
            }
        }

        $save[] = $parent;
        $save[] = $email;

        //save the entities
        $this->saveEntities($save, false);
    }

    /**
     * Get a stats for email by list
     *
     * @param Email|int $email
     * @param bool      $includeVariants
     *
     * @return array
     */
    public function getEmailListStats($email, $includeVariants = false)
    {
        if (!$email instanceof Email) {
            $email = $this->getEntity($email);
        }

        if ($includeVariants && $email->isVariant()) {
            $parent = $email->getVariantParent();
            if ($parent) {
                // $email is a variant of another
                $children   = $parent->getVariantChildren();
                $emailIds   = $children->getKeys();
                $emailIds[] = $parent->getId();
            } else {
                $children   = $email->getVariantChildren();
                $emailIds   = $children->getKeys();
                $emailIds[] = $email->getId();
            }
        } else {
            $emailIds = [$email->getId()];
        }

        $lists     = $email->getLists();
        $listCount = count($lists);
        $combined  = [0, 0, 0, 0, 0, 0];

        $chart = new BarChart(
            [
                $this->translator->trans('mautic.email.sent'),
                $this->translator->trans('mautic.email.read'),
                $this->translator->trans('mautic.email.failed'),
                $this->translator->trans('mautic.email.clicked'),
                $this->translator->trans('mautic.email.unsubscribed'),
                $this->translator->trans('mautic.email.bounced')
            ]
        );

        if ($listCount) {
            /** @var \Mautic\EmailBundle\Entity\StatRepository $statRepo */
            $statRepo = $this->em->getRepository('MauticEmailBundle:Stat');

            /** @var \Mautic\LeadBundle\Entity\DoNotContactRepository $dncRepo */
            $dncRepo = $this->em->getRepository('MauticLeadBundle:DoNotContact');

            /** @var \Mautic\PageBundle\Entity\TrackableRepository $trackableRepo */
            $trackableRepo = $this->em->getRepository('MauticPageBundle:Trackable');

            $key = ($listCount > 1) ? 1 : 0;

            foreach ($lists as $l) {
                $sentCount = $statRepo->getSentCount($emailIds, $l->getId());
                $combined[0] += $sentCount;

                $readCount = $statRepo->getReadCount($emailIds, $l->getId());
                $combined[1] += $readCount;

                $failedCount = $statRepo->getFailedCount($emailIds, $l->getId());
                $combined[2] += $failedCount;

                $clickCount = $trackableRepo->getCount('email', $emailIds, $l->getId());
                $combined[3] += $clickCount;

                $unsubscribedCount = $dncRepo->getCount('email', $emailIds, DoNotContact::UNSUBSCRIBED, $l->getId());
                $combined[4] += $unsubscribedCount;

                $bouncedCount = $dncRepo->getCount('email', $emailIds, DoNotContact::BOUNCED, $l->getId());
                $combined[5] += $bouncedCount;

                $chart->setDataset(
                    $l->getName(),
                    [
                        $sentCount,
                        $readCount,
                        $failedCount,
                        $clickCount,
                        $unsubscribedCount,
                        $bouncedCount
                    ],
                    $key
                );

                $key++;
            }
        }

        if ($listCount > 1) {
            $chart->setDataset(
                $this->translator->trans('mautic.email.lists.combined'),
                $combined,
                0
            );
        }

        return $chart->render();
    }

    /**
     * @param           $email
     * @param bool      $includeVariants
     * @param           $unit
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     *
     * @return array
     */
    public function getEmailGeneralStats($email, $includeVariants = false, $unit, \DateTime $dateFrom, \DateTime $dateTo)
    {
        if (!$email instanceof Email) {
            $email = $this->getEntity($email);
        }

        if ($includeVariants && $email->isVariant()) {
            $parent = $email->getVariantParent();
            if ($parent) {
                // $email is a variant of another
                $children   = $parent->getVariantChildren();
                $emailIds   = $children->getKeys();
                $emailIds[] = $parent->getId();
            } else {
                $children   = $email->getVariantChildren();
                $emailIds   = $children->getKeys();
                $emailIds[] = $email->getId();
            }
        } else {
            $emailIds = [$email->getId()];
        }

        $filter = [
            'email_id' => $emailIds,
            'flag'     => 'all'
        ];

        return $this->getEmailsLineChartData($unit, $dateFrom, $dateTo, null, $filter);
    }

    /**
     * Get an array of tracked links
     *
     * @param $emailId
     *
     * @return array
     */
    public function getEmailClickStats($emailId)
    {
        return $this->pageTrackableModel->getTrackableList('email', $emailId);
    }

    /**
     * Get the number of leads this email will be sent to
     *
     * @param Email $email
     * @param mixed $listId          Leads for a specific lead list
     * @param bool  $countOnly       If true, return count otherwise array of leads
     * @param int   $limit           Max number of leads to retrieve
     * @param bool  $includeVariants If false, emails sent to a variant will not be included
     *
     * @return int|array
     */
    public function getPendingLeads(Email $email, $listId = null, $countOnly = false, $limit = null, $includeVariants = true)
    {
        if ($includeVariants && $email->isVariant()) {
            $parent = $email->getVariantParent();
            if ($parent) {
                // $email is a variant of another
                $ids[] = $parent->getId();

                $children   = $parent->getVariantChildren();
                $variantIds = $children->getKeys();

                // Remove $email from the array
                $key = array_search($email->getId(), $variantIds);
                unset($variantIds[$key]);
            } else {
                $children   = $email->getVariantChildren();
                $variantIds = $children->getKeys();
            }
        } else {
            $variantIds = null;
        }

        $total = $this->getRepository()->getEmailPendingLeads($email->getId(), $variantIds, $listId, $countOnly, $limit);

        return $total;
    }

    /**
     * Send an email to lead lists
     *
     * @param Email $email
     * @param array $lists
     * @param int   $limit
     *
     * @return array array(int $sentCount, int $failedCount, array $failedRecipientsByList)
     */
    public function sendEmailToLists(Email $email, $lists = null, $limit = null)
    {
        //get the leads
        if (empty($lists)) {
            $lists = $email->getLists();
        }

        //get email settings such as templates, weights, etc
        $emailSettings = $this->getEmailSettings($email);
        $options       = [
            'source'        => ['email', $email->getId()],
            'emailSettings' => $emailSettings,
            'allowResends'  => false,
            'customHeaders' => [
                'Precedence' => 'Bulk'
            ]
        ];

        $failed      = [];
        $sentCount   = 0;
        $failedCount = 0;

        foreach ($lists as $list) {
            if ($limit !== null && $limit <= 0) {
                // Hit the max for this batch
                break;
            }

            $options['listId'] = $list->getId();
            $leads             = $this->getPendingLeads($email, $list->getId(), false, $limit);
            $leadCount         = count($leads);
            $sentCount += $leadCount;

            if ($limit != null) {
                // Only retrieve the difference between what has already been sent and the limit
                $limit -= $leadCount;
            }

            $listErrors = $this->sendEmail($email, $leads, $options);

            if (!empty($listErrors)) {
                $listFailedCount = count($listErrors);

                $sentCount -= $listFailedCount;
                $failedCount += $listFailedCount;

                $failed[$options['listId']] = $listErrors;
            }
        }

        return [$sentCount, $failedCount, $failed];
    }

    /**
     * Gets template, stats, weights, etc for an email in preparation to be sent
     *
     * @param Email $email
     * @param bool  $includeVariants
     *
     * @return array
     */
    public function getEmailSettings(Email $email, $includeVariants = true)
    {
        static $emailSettings = [];

        if (empty($emailSettings[$email->getId()])) {

            //used to house slots so they don't have to be fetched over and over for same template
            $slots = [];
            if ($template = $email->getTemplate()) {
                $slots[$template] = $this->themeHelper->getTheme($template)->getSlots('email');
            }

            //store the settings of all the variants in order to properly disperse the emails
            //set the parent's settings
            $emailSettings = [
                $email->getId() => [
                    'template'     => $email->getTemplate(),
                    'slots'        => $slots,
                    'sentCount'    => $email->getSentCount(),
                    'variantCount' => $email->getVariantSentCount(),
                    'entity'       => $email
                ]
            ];

            if ($includeVariants) {
                //get a list of variants for A/B testing
                $childrenVariant = $email->getVariantChildren();

                if (count($childrenVariant)) {
                    $variantWeight = 0;
                    $totalSent     = $email->getVariantSentCount();

                    foreach ($childrenVariant as $id => $child) {
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
                            $variantSettings = $child->getVariantSettings();

                            $emailSettings[$child->getId()] = [
                                'template'     => $child->getTemplate(),
                                'slots'        => $useSlots,
                                'sentCount'    => $child->getSentCount(),
                                'variantCount' => $child->getVariantSentCount(),
                                'weight'       => ($variantSettings['weight'] / 100),
                                'entity'       => $child
                            ];

                            $variantWeight += $variantSettings['weight'];
                            $totalSent += $emailSettings[$child->getId()]['sentCount'];
                        }
                    }

                    //set parent weight
                    $emailSettings[$email->getId()]['weight'] = ((100 - $variantWeight) / 100);

                    //now find what percentage of current leads should receive the variants
                    foreach ($emailSettings as $eid => &$details) {
                        $details['weight'] = ($totalSent)
                            ?
                            ($details['weight'] - ($details['variantCount'] / $totalSent)) + $details['weight']
                            :
                            $details['weight'];
                    }
                } else {
                    $emailSettings[$email->getId()]['weight'] = 1;
                }
            }
        }

        return $emailSettings;
    }

    /**
     * Send an email to lead(s)
     *
     * @param       $email
     * @param       $leads
     * @param       $options = array()
     *                       array source array('model', 'id')
     *                       array emailSettings
     *                       int   listId
     *                       bool  allowResends     If false, exact emails (by id) already sent to the lead will not be resent
     *                       bool  ignoreDNC        If true, emails listed in the do not contact table will still get the email
     *                       bool  sendBatchMail    If false, the function will not send batched mail but will defer to calling function to handle it
     *                       array assetAttachments Array of optional Asset IDs to attach
     *
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     */
    public function sendEmail($email, $leads, $options = [])
    {
        $source           = (isset($options['source'])) ? $options['source'] : null;
        $emailSettings    = (isset($options['emailSettings'])) ? $options['emailSettings'] : [];
        $listId           = (isset($options['listId'])) ? $options['listId'] : null;
        $ignoreDNC        = (isset($options['ignoreDNC'])) ? $options['ignoreDNC'] : false;
        $allowResends     = (isset($options['allowResends'])) ? $options['allowResends'] : true;
        $tokens           = (isset($options['tokens'])) ? $options['tokens'] : [];
        $sendBatchMail    = (isset($options['sendBatchMail'])) ? $options['sendBatchMail'] : true;
        $assetAttachments = (isset($options['assetAttachments'])) ? $options['assetAttachments'] : [];
        $customHeaders    = (isset($options['customHeaders'])) ? $options['customHeaders'] : [];

        if (!$email->getId()) {
            return false;
        }

        $singleEmail = false;
        if (isset($leads['id'])) {
            $singleEmail = true;
            $leads       = [$leads['id'] => $leads];
        }

        /** @var \Mautic\EmailBundle\Entity\StatRepository $statRepo */
        $statRepo = $this->em->getRepository('MauticEmailBundle:Stat');
        /** @var \Mautic\EmailBundle\Entity\EmailRepository $emailRepo */
        $emailRepo = $this->getRepository();

        if (empty($emailSettings)) {
            //get email settings such as templates, weights, etc
            $emailSettings = $this->getEmailSettings($email);
        }

        if (!$allowResends) {
            static $sent = [];
            if (!isset($sent[$email->getId()])) {
                $sent[$email->getId()] = $statRepo->getSentStats($email->getId(), $listId);
            }
            $sendTo = array_diff_key($leads, $sent[$email->getId()]);
        } else {
            $sendTo = $leads;
        }

        if (!$ignoreDNC) {
            //get the list of do not contacts
            static $dnc;
            if (!is_array($dnc)) {
                $dnc = $emailRepo->getDoNotEmailList();
            }

            //weed out do not contacts
            if (!empty($dnc)) {
                foreach ($sendTo as $k => $lead) {
                    if (in_array(strtolower($lead['email']), $dnc)) {
                        unset($sendTo[$k]);
                    }
                }
            }
        }

        //get a count of leads
        $count = count($sendTo);

        //noone to send to so bail
        if (empty($count)) {
            return $singleEmail ? true : [];
        }

        //how many of this batch should go to which email
        $batchCount = 0;

        $backup = reset($emailSettings);
        foreach ($emailSettings as $eid => &$details) {
            if (isset($details['weight'])) {
                $limit = round($count * $details['weight']);

                if (!$limit) {
                    // Don't send any emails to this one
                    unset($emailSettings[$eid]);
                } else {
                    $details['limit'] = $limit;
                }
            } else {
                $details['limit'] = $count;
            }
        }

        if (count($emailSettings) == 0) {
            // Shouldn't happen but a safety catch
            $emailSettings[$backup['entity']->getId()] = $backup;
        }

        //randomize the leads for statistic purposes
        shuffle($sendTo);

        //start at the beginning for this batch
        $useEmail = reset($emailSettings);
        $errors   = [];
        // Store stat entities
        $saveEntities    = [];
        $emailSentCounts = [];

        $mailer = $this->mailHelper->getMailer(!$sendBatchMail);

        $contentGenerated = false;

        $flushQueue = function ($reset = true) use (&$mailer, &$saveEntities, &$errors, &$emailSentCounts, $sendBatchMail) {

            if ($sendBatchMail) {
                $flushResult = $mailer->flushQueue();
                if (!$flushResult) {
                    $sendFailures = $mailer->getErrors();

                    // Check to see if failed recipients were stored by the transport
                    if (!empty($sendFailures['failures'])) {
                        // Prevent the stat from saving
                        foreach ($sendFailures['failures'] as $failedEmail) {
                            // Add lead ID to list of failures
                            $errors[$saveEntities[$failedEmail]->getLead()->getId()] = $failedEmail;

                            // Down sent counts
                            $emailId = $saveEntities[$failedEmail]->getEmail()->getId();
                            $emailSentCounts[$emailId]++;

                            // Delete the stat
                            unset($saveEntities[$failedEmail]);
                        }
                    }
                }

                if ($reset) {
                    $mailer->reset(true);
                }

                return $flushResult;
            }

            return true;
        };

        foreach ($sendTo as $lead) {
            // Generate content
            if ($useEmail['entity']->getId() !== $contentGenerated) {
                // Flush the mail queue if applicable
                $flushQueue();

                $contentGenerated = $useEmail['entity']->getId();

                // Use batching/tokenization if supported
                $mailer->useMailerTokenization();
                $mailer->setSource($source);
                $mailer->setEmail($useEmail['entity'], true, $useEmail['slots'], $assetAttachments);

                if (!empty($customHeaders)) {
                    $mailer->setCustomHeaders($customHeaders);
                }
            }

            $idHash = uniqid();

            // Add tracking pixel token
            if (!empty($tokens)) {
                $mailer->setTokens($tokens);
            }

            $mailer->setLead($lead);
            $mailer->setIdHash($idHash);

            try {
                if (!$mailer->addTo($lead['email'], $lead['firstname'].' '.$lead['lastname'])) {
                    // Clear the errors so it doesn't stop the next send
                    $mailer->clearErrors();

                    // Bad email so note and continue
                    $errors[$lead['id']] = $lead['email'];

                    continue;
                }
            } catch (BatchQueueMaxException $e) {
                // Queue full so flush then try again
                $flushQueue(false);

                $mailer->addTo($lead['email'], $lead['firstname'].' '.$lead['lastname']);
            }

            //queue or send the message
            if (!$mailer->queue(true)) {
                $errors[$lead['id']] = $lead['email'];

                continue;
            }

            if (!$allowResends) {
                $sent[$useEmail['entity']->getId()][$lead['id']] = $lead['id'];
            }

            //create a stat
            $saveEntities[$lead['email']] = $mailer->createEmailStat(false, null, $listId);

            // Up sent counts
            $emailId = $useEmail['entity']->getId();
            if (!isset($emailSentCounts[$emailId])) {
                $emailSentCounts[$emailId] = 0;
            }
            $emailSentCounts[$emailId]++;

            $batchCount++;
            if ($batchCount >= $useEmail['limit']) {
                unset($useEmail);

                //use the next email
                $batchCount = 0;
                $useEmail   = next($emailSettings);
            }
        }

        // Send batched mail if applicable
        $flushQueue();

        // Persist stats
        $statRepo->saveEntities($saveEntities);

        // Update sent counts
        foreach ($emailSentCounts as $emailId => $count) {
            $isVariant = $emailSettings[$emailId]['entity']->getVariantStartDate();

            try {
                $this->getRepository()->upCount($emailId, 'sent', $count, !empty($isVariant));
            } catch (\Exception $exception) {
                error_log($exception);
            }
        }

        // Free RAM
        foreach ($saveEntities as $stat) {
            $this->em->detach($stat);
            unset($stat);
        }

        unset($emailSentCounts, $emailSettings, $options, $tokens, $useEmail, $sendTo);

        return $singleEmail ? (empty($errors)) : $errors;
    }

    /**
     * Send an email to lead(s)
     *
     * @param       $email
     * @param       $users
     * @param mixed $lead
     * @param array $tokens
     * @param array $assetAttachments
     * @param bool  $saveStat
     *
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     */
    public function sendEmailToUser($email, $users, $lead = null, $tokens = [], $assetAttachments = [], $saveStat = true)
    {
        if (!$emailId = $email->getId()) {
            return false;
        }

        if (!is_array($users)) {
            $user  = ['id' => $users];
            $users = [$user];
        }

        //get email settings
        $emailSettings = $this->getEmailSettings($email, false);

        //noone to send to so bail
        if (empty($users)) {
            return false;
        }

        $mailer = $this->mailHelper->getMailer();
        $mailer->setLead($lead, true);
        $mailer->setTokens($tokens);
        $mailer->setEmail($email, false, $emailSettings[$emailId]['slots'], $assetAttachments, (!$saveStat));

        $mailer->useMailerTokenization();

        foreach ($users as $user) {
            $idHash = uniqid();
            $mailer->setIdHash($idHash);

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

            $mailer->setTo($user['email'], $user['firstname'].' '.$user['lastname']);

            $mailer->queue(true);

            if ($saveStat) {
                $saveEntities[] = $mailer->createEmailStat(false, $user['email']);
            }
        }

        //flush the message
        $mailer->flushQueue();

        if (isset($saveEntities)) {
            $this->getStatRepository()->saveEntities($saveEntities);
        }

        //save some memory
        unset($mailer);
    }

    /**
     * @param Stat   $stat
     * @param string $comments
     * @param string $reason
     * @param bool   $flush
     */
    public function setDoNotContact(Stat $stat, $comments, $reason = 'bounced', $flush = true)
    {
        $lead = $stat->getLead();

        if ($lead instanceof Lead) {
            $email   = $stat->getEmail();
            $channel = ($email) ? ['email' => $email->getId()] : 'email';
            $this->leadModel->addDncForLead($lead, $channel, $comments, $reason, $flush);
        }
    }

    /**
     * @param           $email
     * @param string    $reason
     * @param string    $comments
     * @param bool|true $flush
     * @param int|null  $leadId
     */
    public function setEmailDoNotContact($email, $reason = 'bounced', $comments = '', $flush = true, $leadId = null)
    {
        /** @var \Mautic\LeadBundle\Entity\LeadRepository $leadRepo */
        $leadRepo = $this->em->getRepository('MauticLeadBundle:Lead');
        $leadId   = (array) $leadRepo->getLeadByEmail($email, true);

        /** @var \Mautic\LeadBundle\Entity\Lead[] $leads */
        $leads = [];

        foreach ($leadId as $lead) {
            $leads[] = $leadRepo->getEntity($lead['id']);
        }

        foreach ($leads as $lead) {
            $this->leadModel->addDncForLead($lead, 'email', $comments, $reason, $flush);
        }
    }

    /**
     * Processes the callback response from a mailer for bounces and unsubscribes
     *
     * @param array $response
     */
    public function processMailerCallback(array $response)
    {
        if (empty($response)) {

            return;
        }

        $batch = 20;
        $count = 0;

        $statRepo = $this->getStatRepository();
        $alias    = $statRepo->getTableAlias();
        if (!empty($alias)) {
            $alias .= '.';
        }

        // Keep track to prevent duplicates before flushing
        $emails = [];

        foreach ($response as $type => $entries) {
            if (!empty($entries['hashIds'])) {
                $stats = $this->getStatRepository()->getEntities(
                    [
                        'filter' => [
                            'force' => [
                                [
                                    'column' => $alias.'trackingHash',
                                    'expr'   => 'in',
                                    'value'  => array_keys($entries['hashIds'])
                                ]
                            ]
                        ]
                    ]
                );

                /** @var \Mautic\EmailBundle\Entity\Stat $s */
                foreach ($stats as $s) {
                    $reason = $entries['hashIds'][$s->getTrackingHash()];
                    if ($this->translator->hasId('mautic.email.bounce.reason.'.$reason)) {
                        $reason = $this->translator->trans('mautic.email.bounce.reason.'.$reason);
                    }

                    $this->setDoNotContact($s, $reason, $type, ($count === $batch));

                    $s->setIsFailed(true);
                    $this->em->persist($s);

                    if ($count === $batch) {
                        $count = 0;
                    }
                }
            }

            if (!empty($entries['emails'])) {
                foreach ($entries['emails'] as $email => $reason) {
                    if (in_array($email, $emails)) {

                        continue;
                    }
                    $emails[] = $email;

                    $leadId = null;
                    if (is_array($reason)) {
                        // Includes a lead ID
                        $leadId = $reason['leadId'];
                        $reason = $reason['reason'];
                    }

                    if ($this->translator->hasId('mautic.email.bounce.reason.'.$reason)) {
                        $reason = $this->translator->trans('mautic.email.bounce.reason.'.$reason);
                    }

                    $this->setEmailDoNotContact($email, $type, $reason, ($count === $batch), $leadId);

                    if ($count === $batch) {
                        $count = 0;
                    }
                }
            }
        }

        $this->em->flush();
    }

    /**
     * Get the settings for a monitored mailbox or false if not enabled
     *
     * @param $bundleKey
     * @param $folderKey
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
     * Joins the email table and limits created_by to currently logged in user
     *
     * @param QueryBuilder $q
     */
    public function limitQueryToCreator(QueryBuilder &$q)
    {
        $q->join('t', MAUTIC_TABLE_PREFIX.'emails', 'e', 'e.id = t.email_id')
            ->andWhere('e.created_by = :userId')
            ->setParameter('userId', $this->user->getId());
    }

    /**
     * Get line chart data of emails sent and read
     *
     * @param char      $unit {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param string    $dateFormat
     * @param array     $filter
     * @param boolean   $canViewOthers
     *
     * @return array
     */
    public function getEmailsLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [], $canViewOthers = true)
    {
        $flag = null;

        if (isset($filter['flag'])) {
            $flag = $filter['flag'];
            unset($filter['flag']);
        }

        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);

        if ($flag == 'sent_and_opened_and_failed' || $flag == 'all' || $flag == 'sent_and_opened' || !$flag) {
            $q = $query->prepareTimeDataQuery('email_stats', 'date_sent', $filter);
            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }
            $data = $query->loadAndBuildTimeData($q);
            $chart->setDataset($this->translator->trans('mautic.email.sent.emails'), $data);
        }

        if ($flag == 'sent_and_opened_and_failed' || $flag == 'all' || $flag == 'sent_and_opened' || $flag == 'opened') {
            $q = $query->prepareTimeDataQuery('email_stats', 'date_read', $filter);
            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }
            $data = $query->loadAndBuildTimeData($q);
            $chart->setDataset($this->translator->trans('mautic.email.read.emails'), $data);
        }

        if ($flag == 'sent_and_opened_and_failed' || $flag == 'all' || $flag == 'failed') {
            $q = $query->prepareTimeDataQuery('email_stats', 'date_sent', $filter);
            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }
            $q->andWhere($q->expr()->eq('t.is_failed', ':true'))
                ->setParameter('true', true, 'boolean');
            $data = $query->loadAndBuildTimeData($q);
            $chart->setDataset($this->translator->trans('mautic.email.failed.emails'), $data);
        }

        if ($flag == 'all' || $flag == 'clicked') {
            $q = $query->prepareTimeDataQuery('page_hits', 'date_hit', [])
                ->join('t', MAUTIC_TABLE_PREFIX.'channel_url_trackables', 'cut', 't.redirect_id = cut.redirect_id')
                ->andWhere('cut.channel = :channel')
                ->setParameter('channel', 'email');

            if (isset($filter['email_id'])) {
                if (is_array($filter['email_id'])) {
                    $q->andWhere('cut.channel_id IN(:channel_id)');
                    $q->setParameter('channel_id', implode(',', $filter['email_id']));
                } else {
                    $q->andWhere('cut.channel_id = :channel_id');
                    $q->setParameter('channel_id', $filter['email_id']);
                }
            }


            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }

            $data = $query->loadAndBuildTimeData($q);
            $chart->setDataset($this->translator->trans('mautic.email.clicked'), $data);
        }

        if ($flag == 'all' || $flag == 'unsubscribed') {
            $data = $this->getDncLineChartDataset($query, $filter, DoNotContact::UNSUBSCRIBED, $canViewOthers);
            $chart->setDataset($this->translator->trans('mautic.email.unsubscribed'), $data);
        }

        if ($flag == 'all' || $flag == 'bounced') {
            $data = $this->getDncLineChartDataset($query, $filter, DoNotContact::BOUNCED, $canViewOthers);
            $chart->setDataset($this->translator->trans('mautic.email.bounced'), $data);
        }

        return $chart->render();
    }

    /**
     * Modifies the line chart query for the DNC
     *
     * @param ChartQuery $q
     * @param array      $filter
     * @param boolean    $reason
     * @param boolean    $canViewOthers
     */
    public function getDncLineChartDataset(ChartQuery &$query, array $filter, $reason, $canViewOthers)
    {
        $dncFilter = isset($filter['email_id']) ? ['channel_id' => $filter['email_id']] : [];
        $q         = $query->prepareTimeDataQuery('lead_donotcontact', 'date_added', $dncFilter);
        $q->andWhere('t.channel = :channel')
            ->setParameter('channel', 'email')
            ->andWhere($q->expr()->eq('t.reason', ':reason'))
            ->setParameter('reason', $reason);

        if (!$canViewOthers) {
            $this->limitQueryToCreator($q);
        }

        return $data = $query->loadAndBuildTimeData($q);
    }

    /**
     * Get pie chart data of ignored vs opened emails
     *
     * @param string  $dateFrom
     * @param string  $dateTo
     * @param array   $filters
     * @param boolean $canViewOthers
     *
     * @return array
     */
    public function getIgnoredVsReadPieChartData($dateFrom, $dateTo, $filters = [], $canViewOthers = true)
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


        $chart->setDataset($this->translator->trans('mautic.email.graph.pie.ignored.read.failed.ignored'), ($sent - $read));
        $chart->setDataset($this->translator->trans('mautic.email.graph.pie.ignored.read.failed.read'), $read);
        $chart->setDataset($this->translator->trans('mautic.email.graph.pie.ignored.read.failed.failed'), $failed);

        return $chart->render();
    }

    /**
     * Get a list of emails in a date range, grouped by a stat date count
     *
     * @param integer   $limit
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param array     $filters
     * @param array     $options
     *
     * @return array
     */
    public function getEmailStatList($limit = 10, \DateTime $dateFrom = null, \DateTime $dateTo = null, $filters = [], $options = [])
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(DISTINCT t.id) AS count, e.id, e.name')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 't')
            ->join('t', MAUTIC_TABLE_PREFIX.'emails', 'e', 'e.id = t.email_id')
            ->orderBy('count', 'DESC')
            ->groupBy('e.id')
            ->setMaxResults($limit);

        if (!empty($options['canViewOthers'])) {
            $q->andWhere('e.created_by = :userId')
                ->setParameter('userId', $this->user->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);

        if (isset($options['groupBy']) && $options['groupBy'] == 'sends') {
            $chartQuery->applyDateFilters($q, 'date_sent');
        }

        if (isset($options['groupBy']) && $options['groupBy'] == 'reads') {
            $chartQuery->applyDateFilters($q, 'date_read');
        }

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * Get a list of emails in a date range
     *
     * @param integer   $limit
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param array     $filters
     * @param array     $options
     *
     * @return array
     */
    public function getEmailList($limit = 10, \DateTime $dateFrom = null, \DateTime $dateTo = null, $filters = [], $options = [])
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('t.id, t.name, t.date_added, t.date_modified')
            ->from(MAUTIC_TABLE_PREFIX.'emails', 't')
            ->setMaxResults($limit);

        if (!empty($options['canViewOthers'])) {
            $q->andWhere('t.created_by = :userId')
                ->setParameter('userId', $this->user->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * Get a list of upcoming emails
     *
     * @param integer $limit
     * @param boolean $canViewOthers
     *
     * @return array
     */
    public function getUpcomingEmails($limit = 10, $canViewOthers = true)
    {
        /** @var \Mautic\CampaignBundle\Entity\LeadEventLogRepository $leadEventLogRepository */
        $leadEventLogRepository = $this->em->getRepository('MauticCampaignBundle:LeadEventLog');
        $leadEventLogRepository->setCurrentUser($this->user);
        $upcomingEmails = $leadEventLogRepository->getUpcomingEvents(
            [
                'type'          => 'email.send',
                'scheduled'     => 1,
                'eventType'     => 'action',
                'limit'         => $limit,
                'canViewOthers' => $canViewOthers
            ]
        );

        return $upcomingEmails;
    }

    /**
     * Check all links in content and remove &amp;
     * This even works with double encoded ampersands
     *
     * @param string $content
     *
     * @return string
     */
    private function cleanUrlsInContent($content)
    {
        if (preg_match_all('/((https?|ftps?):\/\/)([a-zA-Z0-9-\.{}]*[a-zA-Z0-9=}]*)(\??)([^\s\"\]]+)?/i', $content, $matches)) {
            foreach ($matches[0] as $url) {
                $newUrl = $url;

                while (strpos($newUrl, '&amp;') !== false) {
                    $newUrl = str_replace('&amp;', '&', $newUrl);
                }

                $content = str_replace($url, $newUrl, $content);
            }
        }

        return $content;
    }
}
