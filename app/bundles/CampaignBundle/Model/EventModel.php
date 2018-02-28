<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Model;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Executioner\DecisionExecutioner;
use Mautic\CampaignBundle\Executioner\Dispatcher\EventDispatcher;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CampaignBundle\Executioner\InactiveExecutioner;
use Mautic\CampaignBundle\Executioner\KickoffExecutioner;
use Mautic\CampaignBundle\Executioner\ScheduledExecutioner;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Model\UserModel;

/**
 * Class EventModel
 * {@inheritdoc}
 */
class EventModel extends LegacyEventModel
{
    /**
     * @var UserModel
     */
    protected $userModel;

    /**
     * @var NotificationModel
     */
    protected $notificationModel;

    /**
     * EventModel constructor.
     *
     * @param IpLookupHelper       $ipLookupHelper
     * @param LeadModel            $leadModel
     * @param UserModel            $userModel
     * @param NotificationModel    $notificationModel
     * @param CampaignModel        $campaignModel
     * @param DecisionExecutioner  $decisionExecutioner
     * @param KickoffExecutioner   $kickoffExecutioner
     * @param ScheduledExecutioner $scheduledExecutioner
     * @param InactiveExecutioner  $inactiveExecutioner
     * @param EventExecutioner     $eventExecutioner
     * @param EventDispatcher      $eventDispatcher
     */
    public function __construct(
        UserModel $userModel,
        NotificationModel $notificationModel,
        CampaignModel $campaignModel,
        LeadModel $leadModel,
        IpLookupHelper $ipLookupHelper,
        DecisionExecutioner $decisionExecutioner,
        KickoffExecutioner $kickoffExecutioner,
        ScheduledExecutioner $scheduledExecutioner,
        InactiveExecutioner $inactiveExecutioner,
        EventExecutioner $eventExecutioner,
        EventDispatcher $eventDispatcher,
        EventCollector $eventCollector
    ) {
        $this->userModel         = $userModel;
        $this->notificationModel = $notificationModel;

        parent::__construct(
            $campaignModel,
            $leadModel,
            $ipLookupHelper,
            $decisionExecutioner,
            $kickoffExecutioner,
            $scheduledExecutioner,
            $inactiveExecutioner,
            $eventExecutioner,
            $eventDispatcher,
            $eventCollector
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\CampaignBundle\Entity\EventRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:Event');
    }

    /**
     * Get CampaignRepository.
     *
     * @return \Mautic\CampaignBundle\Entity\CampaignRepository
     */
    public function getCampaignRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:Campaign');
    }

    /**
     * @return LeadEventLogRepository
     */
    public function getLeadEventLogRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:LeadEventLog');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'campaign:campaigns';
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param $id
     *
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Event();
        }

        $entity = parent::getEntity($id);

        return $entity;
    }

    /**
     * Delete events.
     *
     * @param $currentEvents
     * @param $deletedEvents
     */
    public function deleteEvents($currentEvents, $deletedEvents)
    {
        $deletedKeys = [];
        foreach ($deletedEvents as $k => $deleteMe) {
            if ($deleteMe instanceof Event) {
                $deleteMe = $deleteMe->getId();
            }

            if (strpos($deleteMe, 'new') === 0) {
                unset($deletedEvents[$k]);
            }

            if (isset($currentEvents[$deleteMe])) {
                unset($deletedEvents[$k]);
            }

            if (isset($deletedEvents[$k])) {
                $deletedKeys[] = $deleteMe;
            }
        }

        if (count($deletedEvents)) {
            // wipe out any references to these events to prevent restraint violations
            $this->getRepository()->nullEventRelationships($deletedKeys);

            // delete the events
            $this->deleteEntities($deletedEvents);
        }
    }

    /**
     * Get line chart data of campaign events.
     *
     * @param string    $unit          {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param string    $dateFormat
     * @param array     $filter
     * @param bool      $canViewOthers
     *
     * @return array
     */
    public function getEventLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [], $canViewOthers = true)
    {
        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $q     = $query->prepareTimeDataQuery('campaign_lead_event_log', 'date_triggered', $filter);

        if (!$canViewOthers) {
            $q->join('t', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'c.id = c.campaign_id')
                ->andWhere('c.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $data = $query->loadAndBuildTimeData($q);
        $chart->setDataset($this->translator->trans('mautic.campaign.triggered.events'), $data);

        return $chart->render();
    }

    /**
     * @param Lead $lead
     * @param      $campaignCreatedBy
     * @param      $header
     */
    public function notifyOfFailure(Lead $lead, $campaignCreatedBy, $header)
    {
        // Notify the lead owner if there is one otherwise campaign creator that there was a failure
        if (!$owner = $lead->getOwner()) {
            $ownerId = (int) $campaignCreatedBy;
            $owner   = $this->userModel->getEntity($ownerId);
        }

        if ($owner && $owner->getId()) {
            $this->notificationModel->addNotification(
                $header,
                'error',
                false,
                $this->translator->trans(
                    'mautic.campaign.event.failed',
                    [
                        '%contact%' => '<a href="'.$this->router->generate(
                                'mautic_contact_action',
                                ['objectAction' => 'view', 'objectId' => $lead->getId()]
                            ).'" data-toggle="ajax">'.$lead->getPrimaryIdentifier().'</a>',
                    ]
                ),
                null,
                null,
                $owner
            );
        }
    }
}
