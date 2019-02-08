<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Export;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use MauticPlugin\MauticCrmBundle\Integration\Pipedrive\AbstractPipedrive;

class ActivitiesExport extends AbstractPipedrive
{
    /** @var string */
    private $timeout;

    /** @var \DateTime */
    private $endDate;
    /**
     * @var LeadModel
     */
    private $leadModel;

    /** @var DateTimeHelper */
    private $dateTimeHelper;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * ActivitiesExport constructor.
     *
     * @param EntityManager $entityManager
     * @param LeadModel     $leadModel
     */
    public function __construct(EntityManager $entityManager, LeadModel $leadModel)
    {
        $this->leadModel      = $leadModel;
        $this->entityManager  = $entityManager;
        $this->dateTimeHelper = new DateTimeHelper();
        $this->endDate        = $this->dateTimeHelper->getDateTime();
    }

    /**
     * @param string|array $eventLabel
     *
     * @return string
     */
    private function generateDescription($eventLabel)
    {
        if (is_array($eventLabel) && isset($eventLabel['href']) && isset($eventLabel['label'])) {
            return '<a  href="'.$eventLabel['href'].'" '.(!empty($eventLabel['isExternal]']) ? 'target="_blank"' : '').'>'.$eventLabel['label'].'</a>';
        }

        return '';
    }

    /**
     * @param string|array $eventLabel
     *
     * @return string
     */
    private function getEventLabel($eventLabel)
    {
        if (is_array($eventLabel) && isset($eventLabel['label'])) {
            return $eventLabel['label'];
        }

        return $eventLabel;
    }

    /**
     * @param IntegrationEntity $integrationEntity
     * @param \DateTime|null    $lastActivitySync
     */
    public function createActivities(IntegrationEntity $integrationEntity, $lastActivitySync = null)
    {
        $config         = $this->getIntegration()->mergeConfigToFeatureSettings();
        $activityEvents = isset($config['activityEvents']) ? $config['activityEvents'] : [];

        // no activity events sync
        if (empty($activityEvents)) {
            return;
        }

        $contact = $this->leadModel->getEntity($integrationEntity->getInternalEntityId());
        if (!$contact) {
            return;
        }

        $filters = [
            'search'        => '',
            'includeEvents' => $activityEvents,
            'excludeEvents' => [],
        ];

        if ($lastActivitySync instanceof \DateTime) {
            $filters['dateFrom'] = $lastActivitySync;
            $filters['dateTo']   = $this->endDate;
        }

        $page     = 1;
        while (true) {
            $engagements = $this->leadModel->getEngagements($contact, $filters, null, $page, 100, false);
            $events      = $engagements[0]['events'];

            if (empty($events)) {
                // last event log set to integration Entity
                $internal                       = $integrationEntity->getInternal();
                $internal['last_activity_sync'] = (new DateTimeHelper())->getLocalTimestamp();
                $integrationEntity->setInternal($internal);
                $this->entityManager->persist($integrationEntity);
                $this->entityManager->flush();
                break;
            }

            foreach ($events as $event) {
                // Create activity before exists
                $activityType = $event['event'];
                if (!$this->getIntegration()->getApiHelper()->getActivityType($activityType)) {
                    $this->getIntegration()->getApiHelper()->createActivityType($activityType);
                }
                $this->dateTimeHelper->setDateTime($event['timestamp']);
                $data              = [];
                $data['subject']   = $this->getEventLabel($event['eventLabel']);
                $data['done']      = 1;
                $data['type']      = $activityType;
                $data['person_id'] = $integrationEntity->getIntegrationEntityId();
                $data['due_date']  = $this->dateTimeHelper->getDateTime()->format('Y-m-d');
                $data['due_time']  = $this->dateTimeHelper->getDateTime()->format('H:i:s');
                $data['note']      = $this->generateDescription($event['eventLabel']);
                $this->getIntegration()->getApiHelper()->addActivity($data);
            }
            ++$page;
        }
    }
}
