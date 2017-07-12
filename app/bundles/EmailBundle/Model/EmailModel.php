<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\ChannelBundle\Model\MessageQueueModel;
use Mautic\CoreBundle\Helper\Chart\BarChart;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Mautic\CoreBundle\Model\BuilderModelTrait;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\TranslationModelTrait;
use Mautic\CoreBundle\Model\VariantModelTrait;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatDevice;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailEvent;
use Mautic\EmailBundle\Event\EmailOpenEvent;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\Swiftmailer\Exception\BatchQueueMaxException;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\DeviceModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class EmailModel
 * {@inheritdoc}
 */
class EmailModel extends FormModel implements AjaxLookupModelInterface
{
    use VariantModelTrait;
    use TranslationModelTrait;
    use BuilderModelTrait;

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
     * @var CompanyModel
     */
    protected $companyModel;

    /**
     * @var TrackableModel
     */
    protected $pageTrackableModel;

    /**
     * @var UserModel
     */
    protected $userModel;

    /**
     * @var MessageQueueModel
     */
    protected $messageQueueModel;

    /**
     * @var bool
     */
    protected $updatingTranslationChildren = false;

    /**
     * @var array
     */
    protected $emailSettings = [];

    /**
     * @var DeviceModel
     */
    protected $deviceModel;

    /**
     * EmailModel constructor.
     *
     * @param IpLookupHelper    $ipLookupHelper
     * @param ThemeHelper       $themeHelper
     * @param Mailbox           $mailboxHelper
     * @param MailHelper        $mailHelper
     * @param LeadModel         $leadModel
     * @param CompanyModel      $companyModel
     * @param TrackableModel    $pageTrackableModel
     * @param UserModel         $userModel
     * @param MessageQueueModel $messageQueueModel
     * @param DeviceModel       $deviceModel
     */
    public function __construct(
        IpLookupHelper $ipLookupHelper,
        ThemeHelper $themeHelper,
        Mailbox $mailboxHelper,
        MailHelper $mailHelper,
        LeadModel $leadModel,
        CompanyModel $companyModel,
        TrackableModel $pageTrackableModel,
        UserModel $userModel,
        MessageQueueModel $messageQueueModel,
        DeviceModel $deviceModel
    )
    {
        $this->ipLookupHelper     = $ipLookupHelper;
        $this->themeHelper        = $themeHelper;
        $this->mailboxHelper      = $mailboxHelper;
        $this->mailHelper         = $mailHelper;
        $this->leadModel          = $leadModel;
        $this->companyModel       = $companyModel;
        $this->pageTrackableModel = $pageTrackableModel;
        $this->userModel          = $userModel;
        $this->messageQueueModel  = $messageQueueModel;
        $this->deviceModel        = $deviceModel;
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
     * @return \Mautic\EmailBundle\Entity\StatDeviceRepository
     */
    public function getStatDeviceRepository()
    {
        return $this->em->getRepository('MauticEmailBundle:StatDevice');
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
        $type = $entity->getEmailType();
        if (empty($type)) {
            // Just in case JS failed
            $entity->setEmailType('template');
        }

        // Ensure that list emails are published
        if ($entity->getEmailType() == 'list') {
            // Ensure that this email has the same lists assigned as the translated parent if applicable
            /** @var Email $translationParent */
            if ($translationParent = $entity->getTranslationParent()) {
                $parentLists = $translationParent->getLists()->toArray();
                $entity->setLists($parentLists);
            }
        } else {
            // Ensure that all lists are been removed in case of a clone
            $entity->setLists([]);
        }

        if (!$this->updatingTranslationChildren) {
            if (!$entity->isNew()) {
                //increase the revision
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
            if ($entity->getEmailType() == 'list' && $entity->hasTranslations()) {
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
                $event = $this->dispatchEvent('pre_save', $entity, $isNew);
            }

            $this->getRepository()->saveEntity($entity, false);

            if ($dispatchEvent) {
                $this->dispatchEvent('post_save', $entity, $isNew, $event);
            }

            if ((($k + 1) % $batchSize) === 0) {
                $this->em->flush();
            }
        }
        $this->em->flush();
    }

    /**
     * @param Email $entity
     */
    public function deleteEntity($entity)
    {
        if ($entity->isVariant() && $entity->getIsPublished()) {
            $this->resetVariants($entity);
        }

        parent::deleteEntity($entity);
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
     *
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
     * Get a specific entity or generate a new one if id is empty.
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

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param      $stat
     * @param      $request
     * @param bool $viaBrowser
     *
     * @throws \Exception
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

        $readDateTime = new DateTimeHelper();
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
                'inBrowser' => $viaBrowser,
            ]
        );

        //check for existing IP
        $ipAddress = $this->ipLookupHelper->getIpAddress();
        $stat->setIpAddress($ipAddress);

        if ($this->dispatcher->hasListeners(EmailEvents::EMAIL_ON_OPEN)) {
            $event = new EmailOpenEvent($stat, $request, $firstTime);
            $this->dispatcher->dispatch(EmailEvents::EMAIL_ON_OPEN, $event);
        }

        $emailOpenDevice = $this->deviceModel->getContactDeviceFromUserAgent($lead, $request->server->get('HTTP_USER_AGENT'), $readDateTime->getUtcDateTime());

        if ($email) {
            $this->em->persist($email);
            $this->em->flush($email);
        }

        if (isset($emailOpenDevice) and is_object($emailOpenDevice)) {
            $emailOpenStat = new StatDevice();
            $emailOpenStat->setIpAddress($ipAddress);
            $emailOpenStat->setDevice($emailOpenDevice);
            $emailOpenStat->setDateOpened($readDateTime->toUtcString());
            $emailOpenStat->setStat($stat);

            $this->em->persist($emailOpenStat);
            $this->em->flush($emailOpenStat);
        }

        $this->em->persist($stat);
        $this->em->flush();
    }

