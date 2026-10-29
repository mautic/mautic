<?php

namespace Mautic\FormBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\ConditionEvent;
use Mautic\CampaignBundle\Event\DecisionEvent;
use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\Form\Type\CampaignEventFormFieldValueType;
use Mautic\FormBundle\Form\Type\CampaignEventFormSubmitType;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;

final readonly class CampaignSubscriber
{
    public function __construct(
        private FormModel $formModel,
        private RealTimeExecutioner $realTimeExecutioner,
        private FormFieldHelper $formFieldHelper,
        private FormRepository $formRepository,
        private SubmissionRepository $submissionRepository,
    ) {
    }

    /**
     * Add the option to the list.
     */
    #[AsEventListener(event: CampaignBuilderEvent::class)]
    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $trigger = [
            'label'       => 'mautic.form.campaign.event.submit',
            'description' => 'mautic.form.campaign.event.submit_descr',
            'formType'    => CampaignEventFormSubmitType::class,
            'eventName'   => FormEvents::ON_CAMPAIGN_TRIGGER_DECISION,
        ];
        $event->addDecision('form.submit', $trigger);

        $trigger = [
            'label'       => 'mautic.form.campaign.event.field_value',
            'description' => 'mautic.form.campaign.event.field_value_descr',
            'formType'    => CampaignEventFormFieldValueType::class,
            'formTheme'   => '@MauticForm/FormTheme/FieldValueCondition/_campaignevent_form_field_value_widget.html.twig',
            'eventName'   => FormEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
        ];
        $event->addCondition('form.field_value', $trigger);
    }

    /**
     * Trigger campaign event for when a form is submitted.
     */
    #[AsEventListener(event: SubmissionEvent::class)]
    public function onFormSubmit(SubmissionEvent $event): void
    {
        $form = $event->getSubmission()->getForm();
        $this->realTimeExecutioner->execute('form.submit', $form, 'form', $form->getId());
    }

    #[AsEventListener(event: FormEvents::ON_CAMPAIGN_TRIGGER_DECISION)]
    public function onCampaignTriggerDecision(DecisionEvent $event): void
    {
        $eventDetails = $event->getEventDetails();

        if (null === $eventDetails) {
            $event->setAsApplicable();

            return;
        }

        if (!$eventDetails instanceof Form) {
            return;
        }

        $limitToForms = $event->getConfig()['forms'];

        // check against selected forms
        if (!empty($limitToForms) && !in_array($eventDetails->getId(), $limitToForms)) {
            return;
        }

        $event->setAsApplicable();
    }

    #[AsEventListener(event: FormEvents::ON_CAMPAIGN_TRIGGER_CONDITION)]
    public function onCampaignTriggerCondition(ConditionEvent $event): void
    {
        /** @var Lead|null $lead */
        $lead = $event->getLead();

        if (!$lead || !$lead->getId()) {
            $event->fail();

            return;
        }

        $operators = $this->formModel->getFilterExpressionFunctions();
        $form      = $this->formRepository->findOneById($event->getConfig()['form']);

        if (!$form || !$form->getId()) {
            $event->fail();

            return;
        }

        $field = $this->formModel->findFormFieldByAlias($form, $event->getConfig()['field']);

        $filter = $this->formFieldHelper->getFieldFilter($field->getType());
        $value  = InputHelper::_($event->getConfig()['value'], $filter);

        $result = $this->submissionRepository->compareValue(
            $lead->getId(),
            $form->getId(),
            $form->getAlias(),
            $event->getConfig()['field'],
            $value,
            $operators[$event->getConfig()['operator']]['expr'],
            $field ? $field->getType() : null
        );

        $event->setChannel('form', $form->getId());

        if ($result) {
            $event->pass();
        } else {
            $event->fail();
        }
    }
}
