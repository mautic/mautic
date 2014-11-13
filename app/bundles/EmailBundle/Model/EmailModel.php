<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Model;

use Mautic\CoreBundle\Entity\IpAddress;
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

    public function getRepository()
    {
        return $this->em->getRepository('MauticEmailBundle:Email');
    }

    public function getPermissionBase()
    {
        return 'email:emails';
    }

    public function getNameGetter()
    {
        return "getSubject";
    }

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $unlock
     * @return mixed
     */
    public function saveEntity($entity, $unlock = true)
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
     * {@inheritdoc}
     *
     * @param      $entity
     * @param      $formFactory
     * @param null $action
     * @param array $options
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Email) {
            throw new MethodNotAllowedHttpException(array('Email'));
        }
        $params = (!empty($action)) ? array('action' => $action) : array();
        return $formFactory->create('emailform', $entity, $params);
    }

    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     * @return null|object
     */
    public function getEntity($id = null)
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
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, $event = false)
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
    public function getLookupResults($type, $filter = '', $limit = 10)
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
     * @param      $email
     * @param      $trackingHash
     * @param      $request
     * @param bool $viaBrowser
     */
    public function hitEmail($email, $trackingHash, $request, $viaBrowser = false)
    {
        $stat = $this->getEmailStatus($trackingHash);

        if (!empty($stat) && (int) $stat->isRead()) {
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
            $leadModel = $this->factory->getModel('lead');
            if (!$lead->getIpAddresses()->contains($ipAddress)) {
                $lead->addIpAddress($ipAddress);
                $leadModel->saveEntity($lead, true);
            }

            $leadModel->setLeadCookie($lead->getId());
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
    public function getBuilderComponents(Email $email = null, $component = null)
    {
        static $components;

        if (empty($components)) {
            $components = array();
            $event      = new EmailBuilderEvent($this->translator, $email);
            $this->dispatcher->dispatch(EmailEvents::EMAIL_ON_BUILD, $event);
            $components['tokens'] = $event->getTokenSections();
            $components['abTestWinnerCriteria'] = $event->getAbTestWinnerCriteria();
        }

        return ($component !== null && isset($components[$component])) ? $components[$component] : $components;
    }

    /**
     * @param $idHash
     *
     * @return mixed
     */
    public function getEmailStatus($idHash)
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
            $children = array();
        }

        return array($parent, $children);
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
    public function getEmailListStats(Email $entity)
    {
        $lists  = $entity->getLists();
        $combinedLabel = $this->translator->trans('mautic.email.lists.combined');
        $combinedColor = '#' . substr(md5($combinedLabel), 0, 6);
        $datasets = array(
            array(
                'data' => array(0, 0, 0, 0),
                'label' => $combinedLabel,
                'fillColor' => $this->adjustColor($combinedColor, -30),
                'highlightFill' => $combinedColor,
                'highlightStroke' => $this->adjustColor($combinedColor, 10),
                'strokeColor' => $combinedColor
            )
        );

        $labels = array(
            $this->translator->trans('mautic.email.total'),
            $this->translator->trans('mautic.email.sent'),
            $this->translator->trans('mautic.email.read'),
            $this->translator->trans('mautic.email.failed')
        );

        if (count($lists)) {
            $listRepo = $this->em->getRepository('MauticLeadBundle:LeadList');
            $statRepo = $this->em->getRepository('MauticEmailBundle:Stat');
            foreach ($lists as $l) {
                $filters        = $l->getFilters();

                $recipientCount = $listRepo->getLeadCount($filters, $l);
                $datasets[0]['data'][0] += $recipientCount;

                $sentCount      = $statRepo->getSentCount($entity->getId(), $l->getId());
                $datasets[0]['data'][1] += $sentCount;

                $readCount      = $statRepo->getReadCount($entity->getId(), $l->getId());
                $datasets[0]['data'][2] += $readCount;

                $failedCount    = $statRepo->getFailedCount($entity->getId(), $l->getId());
                $datasets[0]['data'][3] += $failedCount;

                $datasets[$l->getId()] = array();

                $datasets[$l->getId()]['data'] = array(
                    $recipientCount,
                    $sentCount,
                    $readCount,
                    $failedCount
                );

                $datasets[$l->getId()]['label'] = $l->getName();
                $color = '#' . substr(md5($datasets[$l->getId()]['label']), 0, 6);
                $datasets[$l->getId()]['fillColor'] = $this->adjustColor($color, -30);
                $datasets[$l->getId()]['strokeColor'] = $color;
                $datasets[$l->getId()]['highlightFill'] = $color;
                $datasets[$l->getId()]['highlightStroke'] = $this->adjustColor($color, 10);
            }
        }

        return array(
            'datasets' => $datasets,
            'labels' => $labels);
    }

    /**
    * @param $color_code
    * @param int $percentage_adjuster
    * @return array|string
    * @author Jaspreet Chahal
    */
    function adjustColor($color_code, $percentage_adjuster = 0) {
        $percentage_adjuster = round($percentage_adjuster/100,2);
        if(is_array($color_code)) {
            $r = $color_code["r"] - (round($color_code["r"])*$percentage_adjuster);
            $g = $color_code["g"] - (round($color_code["g"])*$percentage_adjuster);
            $b = $color_code["b"] - (round($color_code["b"])*$percentage_adjuster);

            return array("r"=> round(max(0,min(255,$r))),
                "g"=> round(max(0,min(255,$g))),
                "b"=> round(max(0,min(255,$b))));
        }
        else if(preg_match("/#/",$color_code)) {
            $hex = str_replace("#","",$color_code);
            $r = (strlen($hex) == 3)? hexdec(substr($hex,0,1).substr($hex,0,1)):hexdec(substr($hex,0,2));
            $g = (strlen($hex) == 3)? hexdec(substr($hex,1,1).substr($hex,1,1)):hexdec(substr($hex,2,2));
            $b = (strlen($hex) == 3)? hexdec(substr($hex,2,1).substr($hex,2,1)):hexdec(substr($hex,4,2));
            $r = round($r - ($r*$percentage_adjuster));
            $g = round($g - ($g*$percentage_adjuster));
            $b = round($b - ($b*$percentage_adjuster));

            return "#".str_pad(dechex( max(0,min(255,$r)) ),2,"0",STR_PAD_LEFT)
                .str_pad(dechex( max(0,min(255,$g)) ),2,"0",STR_PAD_LEFT)
                .str_pad(dechex( max(0,min(255,$b)) ),2,"0",STR_PAD_LEFT);

        }
    }

    /**
     * Send an email to lead lists
     *
     * @param Email $email
     * @param array $lists
     */
    public function sendEmailToLists(Email $email, $lists = null)
    {
        //get the leads
        if (empty($lists)) {
            $lists = $email->getLists();
        }

        $listModel  = $this->factory->getModel('lead.list');
        $listLeads = $listModel->getLeadsByList($lists);

        //get email settings such as templates, weights, etc
        $emailSettings = $this->getEmailSettings($email);
        $saveEntities  = array();

        foreach ($listLeads as $listId => $leads) {
            $listSaveEntities = $this->sendEmail($email, $leads, array('email', $email->getId()), $emailSettings, $listId, true);
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
     *
     * @return array
     */
    public function getEmailSettings(Email $email)
    {
        static $emailSettings = array();

        if (empty($emailSettings[$email->getId()])) {
            //get a list of variants for A/B testing
            $childrenVariant = $email->getVariantChildren();

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

        return $emailSettings;
    }

    /**
     * Send an email to lead(s)
     *
     * @param       $email
     * @param       $leads
     * @param       $source array('model', 'id')
     * @param array $emailSettings
     * @param null  $listId
     * @param bool  $returnEntities
     *
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     */
    public function sendEmail($email, $leads, $source = null, $emailSettings = array(), $listId = null, $returnEntities = false)
    {
        if (isset($leads['id'])) {
            $leads = array($leads['id'] => $leads);
        }

        $dispatcher   = $this->factory->getDispatcher();
        $hasListeners = $dispatcher->hasListeners(EmailEvents::EMAIL_ON_SEND);
        $templating   = $this->factory->getTemplating();
        $slotsHelper  = $templating->getEngine('MauticEmailBundle::public.html.php')->get('slots');
        /** @var \Mautic\EmailBundle\Entity\StatRepository $statRepo */
        $statRepo     = $this->em->getRepository('MauticEmailBundle:Stat');
        /** @var \Mautic\EmailBundle\Entity\EmailRepository $emailRepo */
        $emailRepo    = $this->getRepository();

        if (empty($emailSettings)) {
            //get email settings such as templates, weights, etc
            $emailSettings = $this->getEmailSettings($email);
        }

        //get the list of do not contacts
        static $dnc = array();
        if (empty($dnc)) {
            $dnc = $emailRepo->getDoNotEmailList();
        }

        $sent   = $statRepo->getSentStats($email->getId(), $listId);
        $sendTo = array_diff_key($leads, $sent);

        //weed out do not contacts
        if (!empty($dnc)) {
            foreach ($sendTo as $k => $lead) {
                if (in_array($lead['email'], $dnc)) {
                    unset($sendTo[$k]);
                }
            }
        }

        //get a count of leads
        $count  = count($sendTo);

        //noone to send to so bail
        if (empty($count)) {
            return array();
        }

        //how many of this batch should go to which email
        $batchCount  = 0;
        foreach ($emailSettings as $eid => &$details) {
            $details['limit'] = round($count * $details['weight']);
        }

        //randomize the leads for statistic purposes
        shuffle($sendTo);

        //start at the beginning for this batch
        $useEmail     = reset($emailSettings);
        $saveEntities = array();
        foreach ($sendTo as $lead) {
            $idHash = uniqid();

            if ($hasListeners) {
                $event = new EmailSendEvent($useEmail['entity'], $lead, $idHash, $source);
                $event->setSlotsHelper($slotsHelper);
                $dispatcher->dispatch(EmailEvents::EMAIL_ON_SEND, $event);
                $content = $event->getContent();
                unset($event);
            } else {
                $content = $email->getContent();
            }

            $mailer = $this->factory->getMailer();
            $mailer->setTemplate('MauticEmailBundle::public.html.php', array(
                'slots'    => $useEmail['slots'],
                'content'  => $content,
                'email'    => $useEmail['entity'],
                'template' => $useEmail['template'],
                'idHash'   => $idHash,

            ));
            $mailer->message->setTo(array($lead['email'] => $lead['firstname'] . ' ' . $lead['lastname']));
            $mailer->message->setSubject($useEmail['entity']->getSubject());

            if ($plaintext = $useEmail['entity']->getPlainText()) {
                $mailer->message->addPart($plaintext, 'text/plain');
            }

            //add the trackingID to the $message object in order to update the stats if the email failed to send
            $mailer->message->leadIdHash = $idHash;

            //queue the message
            $mailer->send();

            //save some memory
            unset($mailer);

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

            $saveEntities[] = $stat;

            //increase the sent counts
            $useEmail['entity']->upSentCounts();

            $batchCount++;
            if ($batchCount > $useEmail['limit']) {
                //use the next email
                $batchCount = 0;
                $useEmail = next($emailSettings);
            }
        }
        unset($sent, $sendTo);

        if ($returnEntities) {
            return $saveEntities;
        } else {
            $this->saveEntities($saveEntities);
        }
    }

    /**
     * Send an email to lead(s)
     *
     * @param       $email
     * @param       $users
     *
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     */
    public function sendEmailToUser($email, $users)
    {
        if (!is_array($users)) {
            $user = array('id' => $users);
            $users = array($user);
        }

        $dispatcher   = $this->factory->getDispatcher();
        $hasListeners = $dispatcher->hasListeners(EmailEvents::EMAIL_ON_SEND);
        $templating   = $this->factory->getTemplating();
        $slotsHelper  = $templating->getEngine('MauticEmailBundle::public.html.php')->get('slots');
        /** @var \Mautic\EmailBundle\Entity\EmailRepository $emailRepo */
        $emailRepo    = $this->getRepository();
        //get email settings such as templates, weights, etc
        $emailSettings = $this->getEmailSettings($email);
        $emailSettings = reset($emailSettings);

        //noone to send to so bail
        if (empty($users)) {
            return array();
        }

        foreach ($users as $user) {
            $idHash = uniqid();
            $content = $email->getContent();

            if (!isset($user['email'])) {
                /** @var \Mautic\UserBundle\Model\UserModel $model */
                $userModel  = $this->factory->getModel('user');
                $userEntity  = $userModel->getEntity($user['id']);
                $user['email'] = $userEntity->getEmail();
                $user['firstname'] = $userEntity->getFirstName();
                $user['lastname'] = $userEntity->getLastName();
            }

            $mailer = $this->factory->getMailer();
            $mailer->setTemplate('MauticEmailBundle::public.html.php', array(
                'slots'    => $emailSettings['slots'],
                'content'  => $content,
                'email'    => $emailSettings['entity'],
                'template' => $emailSettings['template'],
                'idHash'   => $idHash,

            ));
            $mailer->message->setTo(array($user['email'] => $user['firstname'] . ' ' . $user['lastname']));
            $mailer->message->setSubject($emailSettings['entity']->getSubject());

            if ($plaintext = $emailSettings['entity']->getPlainText()) {
                $mailer->message->addPart($plaintext, 'text/plain');
            }

            //queue the message
            $mailer->send();

            //save some memory
            unset($mailer);
        }
    }

    /**
     * @param Stat   $stat
     * @param        $reason
     * @param string $tag
     */
    public function setDoNotContact(Stat $stat, $reason, $tag = 'bounced')
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

            //update stats
            $stat->setIsFailed(true);
            $em->persist($stat);

            if ($email !== null) {
                $email->downSentCounts();
                $em->persist($email);
            }

            $em->flush();
        }
    }

    /**
     * Remove email from DNC list
     *
     * @param $email
     */
    public function removeDoNotContact($email)
    {
        $this->getRepository()->removeFromDoNotEmailList($email);
    }
}