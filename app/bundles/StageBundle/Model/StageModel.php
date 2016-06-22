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
        $query     = $chart->getChartQuery($this->factory->getEntityManager()->getConnection());
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
     * Triggers a specific stage change
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
            $this->factory->getLogger()->addError(print_r($availableActions,true));
            //make sure the action still exists
            if (!isset($availableActions['actions'][$action->getType()])) {
                continue;
            }
            
            $parsed = explode('.', $action->getType());
            $lead->stageChangeLogEntry(
                $parsed[0],
                $action->getId() . ": " . $action->getName(),
                $parsed[1]
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
    /**
     * Add lead to the stage
     *
     * @param Stage  $stage
     * @param           $lead
     * @param bool|true $manuallyAdded
     */
    public function addLead (Stage $stage, $lead, $manuallyAdded = true)
    {
        $this->addLeads($stage, array($lead), $manuallyAdded);

        unset($stage, $lead);
    }

    /**
     * Add lead(s) to a stage
     *
     * @param Stage $stage
     * @param array    $leads
     * @param bool     $manuallyAdded
     * @param bool     $batchProcess
     * @param int      $searchListLead
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function addLeads (Stage $stage, array $leads, $manuallyAdded = false, $batchProcess = false, $searchListLead = 1)
    {
        foreach ($leads as $lead) {
            if (!$lead instanceof Lead) {
                $leadId = (is_array($lead) && isset($lead['id'])) ? $lead['id'] : $lead;
                $lead   = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
            }

            if ($searchListLead == -1) {
                $stageLead = null;
            }
            elseif ($searchListLead) {
                $stageLead = $this->getStageLeadRepository()->findOneBy(array(
                    'lead'     => $lead,
                    'stage' => $stage
                ));
            }

            $dispatchEvent = true;
            if ($stageLead != null) {
                if ($stageLead->wasManuallyRemoved()) {
                    $stageLead->setManuallyRemoved(false);
                    $stageLead->setManuallyAdded($manuallyAdded);

                    try {
                        $this->getRepository()->saveEntity($stageLead);
                    } catch (\Exception $exception) {
                        $dispatchEvent = false;
                        $this->logger->log('error', $exception->getMessage());
                    }
                } else {
                    $this->em->detach($stageLead);
                    if ($batchProcess) {
                        $this->em->detach($lead);
                    }

                    unset($stageLead, $lead);

                    continue;
                }
            } else {
                $stageLead = new \Mautic\StageBundle\Entity\Lead();
                $stageLead->setStage($stage);
                $stageLead->setDateAdded(new \DateTime());
                $stageLead->setLead($lead);
                $stageLead->setManuallyAdded($manuallyAdded);

                try {
                    $this->getRepository()->saveEntity($stageLead);
                } catch (\Exception $exception) {
                    $dispatchEvent = false;
                    $this->logger->log('error', $exception->getMessage());
                }
            }

            if ($dispatchEvent && $this->dispatcher->hasListeners(StageEvents::CAMPAIGN_ON_LEADCHANGE)) {
                $event = new Events\StageLeadChangeEvent($stage, $lead, 'added');
                $this->dispatcher->dispatch(StageEvents::CAMPAIGN_ON_LEADCHANGE, $event);

                unset($event);
            }

            // Detach StageLead to save memory
            $this->em->detach($stageLead);
            if ($batchProcess) {
                $this->em->detach($lead);
            }
            unset($stageLead, $lead);
        }

        unset($leadModel, $stage, $leads);
    }

    /**
     * Remove lead from the stage
     *
     * @param Stage $stage
     * @param          $lead
     * @param bool     $manuallyRemoved
     */
    public function removeLead (Stage $stage, $lead, $manuallyRemoved = true)
    {
        $this->removeLeads($stage, array($lead), $manuallyRemoved);

        unset($stage, $lead);
    }

    /**
     * Remove lead(s) from the stage
     *
     * @param Stage   $stage
     * @param array      $leads
     * @param bool|false $manuallyRemoved
     * @param bool|false $batchProcess
     * @param bool|false $skipFindOne
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function removeLeads (Stage $stage, array $leads, $manuallyRemoved = false, $batchProcess = false, $skipFindOne = false)
    {
        foreach ($leads as $lead) {
            $dispatchEvent = false;

            if (!$lead instanceof Lead) {
                $leadId = (is_array($lead) && isset($lead['id'])) ? $lead['id'] : $lead;
                $lead   = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
            }

            $stageLead = (!$skipFindOne) ?
                $this->getStageLeadRepository()->findOneBy(array(
                    'lead'     => $lead,
                    'stage' => $stage
                )) :
                $this->em->getReference('MauticStageBundle:Lead', array(
                    'lead'     => $leadId,
                    'stage' => $stage->getId()
                ));

            if ($stageLead == null) {
                if ($batchProcess) {
                    $this->em->detach($lead);
                    unset($lead);
                }

                continue;
            }

            if (($manuallyRemoved && $stageLead->wasManuallyAdded()) || (!$manuallyRemoved && !$stageLead->wasManuallyAdded())) {
                //lead was manually added and now manually removed or was not manually added and now being removed

                // Manually added and manually removed so chuck it
                $dispatchEvent   = true;

                $this->getEventRepository()->deleteEntity($stageLead);
            } elseif ($manuallyRemoved) {
                $dispatchEvent = true;

                $stageLead->setManuallyRemoved(true);
                $this->getEventRepository()->saveEntity($stageLead);
            }

            if ($dispatchEvent) {
                //remove scheduled events if the lead was removed
                $this->removeScheduledEvents($stage, $lead);

                if ($this->dispatcher->hasListeners(StageEvents::CAMPAIGN_ON_LEADCHANGE)) {
                    $event = new Events\StageLeadChangeEvent($stage, $lead, 'removed');
                    $this->dispatcher->dispatch(StageEvents::CAMPAIGN_ON_LEADCHANGE, $event);

                    unset($event);
                }
            }

            // Detach StageLead to save memory
            $this->em->detach($stageLead);

            if ($batchProcess) {
                $this->em->detach($lead);
            }

            unset($stageLead, $lead);
        }
    }

}
