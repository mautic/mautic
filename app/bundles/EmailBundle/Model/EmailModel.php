<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Model;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\GraphHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\EmailBundle\Entity\DoNotEmail;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailEvent;
use Mautic\EmailBundle\Event\EmailOpenEvent;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
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
     * {@inheritdoc}
     */
    public function getPermissionBase ()
    {
        return 'email:emails';
    }

    /**
     * {@inheritdoc}
     */
    public function getNameGetter ()
    {
        return "getSubject";
    }

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $unlock
     *
     * @return mixed
     */
    public function saveEntity ($entity, $unlock = true)
    {
        $now = new \DateTime();

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
     * @return null|object
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
    protected function dispatchEvent ($action, &$entity, $isNew = false, $event = false)
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
                return false;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new EmailEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return false;
        }
    }

    /**
     * Get list of entities for autopopulate fields
     *
     * @param $type
     * @param $filter
     * @param $limit
     *
     * @return array
     */
    public function getLookupResults ($type, $filter = '', $limit = 10)
    {
        $results = array();
        switch ($type) {
            case 'email':
                $viewOther = $this->security->isGranted('email:emails:viewother');
                $repo      = $this->getRepository();
                $repo->setCurrentUser($this->factory->getUser());
                $results = $repo->getEmailList($filter, $limit, 0, $viewOther);
                break;
        }

        return $results;
    }

    /**
     * @param      $trackingHash
     * @param      $request
     * @param bool $viaBrowser
     */
    public function hitEmail ($trackingHash, $request, $viaBrowser = false)
    {
        $stat = $this->getEmailStatus($trackingHash);

        if (!$stat) {
            return;
        }

        $email = $stat->getEmail();

        if (!empty($stat) && (int)$stat->isRead()) {
            if ($viaBrowser && !$stat->getViewedInBrowser()) {
                //opened via browser so note it
                $stat->setViewedInBrowser($viaBrowser);
                $this->em->persist($stat);
                $this->em->flush();
            }

            return;
        }

        $stat->setIsRead(true);
        $stat->setDateRead(new \Datetime());
        $stat->setViewedInBrowser($viaBrowser);

        $readCount = $email->getReadCount();
        $readCount++;
        $email->setReadCount($readCount);

        if ($email->isVariant()) {
            $variantReadCount = $email->getVariantReadCount();
            $variantReadCount++;
            $email->setVariantReadCount($variantReadCount);
        }

        $this->em->persist($email);

        //check for existing IP
        $ipAddress = $this->factory->getIpAddress();
        $stat->setIpAddress($ipAddress);

        //save the IP to the lead
        $lead = $stat->getLead();

        if ($lead !== null) {
            /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
            $leadModel = $this->factory->getModel('lead');

            if (!$lead->getIpAddresses()->contains($ipAddress)) {
                $lead->addIpAddress($ipAddress);
                $leadModel->saveEntity($lead, true);
            }

            // Set the lead as current lead
            $leadModel->setCurrentLead($lead);
        }

        if ($this->dispatcher->hasListeners(EmailEvents::EMAIL_ON_OPEN)) {
            $event = new EmailOpenEvent($stat, $request);
            $this->dispatcher->dispatch(EmailEvents::EMAIL_ON_OPEN, $event);
        }

        $this->em->persist($stat);
        $this->em->flush();
    }


    /**
     * Get array of email builder tokens from bundles subscribed EmailEvents::EMAIL_ON_BUILD
     *
     * @param null|Email  $email
     * @param null|string $component null | tokens | abTestWinnerCriteria
     *
     * @return mixed
     */
    public function getBuilderComponents (Email $email = null, $component = null)
    {
        static $components;

        if (empty($components)) {
            $components = array();
            $event      = new EmailBuilderEvent($this->translator, $email);
            $this->dispatcher->dispatch(EmailEvents::EMAIL_ON_BUILD, $event);
            $components['tokens']               = $event->getTokenSections();
            $components['abTestWinnerCriteria'] = $event->getAbTestWinnerCriteria();
        }

        return ($component !== null && isset($components[$component])) ? $components[$component] : $components;
    }

    /**
     * @param $idHash
     *
     * @return mixed
     */
    public function getEmailStatus ($idHash)
    {
        return $this->factory->getEntityManager()->getRepository('MauticEmailBundle:Stat')->getEmailStatus($idHash);
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
     * @param Email $entity
     *
     * @return array
     */
    public function getEmailListStats (Email $entity)
    {
        $lists     = $entity->getLists();
        $listCount = count($lists);

        $combined = $this->translator->trans('mautic.email.lists.combined');
        $datasets = array(
            $combined => array(0, 0, 0, 0)
        );

        $labels = array(
            $this->translator->trans('mautic.email.total'),
            $this->translator->trans('mautic.email.sent'),
            $this->translator->trans('mautic.email.read'),
            $this->translator->trans('mautic.email.failed')
        );

        if ($listCount) {
            $listRepo = $this->em->getRepository('MauticLeadBundle:LeadList');
            $statRepo = $this->em->getRepository('MauticEmailBundle:Stat');

            foreach ($lists as $l) {
                $name = $l->getName();

                $recipientCount = $listRepo->getLeadCount($l->getId());
                $datasets[$combined][0] += $recipientCount;

                $sentCount = $statRepo->getSentCount($entity->getId(), $l->getId());
                $datasets[$combined][1] += $sentCount;

                $readCount = $statRepo->getReadCount($entity->getId(), $l->getId());
                $datasets[$combined][2] += $readCount;

                $failedCount = $statRepo->getFailedCount($entity->getId(), $l->getId());
                $datasets[$combined][3] += $failedCount;

                $datasets[$name] = array();

                $datasets[$name] = array(
                    $recipientCount,
                    $sentCount,
                    $readCount,
                    $failedCount
                );
            }
        }

        if ($listCount === 1) {
            unset($datasets[$combined]);
        }

        $data = GraphHelper::prepareBarGraphData($labels, $datasets);

        return $data;
    }

    /**
     * Get the number of leads this email will be sent to
     *
     * @param Email $email
     * @param mixed $listId     Leads for a specific lead list
     * @param bool  $countOnly  If true, return count otherwise array of leads
     *
     * @return int
     */
    public function getPendingLeads(Email $email, $listId = null, $countOnly = false)
    {
        $total = $this->getRepository()->getEmailPendingLeads($email->getId(), $listId, $countOnly);

        return $total;
    }

    /**
     * Send an email to lead lists
     *
     * @param Email $email
     * @param array $lists
     */
    public function sendEmailToLists (Email $email, $lists = null)
    {
        //get the leads
        if (empty($lists)) {
            $lists = $email->getLists();
        }

        //get email settings such as templates, weights, etc
        $emailSettings = $this->getEmailSettings($email);
        $saveEntities  = array();
        $options       = array(
            'source'          => array('email', $email->getId()),
            'emailSettings'   => $emailSettings,
            'returnEntities'  => true,
            'allowResends'    => false
        );
        foreach ($lists as $list) {
            $options['listId'] = $list->getId();
            $leads             = $this->getPendingLeads($email, $list->getId());
            $listSaveEntities  = $this->sendEmail($email, $leads, $options);
            if (!empty($listSaveEntities)) {
                $saveEntities = array_merge($saveEntities, $listSaveEntities);
            }
        }

        //set the sent count and save
        foreach ($emailSettings as $e) {
            $saveEntities[] = $e['entity'];
        }

        if (!empty($saveEntities)) {
            //save the stats and emails
            $this->saveEntities($saveEntities);
        }
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
                    'slots'        => $this->factory->getTheme($email->getTemplate())->getSlots('email'),
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
                                $slots[$template] = $this->factory->getTheme($template())->getSlots('email');
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
     *     bool  returnEntities  If true, entitites will be returned rather than persisted
     *     bool  allowResends    If false, exact emails (by id) already sent to the lead will not be resent
     *     bool  ignoreDNC       If true, emails listed in the do not contact table will still get the email
     *
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     */
    public function sendEmail ($email, $leads, $options = array())
    {
        $source           = (isset($options['source'])) ? $options['source'] : null;
        $emailSettings    = (isset($options['emailSettings'])) ? $options['emailSettings'] : array();
        $listId           = (isset($options['listId'])) ? $options['listId'] : null;
        $returnEntities   = (isset($options['returnEntities'])) ? $options['returnEntities'] : false;
        $ignoreDNC        = (isset($options['ignoreDNC'])) ? $options['ignoreDNC'] : false;
        $allowResends     = (isset($options['allowResends'])) ? $options['allowResends'] : true;
        $tokens           = (isset($options['tokens'])) ? $options['tokens'] : array();

        if (!$email->getId()) {
            return ($returnEntities) ? array() : false;
        }

        if (isset($leads['id'])) {
            $leads = array($leads['id'] => $leads);
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
            static $sent = array();
            if (!isset($sent[$email->getId()])) {
                $sent[$email->getId()] = $statRepo->getSentStats($email->getId(), $listId);
            }
            $sendTo = array_diff_key($leads, $sent[$email->getId()]);
        } else {
            $sendTo = $leads;
        }

        if ($ignoreDNC) {
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
            return true;
        }

        //how many of this batch should go to which email
        $batchCount = 0;

        foreach ($emailSettings as $eid => &$details) {
            if (isset($details['weight'])) {
                $details['limit'] = round($count * $details['weight']);
            } else {
                $details['limit'] = $count;
            }
        }

        //randomize the leads for statistic purposes
        shuffle($sendTo);

        //start at the beginning for this batch
        $useEmail     = reset($emailSettings);
        $saveEntities = $saveEmails = array();

        $errors = array();

        $mailer = $this->factory->getMailer();

        foreach ($sendTo as $lead) {
            $idHash = uniqid();

            $mailer->setLead($lead);
            $mailer->setIdHash($idHash);
            $mailer->setSource($source);
            $mailer->setEmail($useEmail['entity']);
            $mailer->setCustomTokens($tokens);

            if ($useEmail['entity']->getContentMode() == 'builder') {
                $mailer->setTemplate('MauticEmailBundle::public.html.php', array(
                    'slots'    => $useEmail['slots'],
                    'content'  => $useEmail['entity']->getContent(),
                    'email'    => $useEmail['entity'],
                    'template' => $useEmail['template'],
                    'idHash'   => $idHash
                ));
            } else {
                // Tak on the tracking URL
                $customHtml  = $useEmail['entity']->getCustomHtml();
                $trackingImg = '<img height="1" width="1" src="' . $this->factory->getRouter()->generate('mautic_email_tracker', array('idHash' => $idHash), true) . '" />';
                if (strpos($customHtml, '</body>') !== false) {
                    $customHtml = str_replace('</body>', $trackingImg . '</body>', $customHtml);
                } else {
                    $customHtml .= $trackingImg;
                }

                $mailer->setBody($customHtml);
            }

            $mailer->setTo(array($lead['email'] => $lead['firstname'] . ' ' . $lead['lastname']));
            $mailer->setSubject($useEmail['entity']->getSubject());

            if ($plaintext = $useEmail['entity']->getPlainText()) {
                $mailer->setPlainText($plaintext);
            }

            //queue the message
            if (!$mailer->send(true)) {
                $errors[$lead['id']] = true;

                $mailer->reset();

                continue;
            }

            $mailer->reset();

            if (!$allowResends) {
                $sent[$email->getId()][$lead['id']] = $lead['id'];
            }

            //create a stat
            $stat = new Stat();
            $stat->setDateSent(new \DateTime());
            //use reference to set the email to avoid persist errors
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
            //Set custom tokens specific to this email
            $stat->setTokens($tokens);
            $saveEntities[] = $stat;

            unset($stat);

            //increase the sent counts
            $useEmail['entity']->upSentCounts();

            if (!isset($saveEmails[$useEmail['entity']->getId()])) {
                $saveEmails[$useEmail['entity']->getId()] = $useEmail['entity'];
            }

            $batchCount++;
            if ($batchCount > $useEmail['limit']) {
                unset($useEmail);

                //use the next email
                $batchCount = 0;
                $useEmail   = next($emailSettings);
            }
        }

        if ($returnEntities) {

            return $saveEntities;
        } else {
            $this->getRepository()->saveEntities($saveEntities);

            if (!empty($saveEmails)) {
                $this->getRepository()->saveEntities($saveEmails);
                unset($saveEmails);
            }

            foreach ($saveEntities as $stat) {
                // Save RAM
                $this->em->detach($stat);
            }

            unset($emailSettings, $options, $tokens, $saveEntities, $useEmail, $sendTo);

            return (count($leads) > 1) ? $errors : (empty($error));
        }
    }

    /**
     * Send an email to lead(s)
     *
     * @param       $email
     * @param       $users
     * @param mixed $lead
     * @param array $tokens
     *
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     */
    public function sendEmailToUser ($email, $users, $lead = null, $tokens = array())
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

        foreach ($users as $user) {
            $idHash = uniqid();

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

            $mailer = $this->factory->getMailer();
            $mailer->setLead($lead);
            $mailer->setCustomTokens($tokens);

            if ($email->getContentMode() == 'builder') {
                $mailer->setTemplate('MauticEmailBundle::public.html.php', array(
                    'slots'    => $emailSettings[$emailId]['slots'],
                    'content'  => $email->getContent(),
                    'email'    => $email,
                    'template' => $emailSettings[$emailId]['template'],
                    'idHash'   => $idHash
                ));
            } else {
                $mailer->setBody($email->getCustomHtml());
            }

            $mailer->setTo(array($user['email'] => $user['firstname'] . ' ' . $user['lastname']));
            $mailer->setSubject($email->getSubject());

            if ($plaintext = $email->getPlainText()) {
                $mailer->setPlainText($plaintext);
            }

            //queue the message
            $mailer->send(true);

            //save some memory
            unset($mailer);
        }
    }

    /**
     * @param Stat   $stat
     * @param        $reason
     * @param string $tag
     */
    public function setDoNotContact (Stat $stat, $reason, $tag = 'bounced')
    {
        $lead    = $stat->getLead();
        $email   = $stat->getEmail();
        $address = $stat->getEmailAddress();

        $repo = $this->getRepository();
        if (!$repo->checkDoNotEmail($address)) {
            $dnc = new DoNotEmail();
            $dnc->setEmail($email);
            $dnc->setLead($lead);
            $dnc->setEmailAddress($address);
            $dnc->setDateAdded(new \DateTime());
            $dnc->{"set" . ucfirst($tag)}();
            $dnc->setComments($reason);

            $em = $this->factory->getEntityManager();
            $em->persist($dnc);

            $em->flush();
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
}