    /**
     * Get array of page builder tokens from bundles subscribed PageEvents::PAGE_ON_BUILD.
     *
     * @param null|Email   $email
     * @param array|string $requestedComponents all | tokens | abTestWinnerCriteria
     * @param null|string  $tokenFilter
     *
     * @return array
     */
    public function getBuilderComponents(Email $email = null, $requestedComponents = 'all', $tokenFilter = null, $withBC = true)
    {
        $event = new EmailBuilderEvent($this->translator, $email, $requestedComponents, $tokenFilter);
        $this->dispatcher->dispatch(EmailEvents::EMAIL_ON_BUILD, $event);

        return $this->getCommonBuilderComponents($requestedComponents, $event);
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
     * Search for an email stat by email and lead IDs.
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
                'lead'  => (int) $leadId,
            ],
            ['dateSent' => 'DESC']
        );
    }

    /**
     * Get a stats for email by list.
     *
     * @param                $email
     * @param bool           $includeVariants
     * @param \DateTime|null $dateFrom
     * @param \DateTime|null $dateTo
     *
     * @return array
     */
    public function getEmailListStats($email, $includeVariants = false, \DateTime $dateFrom = null, \DateTime $dateTo = null)
    {
        if (!$email instanceof Email) {
            $email = $this->getEntity($email);
        }

        $emailIds = ($includeVariants && ($email->isVariant() || $email->isTranslation())) ? $email->getRelatedEntityIds() : [$email->getId()];

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
                $this->translator->trans('mautic.email.bounced'),
            ]
        );

        if ($listCount) {
            /** @var \Mautic\EmailBundle\Entity\StatRepository $statRepo */
            $statRepo = $this->em->getRepository('MauticEmailBundle:Stat');

            /** @var \Mautic\LeadBundle\Entity\DoNotContactRepository $dncRepo */
            $dncRepo = $this->em->getRepository('MauticLeadBundle:DoNotContact');

            /** @var \Mautic\PageBundle\Entity\TrackableRepository $trackableRepo */
            $trackableRepo = $this->em->getRepository('MauticPageBundle:Trackable');

            $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
            $key   = ($listCount > 1) ? 1 : 0;

            $sentCounts         = $statRepo->getSentCount($emailIds, true, $query);
            $readCounts         = $statRepo->getReadCount($emailIds, true, $query);
            $failedCounts       = $statRepo->getFailedCount($emailIds, true, $query);
            $clickCounts        = $trackableRepo->getCount('email', $emailIds, $lists->getKeys(), $query);
            $unsubscribedCounts = $dncRepo->getCount('email', $emailIds, DoNotContact::UNSUBSCRIBED, $lists->getKeys(), $query);
            $bouncedCounts      = $dncRepo->getCount('email', $emailIds, DoNotContact::BOUNCED, $lists->getKeys(), $query);

            foreach ($lists as $l) {
                $sentCount = isset($sentCounts[$l->getId()]) ? $sentCounts[$l->getId()] : 0;
                $combined[0] += $sentCount;

                $readCount = isset($readCounts[$l->getId()]) ? $readCounts[$l->getId()] : 0;
                $combined[1] += $readCount;

                $failedCount = isset($failedCounts[$l->getId()]) ? $failedCounts[$l->getId()] : 0;
                $combined[2] += $failedCount;

                $clickCount = isset($clickCounts[$l->getId()]) ? $clickCounts[$l->getId()] : 0;
                $combined[3] += $clickCount;

                $unsubscribedCount = isset($unsubscribedCounts[$l->getId()]) ? $unsubscribedCounts[$l->getId()] : 0;
                $combined[4] += $unsubscribedCount;

                $bouncedCount = isset($bouncedCounts[$l->getId()]) ? $bouncedCounts[$l->getId()] : 0;
                $combined[5] += $bouncedCount;

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
     * Get a stats for email by list.
     *
     * @param Email|int $email
     * @param bool      $includeVariants
     *
     * @return array
     */
    public function getEmailDeviceStats($email, $includeVariants = false, $dateFrom = null, $dateTo = null)
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
     * @param           $email
     * @param bool      $includeVariants
     * @param           $unit
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     *
     * @return array
     */
    public function getEmailGeneralStats($email, $includeVariants, $unit, \DateTime $dateFrom, \DateTime $dateTo)
    {
        if (!$email instanceof Email) {
            $email = $this->getEntity($email);
        }

        $filter = [
            'email_id' => ($includeVariants) ? $email->getRelatedEntityIds() : [$email->getId()],
            'flag'     => 'all',
        ];

        return $this->getEmailsLineChartData($unit, $dateFrom, $dateTo, null, $filter);
    }

    /**
     * Get an array of tracked links.
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
     * Get the number of leads this email will be sent to.
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
        $variantIds = ($includeVariants) ? $email->getRelatedEntityIds() : null;
        $total      = $this->getRepository()->getEmailPendingLeads($email->getId(), $variantIds, $listId, $countOnly, $limit);

        return $total;
    }

    /**
     * @param Email $email
     * @param bool  $includeVariants
     *
     * @return array|int
     */
    public function getQueuedCounts(Email $email, $includeVariants = true)
    {
        $ids = ($includeVariants) ? $email->getRelatedEntityIds() : null;
        if (!in_array($email->getId(), $ids)) {
            $ids[] = $email->getId();
        }

        return $this->messageQueueModel->getQueuedChannelCount('email', $ids);
    }

    /**
     * Send an email to lead lists.
     *
     * @param Email           $email
     * @param array           $lists
     * @param int             $limit
     * @param bool            $batch  True to process and batch all pending leads
     * @param OutputInterface $output
     *
     * @return array array(int $sentCount, int $failedCount, array $failedRecipientsByList)
     */
    public function sendEmailToLists(Email $email, $lists = null, $limit = null, $batch = false, OutputInterface $output = null)
    {
        //get the leads
        if (empty($lists)) {
            $lists = $email->getLists();
        }

        // Safety check
        if ('list' !== $email->getEmailType()) {
            return [0, 0, []];
        }

        $options = [
            'source'        => ['email', $email->getId()],
            'allowResends'  => false,
            'customHeaders' => [
                'Precedence' => 'Bulk',
            ],
        ];

        $failed      = [];
        $sentCount   = 0;
        $failedCount = 0;

        $progress = false;
        if ($batch && $output) {
            $progressCounter = 0;
            $totalLeadCount  = $this->getPendingLeads($email, null, true);
            if (!$totalLeadCount) {
                return;
            }

            // Broadcast send through CLI
            $output->writeln("\n<info>".$email->getName().'</info>');
            $progress = new ProgressBar($output, $totalLeadCount);
        }

        foreach ($lists as $list) {
            if (!$batch && $limit !== null && $limit <= 0) {
                // Hit the max for this batch
                break;
            }

            $options['listId'] = $list->getId();
            $leads             = $this->getPendingLeads($email, $list->getId(), false, $limit);
            $leadCount         = count($leads);

            while ($leadCount) {
                $sentCount += $leadCount;

                if (!$batch && $limit != null) {
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

                if ($batch) {
                    if ($progress) {
                        $progressCounter += $leadCount;
                        $progress->setProgress($progressCounter);
                    }

                    // Get the next batch of leads
                    $leads     = $this->getPendingLeads($email, $list->getId(), false, $limit);
                    $leadCount = count($leads);
                } else {
                    $leadCount = 0;
                }
            }
        }

        if ($progress) {
            $progress->finish();
        }

        return [$sentCount, $failedCount, $failed];
    }

    /**
     * Gets template, stats, weights, etc for an email in preparation to be sent.
     *
     * @param Email $email
     * @param bool  $includeVariants
     *
     * @return array
     */
    public function &getEmailSettings(Email $email, $includeVariants = true)
    {
        if (empty($this->emailSettings[$email->getId()])) {
            //used to house slots so they don't have to be fetched over and over for same template
            // BC for Mautic v1 templates
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
                //get a list of variants for A/B testing
                $childrenVariant = $email->getVariantChildren();

                if (count($childrenVariant)) {
                    $variantWeight = 0;
                    $totalSent     = $emailSettings[$email->getId()]['variantCount'];

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

                    //set parent weight
                    $emailSettings[$email->getId()]['weight'] = ((100 - $variantWeight) / 100);
                } else {
                    $emailSettings[$email->getId()]['weight'] = 1;
                }
            }

            $this->emailSettings[$email->getId()] = $emailSettings;
        }

        if ($includeVariants && $email->isVariant()) {
            //now find what percentage of current leads should receive the variants
            if (!isset($totalSent)) {
                $totalSent = 0;
                foreach ($this->emailSettings[$email->getId()] as $eid => $details) {
                    $totalSent += $details['variantCount'];
                }
            }

            foreach ($this->emailSettings[$email->getId()] as $eid => &$details) {
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
            uasort($this->emailSettings[$email->getId()], function ($a, $b) {
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
     * @param   $email
     * @param   $leads
     * @param   $options = array()
     *                   array source array('model', 'id')
     *                   array emailSettings
     *                   int   listId
     *                   bool  allowResends     If false, exact emails (by id) already sent to the lead will not be resent
     *                   bool  ignoreDNC        If true, emails listed in the do not contact table will still get the email
     *                   bool  sendBatchMail    If false, the function will not send batched mail but will defer to calling function to handle it
     *                   array assetAttachments Array of optional Asset IDs to attach
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function sendEmail($email, $leads, $options = [])
    {
        $listId              = (isset($options['listId'])) ? $options['listId'] : null;
        $ignoreDNC           = (isset($options['ignoreDNC'])) ? $options['ignoreDNC'] : false;
        $tokens              = (isset($options['tokens'])) ? $options['tokens'] : [];
        $sendBatchMail       = (isset($options['sendBatchMail'])) ? $options['sendBatchMail'] : true;
        $assetAttachments    = (isset($options['assetAttachments'])) ? $options['assetAttachments'] : [];
        $customHeaders       = (isset($options['customHeaders'])) ? $options['customHeaders'] : [];
        $emailType           = (isset($options['email_type'])) ? $options['email_type'] : '';
        $isMarketing         = (in_array($emailType, ['marketing']) || !empty($listId));
        $emailAttempts       = (isset($options['email_attempts'])) ? $options['email_attempts'] : 3;
        $emailPriority       = (isset($options['email_priority'])) ? $options['email_priority'] : MessageQueue::PRIORITY_NORMAL;
        $messageQueue        = (isset($options['resend_message_queue'])) ? $options['resend_message_queue'] : null;
        $returnErrorMessages = (isset($options['return_errors'])) ? $options['return_errors'] : false;
        $channel             = (isset($options['channel'])) ? $options['channel'] : null;
        $dncAsError          = (isset($options['dnc_as_error'])) ? $options['dnc_as_error'] : false;
        $errors              = [];

        if (empty($channel)) {
            $channel = (isset($options['source'])) ? $options['source'] : null;
        }

        if (!$email->getId()) {
            return false;
        }

        $singleEmail = false;
        if (isset($leads['id'])) {
            $singleEmail = $leads['id'];
            $leads       = [$leads['id'] => $leads];
        }

        /** @var \Mautic\EmailBundle\Entity\StatRepository $statRepo */
        $statRepo = $this->em->getRepository('MauticEmailBundle:Stat');
        /** @var \Mautic\EmailBundle\Entity\EmailRepository $emailRepo */
        $emailRepo = $this->getRepository();

        //get email settings such as templates, weights, etc
        $emailSettings = &$this->getEmailSettings($email);

        $sendTo  = $leads;
        $leadIds = array_keys($sendTo);
        $leadIds = array_combine($leadIds, $leadIds);

        if (!$ignoreDNC) {
            $dnc = $emailRepo->getDoNotEmailList($leadIds);

            if (!empty($dnc)) {
                foreach ($dnc as $removeMeId => $removeMeEmail) {
                    if ($dncAsError) {
                        $errors[$removeMeId] = $this->translator->trans('mautic.email.dnc');
                    }
                    unset($sendTo[$removeMeId]);
                    unset($leadIds[$removeMeId]);
                }
            }
        }

        // Process frequency rules for email
        if ($isMarketing && count($sendTo)) {
            $campaignEventId = (is_array($channel) && 'campaign.event' === $channel[0] && !empty($channel[1])) ? $channel[1] : null;
            $this->messageQueueModel->processFrequencyRules($sendTo, 'email', $email->getId(), $campaignEventId, $emailAttempts, $emailPriority, $messageQueue);
        }

        //get a count of leads
        $count = count($sendTo);

        //no one to send to so bail or if marketing email from a campaign has been put in a queue
        if (empty($count)) {
            if ($returnErrorMessages) {
                return $singleEmail ? $errors[$singleEmail] : $errors;
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

        // Store stat entities
        $saveEntities    = [];
        $deleteEntities  = [];
        $statEntities    = [];
        $statBatchCount  = 0;
        $emailSentCounts = [];
        $badEmails       = [];
        $errorMessages   = [];

        // Setup the mailer
        $mailer = $this->mailHelper->getMailer(!$sendBatchMail);
        $mailer->enableQueue();

        // Flushes the batch in case of using API mailers
        $flushQueue = function ($reset = true) use ($singleEmail, &$mailer, &$statEntities, &$saveEntities, &$deleteEntities, &$errors, &$errorMessages, &$emailSentCounts, $sendBatchMail) {
            if ($sendBatchMail) {
                $flushResult = $mailer->flushQueue();
                if (!$flushResult) {
                    $sendFailures = $mailer->getErrors();

                    // Check to see if failed recipients were stored by the transport
                    if (!empty($sendFailures['failures'])) {
                        $failures = $sendFailures;
                        unset($sendFailures['failures']);
                        $error = implode('; ', $sendFailures);

                        // Prevent the stat from saving
                        foreach ($failures as $failedEmail) {
                            /** @var Stat $stat */
                            $stat = $statEntities[$failedEmail];
                            // Add lead ID to list of failures
                            $errors[$stat->getLead()->getId()]        = $failedEmail;
                            $errorMessages[$stat->getLead()->getId()] = $error;
                            // Down sent counts
                            $emailId = $stat->getEmail()->getId();
                            ++$emailSentCounts[$emailId];

                            if ($stat->getId()) {
                                $deleteEntities[] = $stat;
                            }
                            unset($statEntities[$failedEmail], $saveEntities[$failedEmail]);
                        }
                    } elseif ($singleEmail) {
                        $error                       = implode('; ', $sendFailures);
                        $errorMessages[$singleEmail] = $error;
                    }
                }

                if ($reset) {
                    $mailer->reset(true);
                }

                return $flushResult;
            }

            return true;
        };

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

                // Flush the mail queue if applicable
                $flushQueue();

                $mailer->setSource($channel);
                $emailConfigured = $mailer->setEmail($emailEntity, true, $useSettings['slots'], $assetAttachments);

                if (!empty($customHeaders)) {
                    $mailer->setCustomHeaders($customHeaders);
                }

                foreach ($contacts as $contact) {
                    if (!$emailConfigured) {
                        // There was an error configuring the email so fail these
                        $errors[$contact['id']]        = $contact['email'];
                        $errorMessages[$contact['id']] = $mailer->getErrors(false);
                        continue;
                    }

                    $idHash = uniqid();

                    // Add tracking pixel token
                    if (!empty($tokens)) {
                        $mailer->setTokens($tokens);
                    }

                    $mailer->setLead($contact);
                    $mailer->setIdHash($idHash);

                    try {
                        if (!$mailer->addTo($contact['email'], $contact['firstname'].' '.$contact['lastname'])) {
                            // Clear the errors so it doesn't stop the next send
                            $errorMessages[$contact['id']] = $mailer->getErrors();

                            // Bad email so note and continue
                            $errors[$contact['id']]    = $contact['email'];
                            $badEmails[$contact['id']] = $contact['email'];
                            continue;
                        }
                    } catch (BatchQueueMaxException $e) {
                        // Queue full so flush then try again
                        $flushQueue(false);

                        if (!$mailer->addTo($contact['email'], $contact['firstname'].' '.$contact['lastname'])) {
                            // Clear the errors so it doesn't stop the next send
                            $errorMessages[$contact['id']] = $mailer->getErrors();

                            // Bad email so note and continue
                            $errors[$contact['id']]    = $contact['email'];
                            $badEmails[$contact['id']] = $contact['email'];
                            continue;
                        }
                    }

                    //queue or send the message
                    list($queued, $queueErrors) = $mailer->queue(true, MailHelper::QUEUE_RETURN_ERRORS);
                    if (!$queued) {
                        $errors[$contact['id']] = $contact['email'];
                        unset($queueErrors['failures']);
                        $errorMessages[$contact['id']] = implode('; ', $queueErrors);

                        continue;
                    }

                    //create a stat
                    $saveEntities[$contact['email']] = $statEntities[$contact['email']] = $mailer->createEmailStat(false, null, $listId);
                    ++$statBatchCount;

                    if (20 === $statBatchCount) {
                        // Save in batches of 20 to prevent email loops if the there are issuses with persisting a large number of stats at once
                        $statRepo->saveEntities($saveEntities);
                        $statBatchCount = 0;
                        $saveEntities   = [];
                    }

                    // Up sent counts
                    if (!isset($emailSentCounts[$translatedId])) {
                        $emailSentCounts[$translatedId] = 0;
                    }
                    ++$emailSentCounts[$translatedId];

                    // Update $emailSetting so campaign a/b tests are handled correctly
                    ++$emailSettings[$parentId]['sentCount'];

                    if (!empty($emailSettings[$parentId]['isVariant'])) {
                        ++$emailSettings[$parentId]['variantCount'];
                    }
                }
            }
        }

        // Send batched mail if applicable
        $flushQueue();

        // Persist left over stats
        if (count($saveEntities)) {
            $statRepo->saveEntities($saveEntities);
        }
        if (count($deleteEntities)) {
            $statRepo->deleteEntities($deleteEntities);
        }

        // Update bad emails as bounces
        if (count($badEmails)) {
            foreach ($badEmails as $contactId => $contactEmail) {
                $this->leadModel->addDncForLead(
                    $this->em->getReference('MauticLeadBundle:Lead', $contactId),
                    ['email' => $email->getId()],
                    $this->translator->trans('mautic.email.bounce.reason.bad_email'),
                    DoNotContact::BOUNCED,
                    true,
                    false
                );
            }
        }

        // Update sent counts
        foreach ($emailSentCounts as $emailId => $count) {
            // Retry a few times in case of deadlock errors
            $strikes = 3;
            while ($strikes >= 0) {
                try {
                    $this->getRepository()->upCount($emailId, 'sent', $count, $emailSettings[$emailId]['isVariant']);
                    break;
                } catch (\Exception $exception) {
                    error_log($exception);
                }
                --$strikes;
            }
        }

        // Free RAM
        $this->em->clear('Mautic\EmailBundle\Entity\Stat');
        $this->em->clear('Mautic\LeadBundle\Entity\DoNotContact');

        unset($saveEntities, $saveEntities, $badEmails, $emailSentCounts, $emailSettings, $options, $tokens, $useEmail, $sendTo);

        $success = empty($errors);
        if (!$success && $returnErrorMessages) {
            return $singleEmail ? $errorMessages[$singleEmail] : $errorMessages;
        }

        return $singleEmail ? $success : $errors;
    }

    /**
     * Send an email to lead(s).
     *
     * @param       $email
     * @param       $users
     * @param mixed $lead
     * @param array $tokens
     * @param array $assetAttachments
     * @param bool  $saveStat
     *
     * @return mixed
     *
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
        $emailSettings = &$this->getEmailSettings($email, false);

        //noone to send to so bail
        if (empty($users)) {
            return false;
        }

        $mailer = $this->mailHelper->getMailer();
        $mailer->setLead($lead, true);
        $mailer->setTokens($tokens);
        $mailer->setEmail($email, false, $emailSettings[$emailId]['slots'], $assetAttachments, (!$saveStat));

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

        //flush the message
        if (!$mailer->flushQueue()) {
            $errorArray = $mailer->getErrors();
            unset($errorArray['failures']);
            $errors[] = implode('; ', $errorArray);
        }

        if (isset($saveEntities)) {
            $this->getStatRepository()->saveEntities($saveEntities);
        }

        //save some memory
        unset($mailer);

        return $errors;
    }

    /**
     * @param Stat $stat
     * @param      $comments
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

            return $this->leadModel->addDncForLead($lead, $channel, $comments, $reason, $flush);
        }

        return false;
    }

    /**
     * Remove a Lead's EMAIL DNC entry.
     *
     * @param string $email
     */
    public function removeDoNotContact($email)
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
            $this->leadModel->removeDncForLead($lead, 'email');
        }
    }

    /**
     * @param        $email
     * @param int    $reason
     * @param string $comments
     * @param bool   $flush
     * @param null   $leadId
     *
     * @return array
     */
    public function setEmailDoNotContact($email, $reason = DoNotContact::BOUNCED, $comments = '', $flush = true, $leadId = null)
    {
        /** @var \Mautic\LeadBundle\Entity\LeadRepository $leadRepo */
        $leadRepo = $this->em->getRepository('MauticLeadBundle:Lead');

        if (null === $leadId) {
            $leadId = (array) $leadRepo->getLeadByEmail($email, true);
        } elseif (!is_array($leadId)) {
            $leadId = [$leadId];
        }

        $dnc = [];
        foreach ($leadId as $lead) {
            $dnc[] = $this->leadModel->addDncForLead(
                $this->em->getReference('MauticLeadBundle:Lead', $lead),
                'email',
                $comments,
                $reason,
                $flush
            );
        }

        return $dnc;
    }

    /**
     * Processes the callback response from a mailer for bounces and unsubscribes.
     *
     * @param array $response
     *
     * @return array|void
     */
    public function processMailerCallback(array $response)
    {
        if (empty($response)) {
            return;
        }

        $statRepo = $this->getStatRepository();
        $alias    = $statRepo->getTableAlias();
        if (!empty($alias)) {
            $alias .= '.';
        }

        // Keep track to prevent duplicates before flushing
        $emails = [];
        $dnc    = [];

        foreach ($response as $type => $entries) {
            if (!empty($entries['hashIds'])) {
                $stats = $statRepo->getEntities(
                    [
                        'filter' => [
                            'force' => [
                                [
                                    'column' => $alias.'trackingHash',
                                    'expr'   => 'in',
                                    'value'  => array_keys($entries['hashIds']),
                                ],
                            ],
                        ],
                    ]
                );

                /** @var \Mautic\EmailBundle\Entity\Stat $s */
                foreach ($stats as $s) {
                    $reason = $entries['hashIds'][$s->getTrackingHash()];
                    if ($this->translator->hasId('mautic.email.bounce.reason.'.$reason)) {
                        $reason = $this->translator->trans('mautic.email.bounce.reason.'.$reason);
                    }

                    $dnc[] = $this->setDoNotContact($s, $reason, $type);

                    $s->setIsFailed(true);
                    $this->em->persist($s);
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

                    $dnc = array_merge($dnc, $this->setEmailDoNotContact($email, $type, $reason, true, $leadId));
                }
            }
        }

        return $dnc;
    }

    /**
     * Get the settings for a monitored mailbox or false if not enabled.
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
     * Joins the email table and limits created_by to currently logged in user.
     *
     * @param QueryBuilder $q
     */
    public function limitQueryToCreator(QueryBuilder &$q)
    {
        $q->join('t', MAUTIC_TABLE_PREFIX.'emails', 'e', 'e.id = t.email_id')
            ->andWhere('e.created_by = :userId')
            ->setParameter('userId', $this->userHelper->getUser()->getId());
    }

    /**
     * Get line chart data of emails sent and read.
     *
     * @param char      $unit          {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param string    $dateFormat
     * @param array     $filter
     * @param bool      $canViewOthers
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
                    $q->andWhere($q->expr()->in('cut.channel_id', $filter['email_id']));
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
     * Modifies the line chart query for the DNC.
     *
     * @param ChartQuery $query
     * @param array      $filter
     * @param            $reason
     * @param            $canViewOthers
     *
     * @return array
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
     * Get pie chart data of ignored vs opened emails.
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $filters
     * @param bool   $canViewOthers
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
     * Get pie chart data of ignored vs opened emails.
     *
     * @param   $dateFrom
     * @param   $dateTo
     *
     * @return array
     */
    public function getDeviceGranularityPieChartData($dateFrom, $dateTo)
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
                ($device['device']) ? $device['device'] : $this->translator->trans('mautic.core.unknown'),
                $device['count']
            );
        }

        return $chart->render();
    }

    /**
     * Get a list of emails in a date range, grouped by a stat date count.
     *
     * @param int       $limit
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
                ->setParameter('userId', $this->userHelper->getUser()->getId());
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
     * Get a list of emails in a date range.
     *
     * @param int       $limit
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
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * Get a list of upcoming emails.
     *
     * @param int  $limit
     * @param bool $canViewOthers
     *
     * @return array
     */
    public function getUpcomingEmails($limit = 10, $canViewOthers = true)
    {
        /** @var \Mautic\CampaignBundle\Entity\LeadEventLogRepository $leadEventLogRepository */
        $leadEventLogRepository = $this->em->getRepository('MauticCampaignBundle:LeadEventLog');
        $leadEventLogRepository->setCurrentUser($this->userHelper->getUser());
        $upcomingEmails = $leadEventLogRepository->getUpcomingEvents(
            [
                'type'          => 'email.send',
                'limit'         => $limit,
                'canViewOthers' => $canViewOthers,
            ]
        );

        return $upcomingEmails;
    }

    /**
     * @deprecated 2.1 - use $entity->getVariants() instead; to be removed in 3.0
     *
     * @param Email $entity
     *
     * @return array
     */
    public function getVariants(Email $entity)
    {
        return $entity->getVariants();
    }

    /**
     * @param        $type
     * @param string $filter
     * @param int    $limit
     * @param int    $start
     * @param array  $options
     *
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10, $start = 0, $options = [])
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
                    isset($options['top_level']) ? $options['top_level'] : false,
                    isset($options['email_type']) ? $options['email_type'] : null,
                    isset($options['ignore_ids']) ? $options['ignore_ids'] : [],
                    isset($options['variant_parent']) ? $options['variant_parent'] : null
                );

                foreach ($emails as $email) {
                    $results[$email['language']][$email['id']] = $email['name'];
                }

                //sort by language
                ksort($results);

                break;
        }

        return $results;
    }

    /**
     * @param $sendTo
     */
    private function getContactCompanies(array &$sendTo)
    {
        $fetchCompanies = [];
        foreach ($sendTo as $key => $contact) {
            if (!isset($contact['companies'])) {
                $fetchCompanies[$contact['id']] = $key;
                $sendTo[$key]['companies']      = [];
            }
        }

        if (!empty($fetchCompanies)) {
            $companies = $this->companyModel->getRepository()->getCompaniesForContacts($fetchCompanies); // Simple dbal query that fetches lead_id IN $fetchCompanies and returns as array

            foreach ($companies as $contactId => $contactCompanies) {
                $key                       = $fetchCompanies[$contactId];
                $sendTo[$key]['companies'] = $contactCompanies;
            }
        }
    }

    /**
     * Send an email to lead(s).
     *
     * @param       $email
     * @param       $users
     * @param mixed $leadFields
     * @param array $tokens
     * @param array $assetAttachments
     * @param bool  $saveStat
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function sendSampleEmailToUser($email, $users, $leadFields = null, $tokens = [], $assetAttachments = [], $saveStat = true)
    {
        if (!$emailId = $email->getId()) {
            return false;
        }

        if (!is_array($users)) {
            $user  = ['id' => $users];
            $users = [$user];
        }

        //get email settings
        $emailSettings = &$this->getEmailSettings($email, false);

        //noone to send to so bail
        if (empty($users)) {
            return false;
        }

        $mailer = $this->mailHelper->getSampleMailer();
        $mailer->setLead($leadFields, true);
        $mailer->setTokens($tokens);
        $mailer->setEmail($email, false, $emailSettings[$emailId]['slots'], $assetAttachments, (!$saveStat));

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

        //flush the message
        if (!$mailer->flushQueue()) {
            $errorArray = $mailer->getErrors();
            unset($errorArray['failures']);
            $errors[] = implode('; ', $errorArray);
        }

        if (isset($saveEntities)) {
            $this->getStatRepository()->saveEntities($saveEntities);
        }

        //save some memory
        unset($mailer);

        return $errors;
    }
}
