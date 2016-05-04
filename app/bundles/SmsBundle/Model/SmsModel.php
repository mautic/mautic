<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Model;

use Mautic\CoreBundle\Helper\GraphHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Entity\Stat;
use Mautic\SmsBundle\Event\SmsEvent;
use Mautic\SmsBundle\Event\SmsClickEvent;
use Mautic\SmsBundle\SmsEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class SmsModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class SmsModel extends FormModel
{
    /**
     * {@inheritdoc}
     *
     * @return \Mautic\SmsBundle\Entity\SmsRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticSmsBundle:Sms');
    }

    /**
     * @return \Mautic\SmsBundle\Entity\StatRepository
     */
    public function getStatRepository()
    {
        return $this->factory->getEntityManager()->getRepository('MauticSmsBundle:Stat');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'sms:smses';
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

            if ($dispatchEvent = $entity instanceof Sms) {
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
     * @param       $entity
     * @param       $formFactory
     * @param null  $action
     * @param array $options
     *
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws MethodNotAllowedHttpException
     */
    public function createForm ($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Sms) {
            throw new MethodNotAllowedHttpException(array('Sms'));
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create('sms', $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     *
     * @return null|Sms
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            $entity = new Sms;
        } else {
            $entity = parent::getEntity($id);
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
        if (!$entity instanceof Sms) {
            throw new MethodNotAllowedHttpException(array('Sms'));
        }

        switch ($action) {
            case "pre_save":
                $name = SmsEvents::SMS_PRE_SAVE;
                break;
            case "post_save":
                $name = SmsEvents::SMS_POST_SAVE;
                break;
            case "pre_delete":
                $name = SmsEvents::SMS_PRE_DELETE;
                break;
            case "post_delete":
                $name = SmsEvents::SMS_POST_DELgetETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new SmsEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param $idHash
     *
     * @return Stat
     */
    public function getSmsStatus($idHash)
    {
        return $this->getStatRepository()->getSmsStatus($idHash);
    }

    /**
     * Search for an sms stat by sms and lead IDs
     *
     * @param $smsId
     * @param $leadId
     *
     * @return array
     */
    public function getSmsStatByLeadId($smsId, $leadId)
    {
        return $this->getStatRepository()->findBy(
            array(
                'sms' => (int) $smsId,
                'lead'  => (int) $leadId
            ),
            array('dateSent' => 'DESC')
        );
    }

    /**
     * Get a stats for sms by list
     *
     * @param Sms|int $sms
     *
     * @return array
     */
    public function getSmsListStats($sms)
    {
        if (! $sms instanceof Sms) {
            $sms = $this->getEntity($sms);
        }

        $smsIds = array($sms->getId());

        $lists     = $sms->getLists();
        $listCount = count($lists);

        $combined = $this->translator->trans('mautic.sms.lists.combined');
        $datasets = array(
            $combined => array(0, 0, 0)
        );

        $labels = array(
            $this->translator->trans('mautic.sms.sent')
        );

        if ($listCount) {
            /** @var \Mautic\SmsBundle\Entity\StatRepository $statRepo */
            $statRepo = $this->em->getRepository('MauticSmsBundle:Stat');

            foreach ($lists as $l) {
                $name = $l->getTitle();

                $sentCount = $statRepo->getSentCount($smsIds, $l->getId());
                $datasets[$combined][0] += $sentCount;

                $datasets[$name] = array();

                $datasets[$name] = array(
                    $sentCount
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
     * @param int|Sms $sms
     * @param int       $amount
     * @param string    $unit
     *
     * @return array
     */
    public function getSmsGeneralStats($sms, $amount = 30, $unit = 'D')
    {
        if (! $sms instanceof Sms) {
            $sms = $this->getEntity($sms);
        }

        $smsIds = array($sms->getId());

        /** @var \Mautic\SmsBundle\Entity\StatRepository $statRepo */
        $statRepo = $this->em->getRepository('MauticSmsBundle:Stat');

        $graphData = GraphHelper::prepareDatetimeLineGraphData($amount, $unit,
            array(
                $this->translator->trans('mautic.sms.stat.sent')
            )
        );

        $fromDate = $graphData['fromDate'];

        $sentData  = $statRepo->getSmsStats($smsIds, $fromDate, 'sent');
        $graphData = GraphHelper::mergeLineGraphData($graphData, $sentData, $unit, 0, 'date', 'data');

        return $graphData;
    }

    /**
     * Get an array of tracked links
     *
     * @param $smsId
     *
     * @return array
     */
    public function getSmsClickStats($smsId)
    {
        return $this->factory->getModel('page.trackable')->getTrackableList('sms', $smsId);
    }
}
