<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Form\Type\CampaignActionAnonymizeUserDataType;
use Mautic\LeadBundle\Helper\AnonymizeHelper;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignActionAnonymizeUserDataSubscriber implements EventSubscriberInterface
{
    public function __construct(private LeadModel $leadModel, private FieldModel $fieldModel)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD                  => ['configureAction', 0],
            LeadEvents::ON_CAMPAIGN_ACTION_ANONYMIZE_USER_DATA => ['anonymizeUserData', 0],
        ];
    }

    public function configureAction(CampaignBuilderEvent $event): void
    {
        $event->addAction(
            'lead.action_anonymizeuserdata',
            [
                'label'                  => 'mautic.lead.lead.events.anonymize',
                'description'            => 'mautic.lead.lead.events.anonymize_descr',
                // Kept for BC in case plugins are listening to the shared trigger
                'eventName'              => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
                'formType'               => CampaignActionAnonymizeUserDataType::class,
                'batchEventName'         => LeadEvents::ON_CAMPAIGN_ACTION_ANONYMIZE_USER_DATA,
            ]
        );
    }

    public function anonymizeUserData(PendingEvent $event): void
    {
        $leads      = $this->leadModel->getEntities($event->getContactIds());
        $properties = $event->getEvent()->getProperties();
        $idFields   = array_merge($properties['fieldsToAnonymize'], $properties['fieldsToDelete']);
        $fields     = $this->fieldModel->getRepository()->findBy(['id' => $idFields]);
        foreach ($fields as $field) {
            if (in_array($field->getId(), $properties['fieldsToDelete'])) {
                $leads = $this->setDeleteFields($leads, $field);
                continue;
            }
            if (in_array($field->getId(), $properties['fieldsToAnonymize'])) {
                $leads = $this->setHashFields($leads, $field);
            }
        }
        $this->leadModel->saveEntities($leads);
        $event->passAll();
    }

    /**
     * @param array<Lead> $leads
     *
     * @return array<Lead>
     */
    private function setDeleteFields(array $leads, LeadField $field): array
    {
        foreach ($leads as $key => $lead) {
            $leadField = $lead->getField($field->getAlias());
            if (empty($leadField['value'])) {
                continue;
            }
            $leads[$key] = $lead->addUpdatedField($field->getAlias(), null);
        }

        return $leads;
    }

    /**
     * @param array<Lead> $leads
     *
     * @return array<Lead>
     */
    private function setHashFields(array $leads, LeadField $field): array
    {
        foreach ($leads as $key => $lead) {
            $leadField = $lead->getField($field->getAlias());
            if (empty($leadField['value'])) {
                continue;
            }
            if ('email' === $field->getType()) {
                $leads[$key] = $lead->addUpdatedField($field->getAlias(), AnonymizeHelper::email($leadField['value']));
                continue;
            }

            $leads[$key] = $lead->addUpdatedField($field->getAlias(), AnonymizeHelper::text($leadField['value']));
        }

        return $leads;
    }
}
