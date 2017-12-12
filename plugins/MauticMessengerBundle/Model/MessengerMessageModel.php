<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticMessengerBundle\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\TranslationModelTrait;
use Mautic\CoreBundle\Model\VariantModelTrait;
use MauticPlugin\MauticMessengerBundle\MessengerEvents;
use MauticPlugin\MauticMessengerBundle\Entity\MessengerMessage;
use MauticPlugin\MauticMessengerBundle\Entity\MessengerMessageRepository;
use MauticPlugin\MauticMessengerBundle\Entity\Stat;
use MauticPlugin\MauticMessengerBundle\Event\MessengerMessageEvent;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class MessengerMessageModel extends FormModel implements AjaxLookupModelInterface
{
    use VariantModelTrait;
    use TranslationModelTrait;

    /**
     * Retrieve the permissions base.
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'messenger:messages';
    }

    /**
     * {@inheritdoc}
     *
     * @return MessengerMessageRepositary
     */
    public function getRepository()
    {
        /** @var MessengerMessageRepositary $repo */
        $repo = $this->em->getRepository('MauticMessengerBundle:MessengerMessage');

        $repo->setTranslator($this->translator);

        return $repo;
    }

    /**
     * @return \MauticPlugin\MauticMessengerBundle\Entity\StatRepository
     */
    public function getStatRepository()
    {
        return $this->em->getRepository('MauticMessengerBundle:Stat');
    }

    /**
     * {@inheritdoc}
     *
     * @param object $entity
     * @param bool   $unlock
     */
    public function saveEntity($entity, $unlock = true)
    {
        parent::saveEntity($entity, $unlock);

        $this->postTranslationEntitySave($entity);
    }

    /**
     * Here just so PHPStorm calms down about type hinting.
     *
     * @param null $id
     *
     * @return null|MessengerMessage
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new MessengerMessage();
        }

        return parent::getEntity($id);
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
     * @throws \InvalidArgumentException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof MessengerMessage) {
            throw new \InvalidArgumentException('Entity must be of class MessengerMessage');
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create('msg', $entity, $options);
    }

   

    /**
     * @param MessengerMessage $messengerMessage
     * @param Lead|array     $lead
     * @param string         $source
     */
    public function createStatEntry(MessengerMessage $messengerMessage, $lead, $source = null)
    {
        if (is_array($lead)) {
            $lead = $this->em->getReference('MauticLeadBundle:Lead', $lead['id']);
        }

        $stat = new Stat();
        $stat->setDateSent(new \DateTime());
        $stat->setLead($lead);
        $stat->setMessengerMessage($messengerMessage);
        $stat->setSource($source);

        $this->getStatRepository()->saveEntity($stat);
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $entity
     * @param $isNew
     * @param $event
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof MessengerMessage) {
            throw new MethodNotAllowedHttpException(['Messenger']);
        }

        switch ($action) {
            case 'pre_save':
                $name = MessengerEvents::PRE_SAVE;
                break;
            case 'post_save':
                $name = MessengerEvents::POST_SAVE;
                break;
            case 'pre_delete':
                $name = MessengerEvents::PRE_DELETE;
                break;
            case 'post_delete':
                $name = MessengerEvents::POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new MessengerMessageEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * Joins the page table and limits created_by to currently logged in user.
     *
     * @param QueryBuilder $q
     */
    public function limitQueryToCreator(QueryBuilder &$q)
    {
        $q->join('t', MAUTIC_TABLE_PREFIX.'messenger_message', 'd', 'd.id = t.dmessenger_message_id')
            ->andWhere('d.created_by = :userId')
            ->setParameter('userId', $this->userHelper->getUser()->getId());
    }

    /**
     * Get line chart data of hits.
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
    public function getHitsLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [], $canViewOthers = true)
    {
        $flag = null;

        if (isset($filter['flag'])) {
            $flag = $filter['flag'];
            unset($filter['flag']);
        }

        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);

        if (!$flag || $flag === 'total_and_unique') {
            $q = $query->prepareTimeDataQuery('messenger_message_stats', 'date_sent', $filter);

            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }

            $data = $query->loadAndBuildTimeData($q);
            $chart->setDataset($this->translator->trans('mautic.messengerMessage.show.total.views'), $data);
        }

        if ($flag === 'unique' || $flag === 'total_and_unique') {
            $q = $query->prepareTimeDataQuery('messenger_message_stats', 'date_sent', $filter);
            $q->groupBy('t.lead_id, t.date_sent');

            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }

            $data = $query->loadAndBuildTimeData($q);
            $chart->setDataset($this->translator->trans('mautic.messengerMessage.show.unique.views'), $data);
        }

        return $chart->render();
    }

    /**
     * @param        $type
     * @param string $filter
     * @param int    $limit
     * @param int    $start
     * @param array  $options
     */
    public function getLookupResults($type, $filter = '', $limit = 10, $start = 0, $options = [])
    {
        $results = [];
        switch ($type) {
            case 'messengerMessage':
                $entities = $this->getRepository()->getMessengerMessageList(
                    $filter,
                    $limit,
                    $start,
                    $this->security->isGranted($this->getPermissionBase().':viewother'),
                    isset($options['top_level']) ? $options['top_level'] : false,
                    isset($options['ignore_ids']) ? $options['ignore_ids'] : []
                );

                foreach ($entities as $entity) {
                    $results[$entity['language']][$entity['id']] = $entity['name'];
                }

                //sort by language
                ksort($results);

                break;
        }

        return $results;
    }
}
