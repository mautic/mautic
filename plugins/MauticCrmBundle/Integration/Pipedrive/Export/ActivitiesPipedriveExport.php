<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Export;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use MauticPlugin\MauticCrmBundle\Integration\Pipedrive\AbstractPipedrive;

class ActivitiesPipedriveExport extends AbstractPipedrive
{
    /*
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
    }

    /**
     * @param IntegrationEntity       $integrationEntity
     * @param \DateTimeInterface|null $lastActivitySync
     */
    public function createActivities(IntegrationEntity $integrationEntity, \DateTimeInterface $lastActivitySync = null)
    {
        $config         = $this->getIntegration()->mergeConfigToFeatureSettings();
        $activityEvents = isset($config['activityEvents']) ? $config['activityEvents'] : [];

        // stop, if no events
        if (empty($activityEvents)) {
            return false;
        }

        // stop if contact not exists
        $contact = $this->leadModel->getEntity($integrationEntity->getInternalEntityId());
        if (!$contact) {
            return false;
        }

        $filters = [
            'search'        => '',
            'includeEvents' => $activityEvents,
            'excludeEvents' => [],
        ];

        $dateTo = $this->dateTimeHelper->getDateTime();
        if ($lastActivitySync instanceof \DateTimeInterface) {
            $filters['dateFrom'] = $lastActivitySync;
            $filters['dateTo']   = $dateTo;
        }

        $page     = 1;
        while (true) {
            $engagements = $this->leadModel->getEngagements($contact, $filters, null, $page, 100, false);
            $events      = $engagements[0]['events'];
            if (empty($events)) {
                // last event log set to integration Entity
                $internal                       = $integrationEntity->getInternal();
                $internal['last_activity_sync'] = $dateTo->getTimestamp();
                $integrationEntity->setInternal($internal);
                $this->entityManager->persist($integrationEntity);
                $this->entityManager->flush();
                break;
            }

            foreach ($events as $event) {
                // Create activity before exists
                if (!$this->getIntegration()->getApiHelper()->getActivityType($event)) {
                    $this->getIntegration()->getApiHelper()->createActivityType($event);
                }
                $this->dateTimeHelper->setDateTime($event['timestamp']);
                $data              = [];
                $data['subject']   = $this->generateEventLabel($event);
                $data['done']      = 1;
                $data['type']      = $event['eventType'];
                $data['person_id'] = $integrationEntity->getIntegrationEntityId();
                $data['due_date']  = $this->dateTimeHelper->getDateTime()->format('Y-m-d');
                $data['due_time']  = $this->dateTimeHelper->getDateTime()->format('H:i:s');
                $data['note']      = $this->generateDescription($event['eventLabel']);
                $this->getIntegration()->getApiHelper()->addActivity($data);
            }
            ++$page;
        }
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
     * @param array $eventLabel
     *
     * @return string
     */
    private function generateEventLabel($event)
    {
        $eventLabel = $event['eventLabel'];
        if (is_array($eventLabel) && isset($eventLabel['label'])) {
            return $event['eventType'].': '.$eventLabel['label'];
        }

        return $eventLabel;
    }
}
