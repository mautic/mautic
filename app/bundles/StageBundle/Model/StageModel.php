<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle\Model;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\StageBundle\Entity\Action;
use Mautic\StageBundle\Entity\LeadStageLog;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Event\StageBuilderEvent;
use Mautic\StageBundle\Event\StageEvent;
use Mautic\StageBundle\StageEvents;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class StageModel
 */
class StageModel extends CommonFormModel
{
    /**
     * @deprecated Remove in 2.0
     *
     * @var MauticFactory
     */
    protected $factory;
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var LeadModel
     */
    protected $leadModel;
    /**
     * PointModel constructor.
     *
     * @param Session $session
     * @param LeadModel $leadModel
     */
    public function __construct(LeadModel $leadModel, Session $session)
    {
        $this->session = $session;
        $this->leadModel = $leadModel;
    }
    /**
     * {@inheritdoc}
     *
     * @return \Mautic\StageBundle\Entity\StageRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticStageBundle:Stage');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'stage:stages';
    }

    /**
     * {@inheritdoc}
     *
     * @throws MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Stage) {
            throw new MethodNotAllowedHttpException(array('Stage'));
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }
        return $formFactory->create('stage', $entity, $options);
    }

    /**
     * {@inheritdoc}
     *
     * @return Stage|null
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Stage();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Stage) {
            throw new MethodNotAllowedHttpException(array('Stage'));
        }

        switch ($action) {
            case "pre_save":
                $name = StageEvents::STAGE_PRE_SAVE;
                break;
            case "post_save":
                $name = StageEvents::STAGE_POST_SAVE;
                break;
            case "pre_delete":
                $name = StageEvents::STAGE_PRE_DELETE;
                break;
            case "post_delete":
                $name = StageEvents::STAGE_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new StageEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);
            return $event;
        }

        return null;
    }

    /**
     * Gets array of custom actions from bundles subscribed StageEvents::STAGE_ON_BUILD
     *
     * @return mixed
     */
    public function getStageActions()
    {
        static $actions;

        if (empty($actions)) {
            //build them
            $actions = array();
            $event = new StageBuilderEvent($this->translator);
            $this->dispatcher->dispatch(StageEvents::STAGE_ON_BUILD, $event);
            $actions['actions'] = $event->getActions();
            $actions['list']    = $event->getActionList();
            $actions['choices'] = $event->getActionChoices();
        }

        return $actions;
    }

    /**
     * Get line chart data of stages
     *
     * @param char     $unit   {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @param string   $dateFormat
     * @param array    $filter
     * @param boolean  $canViewOthers
     *
     * @return array
     */
    public function getStageLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = array(), $canViewOthers = true)
    {
        $chart     = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query     = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $q         = $query->prepareTimeDataQuery('lead_stages_change_log', 'date_added', $filter);

        if (!$canViewOthers) {
            $q->join('t', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = t.lead_id')
                ->andWhere('l.owner_id = :userId')
                ->setParameter('userId', $this->factory->getUser()->getId());
        }

        $data = $query->loadAndBuildTimeData($q);
        $chart->setDataset($this->factory->getTranslator()->trans('mautic.stage.changes'), $data);
        return $chart->render();
    }

    /**
     *
     * @return mixed
     */
    public function getUserStages()
    {
        $user  = (!$this->security->isGranted('stage:stages:viewother')) ?
            $this->factory->getUser() : false;
        $stages = $this->em->getRepository('MauticStageBundle:Stage')->getStages($user);

        return $stages;
    }

    /**
     * Triggers a specific stage change (this function is not being used any more but leaving it in case we do want to move stages through different actions)
     *
     * @param $type
     * @param mixed $eventDetails passthrough from function triggering action to the callback function
     * @param mixed $typeId Something unique to the triggering event to prevent  unnecessary duplicate calls
     * @param Lead  $lead
     *
     * @return void
     */
    public function triggerAction($type, $eventDetails = null, $typeId = null, Lead $lead = null)
    {
        //only trigger actions for anonymous users
        if (!$this->security->isAnonymous()) {
            return;
        }

        if ($typeId !== null && MAUTIC_ENV === 'prod') {
            $triggeredEvents = $this->session->get('mautic.triggered.stage.actions', array());
            if (in_array($typeId, $triggeredEvents)) {
                return;
            }
            $triggeredEvents[] = $typeId;
            $this->session->set('mautic.triggered.stage.actions', $triggeredEvents);
        }

        //find all the actions for published stages
        /** @var \Mautic\StageBundle\Entity\StageRepository $repo */
        $repo            = $this->getRepository();
        $availableStages = $repo->getPublishedByType($type);

        if (null === $lead) {
            $lead = $this->leadModel->getCurrentLead();

            if (null === $lead || !$lead->getId()) {

                return;
            }
        }

        //get available actions
        $availableActions = $this->getStageActions();

        //get a list of actions that has already been performed on this lead
        $completedActions = $repo->getCompletedLeadActions($type, $lead->getId());

        $persist = array();
        foreach ($availableStages as $action) {

            //if it's already been done, then skip it
            if (isset($completedActions[$action->getId()])) {
                continue;
            }

            //make sure the action still exists
            if (!isset($availableActions['actions'][$action->getName()])) {
                continue;
            }
            
            $parsed = explode('.', $action->getName());
            $lead->stageChangeLogEntry(
                $parsed[0],
                $action->getId() . ": " . $action->getName(),
                'Moved by an action'
            );
            $lead->setStage($action);
            $log = new LeadStageLog();
            $log->setStage($action);
            $log->setLead($lead);
            $log->setDateFired(new \DateTime());

            $persist[] = $log;
        }

        if (!empty($persist)) {
            $this->leadModel->saveEntity($lead);
            $this->getRepository()->saveEntities($persist);

            // Detach logs to reserve memory
            $this->em->clear('Mautic\StageBundle\Entity\LeadStageLog');
        }
    }
}
