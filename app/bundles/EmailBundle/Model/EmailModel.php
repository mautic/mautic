<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Model;

use Mautic\CoreBundle\Helper\GraphHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\EmailBundle\Swiftmailer\Exception\BatchQueueMaxException;
use Mautic\EmailBundle\Entity\DoNotEmail;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailEvent;
use Mautic\EmailBundle\Event\EmailOpenEvent;
use Mautic\EmailBundle\EmailEvents;
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
     * {@inheritdoc}
     *
     * @return \Mautic\EmailBundle\Entity\EmailRepository
     */
    public function getRepository ()
    {
        return $this->em->getRepository('MauticEmailBundle:Email');
    }

    /**
     * @return \Mautic\EmailBundle\Entity\StatRepository
     */
    public function getStatRepository ()
    {
        return $this->factory->getEntityManager()->getRepository('MauticEmailBundle:Stat');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase ()
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
    public function saveEntity ($entity, $unlock = true)
    {
        $now = new \DateTime();

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

            //reset the variant hit and start date if there are any changes
            $changes = $entity->getChanges();
            if ($entity->isVariant() && !empty($changes) && empty($this->inConversion)) {
                $entity->setVariantSentCount(0);
                $entity->setVariantStartDate($now);
            }
        }

        parent::saveEntity($entity, $unlock);

        //also reset variants if applicable due to changes
        if (!empty($changes) && empty($this->inConversion)) {
            $parent   = $entity->getVariantParent();
            $children = (!empty($parent)) ? $parent->getVariantChildren() : $entity->getVariantChildren();

            $variants = array();
            if (!empty($parent)) {
                $parent->setVariantSentCount(0);
                $parent->setVariantStartDate($now);
                $variants[] = $parent;
            }

            if (count($children)) {
                foreach ($children as $child) {
                    $child->setVariantSentCount(0);
                    $child->setVariantStartDate($now);
                    $variants[] = $child;
                }
            }

            //if the parent was changed, then that parent/children must also be reset
            if (isset($changes['variantParent'])) {
                $parent = $this->getEntity($changes['variantParent'][0]);
                if (!empty($parent)) {
                    $parent->setVariantSentCount(0);
                    $parent->setVariantStartDate($now);
                    $variants[] = $parent;

                    $children = $parent->getVariantChildren();
                    if (count($children)) {
                        foreach ($children as $child) {
                            $child->setVariantSentCount(0);
                            $child->setVariantStartDate($now);
                            $variants[] = $child;
                        }
                    }
                }
            }

            if (!empty($variants)) {
                $this->saveEntities($variants, false);
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
    public function saveEntities ($entities, $unlock = true)
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
    public function createForm ($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Email) {
            throw new MethodNotAllowedHttpException(array('Email'));
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
    public function getEntity ($id = null)
    {
        if ($id === null) {
            $entity = new Email();
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
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent ($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Email) {
            throw new MethodNotAllowedHttpException(array('Email'));
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
     * @param string|Stat   $stat
     * @param      $request
     * @param bool $viaBrowser
     */
    public function hitEmail ($stat, $request, $viaBrowser = false)
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

        $readDateTime = $this->factory->getDate();
        $stat->setLastOpened($readDateTime->getDateTime());

        $lead = $stat->getLead();
        if ($lead !== null) {
            /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
            $leadModel = $this->factory->getModel('lead');

            /*
            // @todo too many webmail clients mask IP address
            if (!$lead->getIpAddresses()->contains($ipAddress)) {
                $lead->addIpAddress($ipAddress);
                $leadModel->saveEntity($lead, true);
            }
            */

            // Set the lead as current lead
            $leadModel->setCurrentLead($lead);
        }

        if (!$stat->getIsRead()) {
            $stat->setIsRead(true);
            $stat->setDateRead($readDateTime->getDateTime());

            // Only up counts if associated with both an email and lead
            if ($email && $lead) {
                $readCount = $email->getReadCount();
                $readCount++;
                $email->setReadCount($readCount);

                if ($email->isVariant()) {
                    $variantReadCount = $email->getVariantReadCount();
                    $variantReadCount++;
                    $email->setVariantReadCount($variantReadCount);
                }
            }
        }

        if ($viaBrowser) {
            $stat->setViewedInBrowser($viaBrowser);
        }

        $stat->addOpenDetails(array(
            'datetime'  => $readDateTime->toUtcString(),
            'useragent' => $request->server->get('HTTP_USER_AGENT'),
            'inBrowser' => $viaBrowser
        ));

        //check for existing IP
        $ipAddress = $this->factory->getIpAddress();
        $stat->setIpAddress($ipAddress);

        if ($email) {
            if ($this->dispatcher->hasListeners(EmailEvents::EMAIL_ON_OPEN)) {
                $event = new EmailOpenEvent($stat, $request);
                $this->dispatcher->dispatch(EmailEvents::EMAIL_ON_OPEN, $event);
            }
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
    public function getBuilderComponents (Email $email = null, $requestedComponents = 'all', $tokenFilter = null)
    {
        $singleComponent = (!is_array($requestedComponents) && $requestedComponents != 'all');
        $components      = array();
        $event           = new EmailBuilderEvent($this->translator, $email, $requestedComponents, $tokenFilter);
        $this->dispatcher->dispatch(EmailEvents::EMAIL_ON_BUILD, $event);

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
                default:
                    $components['tokens']               = $event->getTokens();
                    $components['tokenSections']        = $event->getTokenSections();
                    $components['abTestWinnerCriteria'] = $event->getAbTestWinnerCriteria();
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
    public function getEmailStatus ($idHash)
    {
        return $this->getStatRepository()->getEmailStatus($idHash);
    }

    /**
     * Get the variant parent/children
     *
     * @param Email $email
     *
     * @return array
     */
    public function getVariants (Email $email)
    {
        $parent = $email->getVariantParent();

        if (!empty($parent)) {
            $children = $parent->getVariantChildren();
        } else {
            $parent   = $email;
            $children = $email->getVariantChildren();
        }

        if (empty($children)) {
            $children = array();
        }

        return array($parent, $children);
    }


    /**
     * Converts a variant to the main page and the main page a variant
     *
     * @param Email $email
     */
    public function convertVariant (Email $email)
    {
        //let saveEntities() know it does not need to set variant start dates
        $this->inConversion = true;

        list($parent, $children) = $this->getVariants($email);

        $save = array();

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
    public function getEmailListStats ($email, $includeVariants = false)
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
            $emailIds = array($email->getId());
        }

        $lists     = $email->getLists();
        $listCount = count($lists);

        $combined = $this->translator->trans('mautic.email.lists.combined');
        $datasets = array(
            $combined => array(0, 0, 0)
        );

        $labels = array(
            $this->translator->trans('mautic.email.sent'),
            $this->translator->trans('mautic.email.read'),
            $this->translator->trans('mautic.email.failed')
        );

        if ($listCount) {
            /** @var \Mautic\EmailBundle\Entity\StatRepository $statRepo */
            $statRepo = $this->em->getRepository('MauticEmailBundle:Stat');

            foreach ($lists as $l) {
                $name = $l->getName();

                $sentCount = $statRepo->getSentCount($emailIds, $l->getId());
                $datasets[$combined][0] += $sentCount;

                $readCount = $statRepo->getReadCount($emailIds, $l->getId());
                $datasets[$combined][1] += $readCount;

                $failedCount = $statRepo->getFailedCount($emailIds, $l->getId());
                $datasets[$combined][2] += $failedCount;

                $datasets[$name] = array();

                $datasets[$name] = array(
                    $sentCount,
                    $readCount,
                    $failedCount
                );

                $datasets[$name]['datasetKey'] = $l->getId();
            }
        }

        if ($listCount === 1) {
            unset($datasets[$combined]);
        }

        $data = GraphHelper::prepareBarGraphData($labels, $datasets);

        return $data;
    }

    /**
     * @param int|Email $email
     * @param bool      $includeVariants
     * @param int       $amount
     * @param string    $unit
     *
     * @return array
     */
    public function getEmailGeneralStats ($email, $includeVariants = false, $amount = 30, $unit = 'D')
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
            $emailIds = array($email->getId());
        }

        /** @var \Mautic\EmailBundle\Entity\StatRepository $statRepo */
        $statRepo = $this->em->getRepository('MauticEmailBundle:Stat');

        $graphData = GraphHelper::prepareDatetimeLineGraphData($amount, $unit,
            array(
                $this->translator->trans('mautic.email.stat.sent'),
                $this->translator->trans('mautic.email.stat.read'),
                $this->translator->trans('mautic.email.stat.failed')
            )
        );
        $fromDate = $graphData['fromDate'];

        $sentData  = $statRepo->getEmailStats($emailIds, $fromDate, 'sent');
        $graphData = GraphHelper::mergeLineGraphData($graphData, $sentData, $unit, 0, 'date', 'data');

        $readData  = $statRepo->getEmailStats($emailIds, $fromDate, 'read');
        $graphData = GraphHelper::mergeLineGraphData($graphData, $readData, $unit, 1, 'date', 'data');

        $failedData  = $statRepo->getEmailStats($emailIds, $fromDate, 'failed');
        $graphData = GraphHelper::mergeLineGraphData($graphData, $failedData, $unit, 2, 'date', 'data');

        return $graphData;
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
        return $this->factory->getModel('page.redirect')->getRedirectListBySource('email', $emailId);
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
     * @return array array(int $sentCount, int $failedCount, array $failedRecipientsByList)
     */
    public function sendEmailToLists (Email $email, $lists = null, $limit = null)
    {
        //get the leads
        if (empty($lists)) {
            $lists = $email->getLists();
        }

        //get email settings such as templates, weights, etc
        $emailSettings = $this->getEmailSettings($email);
        $options       = array(
            'source'        => array('email', $email->getId()),
            'emailSettings' => $emailSettings,
            'allowResends'  => false,
            'customHeaders' => array(
                'Precedence' => 'Bulk'
            )
        );

        $failed      = array();
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
            $sentCount        += $leadCount;

            if ($limit != null) {
                // Only retrieve the difference between what has already been sent and the limit
                $limit -= $leadCount;
            }

            $listErrors = $this->sendEmail($email, $leads, $options);

            if (!empty($listErrors)) {
                $listFailedCount = count($listErrors);

                $sentCount   -= $listFailedCount;
                $failedCount += $listFailedCount;

                $failed[$options['listId']] = $listErrors;
            }
        }

        return array($sentCount, $failedCount, $failed);
    }

    /**
     * Gets template, stats, weights, etc for an email in preparation to be sent
     *
     * @param Email $email
     * @param bool  $includeVariants
     *
     * @return array
     */
    public function getEmailSettings (Email $email, $includeVariants = true)
    {
        static $emailSettings = array();

        if (empty($emailSettings[$email->getId()])) {

            //used to house slots so they don't have to be fetched over and over for same template
            $slots            = array();
            $template         = $email->getTemplate();
            $slots[$template] = $this->factory->getTheme($template)->getSlots('email');

            //store the settings of all the variants in order to properly disperse the emails
            //set the parent's settings
            $emailSettings = array(
                $email->getId() => array(
                    'template'     => $email->getTemplate(),
                    'slots'        => $slots,
                    'sentCount'    => $email->getSentCount(),
                    'variantCount' => $email->getVariantSentCount(),
                    'entity'       => $email
                )
            );

            if ($includeVariants) {
                //get a list of variants for A/B testing
                $childrenVariant = $email->getVariantChildren();

                if (count($childrenVariant)) {
                    $variantWeight = 0;
                    $totalSent     = $email->getVariantSentCount();

                    foreach ($childrenVariant as $id => $child) {
                        if ($child->isPublished()) {
                            $template = $child->getTemplate();
                            if (isset($slots[$template])) {
                                $useSlots = $slots[$template];
                            } else {
                                $slots[$template] = $this->factory->getTheme($template)->getSlots('email');
                                $useSlots         = $slots[$template];
                            }
                            $variantSettings = $child->getVariantSettings();

                            $emailSettings[$child->getId()] = array(
                                'template'     => $child->getTemplate(),
                                'slots'        => $useSlots,
                                'sentCount'    => $child->getSentCount(),
                                'variantCount' => $child->getVariantSentCount(),
                                'weight'       => ($variantSettings['weight'] / 100),
                                'entity'       => $child
                            );

                            $variantWeight += $variantSettings['weight'];
                            $totalSent += $emailSettings[$child->getId()]['sentCount'];
                        }
                    }

                    //set parent weight
                    $emailSettings[$email->getId()]['weight'] = ((100 - $variantWeight) / 100);

                    //now find what percentage of current leads should receive the variants
                    foreach ($emailSettings as $eid => &$details) {
                        $details['weight'] = ($totalSent) ?
                            ($details['weight'] - ($details['variantCount'] / $totalSent)) + $details['weight'] :
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
     *     array source array('model', 'id')
     *     array emailSettings
     *     int   listId
     *     bool  allowResends     If false, exact emails (by id) already sent to the lead will not be resent
     *     bool  ignoreDNC        If true, emails listed in the do not contact table will still get the email
     *     bool  sendBatchMail    If false, the function will not send batched mail but will defer to calling function to handle it
     *     array assetAttachments Array of optional Asset IDs to attach
     *
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     */
    public function sendEmail ($email, $leads, $options = array())
    {
        $source           = (isset($options['source'])) ? $options['source'] : null;
        $emailSettings    = (isset($options['emailSettings'])) ? $options['emailSettings'] : array();
        $listId           = (isset($options['listId'])) ? $options['listId'] : null;
        $ignoreDNC        = (isset($options['ignoreDNC'])) ? $options['ignoreDNC'] : false;
        $allowResends     = (isset($options['allowResends'])) ? $options['allowResends'] : true;
        $tokens           = (isset($options['tokens'])) ? $options['tokens'] : array();
        $sendBatchMail    = (isset($options['sendBatchMail'])) ? $options['sendBatchMail'] : true;
        $assetAttachments = (isset($options['assetAttachments'])) ? $options['assetAttachments'] : array();
        $customHeaders    = (isset($options['customHeaders'])) ? $options['customHeaders'] : array();

        if (!$email->getId()) {
            return false;
        }

        $singleEmail = false;
        if (isset($leads['id'])) {
            $singleEmail = true;
            $leads = array($leads['id'] => $leads);
        }

        /** @var \Mautic\EmailBundle\Entity\StatRepository $statRepo */
        $statRepo  = $this->em->getRepository('MauticEmailBundle:Stat');
        /** @var \Mautic\EmailBundle\Entity\EmailRepository $emailRepo */
        $emailRepo = $this->getRepository();

        if (empty($emailSettings)) {
            //get email settings such as templates, weights, etc
            $emailSettings = $this->getEmailSettings($email);
        }

        if (!$allowResends) {
            static $sent = array();
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
            return $singleEmail ? true : array();
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
        $errors   = array();
        // Store stat entities
        $saveEntities = array();

        $mailer = $this->factory->getMailer(!$sendBatchMail);

        $contentGenerated = false;

        $flushQueue = function($reset = true) use (&$mailer, &$saveEntities, &$errors, $sendBatchMail) {

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
                            $saveEntities[$failedEmail]->getEmail()->downSentCounts();

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
                if (!$mailer->addTo($lead['email'], $lead['firstname'] . ' ' . $lead['lastname'])) {
                    // Clear the errors so it doesn't stop the next send
                    $mailer->clearErrors();

                    // Bad email so note and continue
                    $errors[$lead['id']] = $lead['email'];

                    continue;
                }
            } catch (BatchQueueMaxException $e) {
                // Queue full so flush then try again
                $flushQueue(false);

                $mailer->addTo($lead['email'], $lead['firstname'] . ' ' . $lead['lastname']);
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
            $stat = new Stat();
            $stat->setDateSent(new \DateTime());

            $stat->setEmail($useEmail['entity']);
            $stat->setLead($this->em->getReference('MauticLeadBundle:Lead', $lead['id']));
            if ($listId) {
                $stat->setList($this->em->getReference('MauticLeadBundle:LeadList', $listId));
            }
            $stat->setEmailAddress($lead['email']);
            $stat->setTrackingHash($idHash);
            if (!empty($source)) {
                $stat->setSource($source[0]);
                $stat->setSourceId($source[1]);
            }
            $stat->setCopy($mailer->getBody());
            $stat->setTokens($mailer->getTokens());

            $saveEntities[$lead['email']] = $stat;

            // Down sent counts
            $saveEntities[$lead['email']]->getEmail()->upSentCounts();

            // Save RAM
            unset($stat);

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

        // Free RAM
        foreach ($saveEntities as $stat) {
            $this->em->detach($stat);
            unset($stat);
        }

        unset($emailSettings, $options, $tokens, $useEmail, $sendTo);

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
    public function sendEmailToUser ($email, $users, $lead = null, $tokens = array(), $assetAttachments = array(), $saveStat = true)
    {
        if (!$emailId = $email->getId()) {
            return false;
        }

        if (!is_array($users)) {
            $user  = array('id' => $users);
            $users = array($user);
        }

        //get email settings
        $emailSettings = $this->getEmailSettings($email, false);

        //noone to send to so bail
        if (empty($users)) {
            return false;
        }

        $mailer = $this->factory->getMailer();
        $mailer->setLead($lead, true);
        $mailer->setTokens($tokens);
        $mailer->setEmail($email, false, $emailSettings[$emailId]['slots'], $assetAttachments, (!$saveStat));

        $mailer->useMailerTokenization();

        foreach ($users as $user) {
            $idHash = uniqid();
            $mailer->setIdHash($idHash);

            if (!is_array($user)) {
                $id   = $user;
                $user = array('id' => $id);
            } else {
                $id = $user['id'];
            }

            if (!isset($user['email'])) {
                /** @var \Mautic\UserBundle\Model\UserModel $model */
                $userModel         = $this->factory->getModel('user');
                $userEntity        = $userModel->getEntity($id);
                $user['email']     = $userEntity->getEmail();
                $user['firstname'] = $userEntity->getFirstName();
                $user['lastname']  = $userEntity->getLastName();
            }

            $mailer->setTo($user['email'], $user['firstname'] . ' ' . $user['lastname']);

            $mailer->queue(true);

            if ($saveStat) {
                //create a stat
                $stat = new Stat();
                $stat->setDateSent(new \DateTime());
                $stat->setEmailAddress($user['email']);
                $stat->setTrackingHash($idHash);
                if (!empty($source)) {
                    $stat->setSource($source[0]);
                    $stat->setSourceId($source[1]);
                }
                $stat->setCopy($mailer->getBody());
                $stat->setTokens($mailer->getTokens());

                $saveEntities[] = $stat;
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
     * @param        $reason
     * @param string $tag
     * @param bool   $flush
     */
    public function setDoNotContact (Stat $stat, $reason, $tag = 'bounced', $flush = true)
    {
        $lead    = $stat->getLead();
        $email   = $stat->getEmail();
        $address = $stat->getEmailAddress();

        $repo = $this->getRepository();
        if (!$repo->checkDoNotEmail($address)) {
            $dnc = new DoNotEmail();
            if ($email != null) {
                $dnc->setEmail($email);
            }
            $dnc->setLead($lead);
            $dnc->setEmailAddress($address);
            $dnc->setDateAdded(new \DateTime());
            $dnc->{"set" . ucfirst($tag)}();
            $dnc->setComments($reason);

            $em = $this->factory->getEntityManager();
            $em->persist($dnc);

            if ($flush) {
                $em->flush();
            }
        }
    }

    /**
     * @param        $email
     * @param string $reason
     * @param bool   $flush
     */
    public function setBounceDoNotContact($email, $reason = '', $flush = true)
    {
        $repo = $this->getRepository();

        if (!$repo->checkDoNotEmail($email)) {
            $dnc = new DoNotEmail();
            $dnc->setEmailAddress($email);
            $dnc->setDateAdded(new \DateTime());
            $dnc->setBounced();
            $dnc->setComments($reason);

            $em = $this->factory->getEntityManager();
            $em->persist($dnc);

            if ($flush) {
                $em->flush();
            }
        }
    }

    /**
     * Remove email from DNC list
     *
     * @param $email
     */
    public function removeDoNotContact ($email)
    {
        $this->getRepository()->removeFromDoNotEmailList($email);
    }

    /**
     * @param array $bounces
     */
    public function updateBouncedStats(array $bounces)
    {
        if (empty($bounces)) {
            return;
        }

        $batch = 20;
        $count = 0;

        if (!empty($bounces['hashIds'])) {
            $statRepo = $this->getStatRepository();

            $alias = $statRepo->getTableAlias();
            if (!empty($alias)) {
                $alias .= '.';
            }

            $stats = $this->getStatRepository()->getEntities(
                array(
                    'filter' => array(
                        'force' => array(
                            array(
                                'column' => $alias.'trackingHash',
                                'expr'   => 'in',
                                'value'  => array_keys($bounces['hashIds'])
                            )
                        )
                    )
                )
            );

            /** @var \Mautic\EmailBundle\Entity\Stat $s */
            foreach ($stats as $s) {
                $this->setDoNotContact($s, $bounces['hashIds'][$s->getTrackingHash()], 'bounced', ($count === $batch));

                $s->setIsFailed(true);
                $this->em->persist($s);

                if ($count === $batch) {
                    $count = 0;
                }
            }
        }


        if (!empty($bounces['emails'])) {
            foreach ($bounces['emails'] as $email => $reason) {
                $this->setBounceDoNotContact($email, $reason, ($count === $batch));

                if ($count === $batch) {
                    $count = 0;
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
        /** @var \Mautic\EmailBundle\MonitoredEmail\Mailbox $mailboxHelper */
        $mailboxHelper = $this->factory->getHelper('mailbox');

        if ($mailboxHelper->isConfigured($bundleKey, $folderKey)) {

            return $mailboxHelper->getMailboxSettings();
        }

        return false;
    }
}