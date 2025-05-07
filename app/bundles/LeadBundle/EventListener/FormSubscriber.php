<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\FormBundle\Crate\FieldCrate;
use Mautic\FormBundle\Crate\ObjectCrate;
use Mautic\FormBundle\Event\FieldCollectEvent;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\Event\ObjectCollectEvent;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Entity\PointsChangeLog;
use Mautic\LeadBundle\Entity\UtmTag;
use Mautic\LeadBundle\Form\Type\ActionAddUtmTagsType;
use Mautic\LeadBundle\Form\Type\ActionRemoveDoNotContact;
use Mautic\LeadBundle\Form\Type\CompanyChangeScoreActionType;
use Mautic\LeadBundle\Form\Type\FormSubmitActionPointsChangeType;
use Mautic\LeadBundle\Form\Type\ListActionType;
use Mautic\LeadBundle\Form\Type\ModifyLeadTagsType;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PointBundle\Model\PointGroupModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FormSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected LeadModel $leadModel,
        protected ContactTracker $contactTracker,
        protected IpLookupHelper $ipLookupHelper,
        protected LeadFieldRepository $leadFieldRepository,
        private PointGroupModel $groupModel,
        private DoNotContact $doNotContact,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::FORM_ON_BUILD                    => ['onFormBuilder', 0],
            FormEvents::ON_OBJECT_COLLECT                => ['onObjectCollect', 0],
            FormEvents::ON_FIELD_COLLECT                 => ['onFieldCollect', 0],
            LeadEvents::LEAD_ON_SEGMENTS_CHANGE          => ['onLeadSegmentsChange', 0],
            FormEvents::ON_EXECUTE_SUBMIT_ACTION         => [
                ['onFormSubmitActionChangePoints', 0],
                ['onFormSubmitActionChangeList', 1],
                ['onFormSubmitActionChangeTags', 2],
                ['onFormSubmitActionAddUtmTags', 3],
                ['onFormSubmitActionScoreContactsCompanies', 4],
                ['onFormSubmitActionRemoveFromDoNotContact', 5],
            ],
        ];
    }

    /**
     * Add a lead generation action to available form submit actions.
     */
    public function onFormBuilder(FormBuilderEvent $event): void
    {
        $event->addSubmitAction('lead.pointschange', [
            'group'       => 'mautic.lead.lead.submitaction',
            'label'       => 'mautic.lead.lead.submitaction.changepoints',
            'description' => 'mautic.lead.lead.submitaction.changepoints_descr',
            'formType'    => FormSubmitActionPointsChangeType::class,
            'formTheme'   => '@MauticLead/FormTheme/FormActionChangePoints/_formaction_properties_row.html.twig',
            'eventName'   => FormEvents::ON_EXECUTE_SUBMIT_ACTION,
            'template'    => '@MauticLead/Action/points.html.twig',
        ]);

        $event->addSubmitAction('lead.changelist', [
            'group'       => 'mautic.lead.lead.submitaction',
            'label'       => 'mautic.lead.lead.events.changelist',
            'description' => 'mautic.lead.lead.events.changelist_descr',
            'formType'    => ListActionType::class,
            'eventName'   => FormEvents::ON_EXECUTE_SUBMIT_ACTION,
            'template'    => '@MauticLead/Action/segments.html.twig',
        ]);

        $event->addSubmitAction('lead.changetags', [
            'group'             => 'mautic.lead.lead.submitaction',
            'label'             => 'mautic.lead.lead.events.changetags',
            'description'       => 'mautic.lead.lead.events.changetags_descr',
            'formType'          => ModifyLeadTagsType::class,
            'eventName'         => FormEvents::ON_EXECUTE_SUBMIT_ACTION,
            'allowCampaignForm' => true,
            'template'          => '@MauticLead/Action/tags.html.twig',
        ]);

        $event->addSubmitAction('lead.addutmtags', [
            'group'             => 'mautic.lead.lead.submitaction',
            'label'             => 'mautic.lead.lead.events.addutmtags',
            'description'       => 'mautic.lead.lead.events.addutmtags_descr',
            'formType'          => ActionAddUtmTagsType::class,
            'formTheme'         => '@MauticLead/FormTheme/FormActionAddUtmTags/_formaction_properties_row.html.twig',
            'eventName'         => FormEvents::ON_EXECUTE_SUBMIT_ACTION,
            'allowCampaignForm' => true,
        ]);

        $event->addSubmitAction('lead.remove_do_not_contact', [
            'group'             => 'mautic.lead.lead.submitaction',
            'label'             => 'mautic.lead.lead.events.removedonotcontact',
            'description'       => 'mautic.lead.lead.events.removedonotcontact_descr',
            'formType'          => ActionRemoveDoNotContact::class,
            'eventName'         => FormEvents::ON_EXECUTE_SUBMIT_ACTION,
            'allowCampaignForm' => true,
        ]);

        $event->addSubmitAction('lead.scorecontactscompanies', [
            'group'       => 'mautic.lead.lead.submitaction',
            'label'       => 'mautic.lead.lead.events.changecompanyscore',
            'description' => 'mautic.lead.lead.events.changecompanyscore_descr',
            'formType'    => CompanyChangeScoreActionType::class,
            'eventName'   => FormEvents::ON_EXECUTE_SUBMIT_ACTION,
            'template'    => '@MauticLead/Action/points.html.twig',
        ]);
    }

    public function onObjectCollect(ObjectCollectEvent $event): void
    {
        $event->appendObject(new ObjectCrate('contact', 'mautic.lead.contact'));
        $event->appendObject(new ObjectCrate('company', 'mautic.core.company'));
    }

    public function onFieldCollect(FieldCollectEvent $event): void
    {
        $object = 'contact' === $event->getObject() ? 'lead' : $event->getObject(); // BC conversion.
        $fields = $this->leadFieldRepository->getFieldsForObject($object);

        foreach ($fields as $field) {
            $event->appendField(
                new FieldCrate(
                    $field->getAlias(),
                    $field->getLabel(),
                    $field->getType(),
                    $field->getProperties()
                )
            );
        }
    }

    public function onFormSubmitActionChangePoints(SubmissionEvent $event): void
    {
        if (false === $event->checkContext('lead.pointschange')) {
            return;
        }

        if (!$contact = $this->contactTracker->getContact()) {
            return;
        }

        $form = $event->getSubmission()->getForm();

        $pointsChangeLog = new PointsChangeLog();
        $pointsChangeLog->setType('form');
        $pointsChangeLog->setEventName($form->getId().':'.$form->getName());
        $pointsChangeLog->setActionName($event->getAction()->getName());
        $pointsChangeLog->setIpAddress($this->ipLookupHelper->getIpAddress());
        $pointsChangeLog->setDateAdded(new \DateTime());
        $pointsChangeLog->setLead($contact);

        $oldPoints  = $contact->getPoints();
        $properties = $event->getActionConfig();

        $operator     = $properties['operator'];
        $pointGroupId = $properties['group'] ?? null;
        $pointGroup   = $pointGroupId ? $this->groupModel->getEntity($pointGroupId) : null;
        $points       = $properties['points'];

        if (!empty($pointGroup)) {
            $this->groupModel->adjustPoints($contact, $pointGroup, $points, $operator);
        } else {
            $contact->adjustPoints($points, $operator);
        }

        $newPoints = $contact->getPoints();

        $pointsChangeLog->setDelta($newPoints - $oldPoints);
        $contact->addPointsChangeLog($pointsChangeLog);

        $this->leadModel->saveEntity($contact, false);

        $event->getSubmission()->getLead()->setPoints($contact->getPoints());
    }

    public function onFormSubmitActionChangeList(SubmissionEvent $event): void
    {
        if (false === $event->checkContext('lead.changelist')) {
            return;
        }

        if (!$contact = $this->contactTracker->getContact()) {
            return;
        }

        $properties = $event->getAction()->getProperties();
        $addTo      = $properties['addToLists'] ?? null;
        $removeFrom = $properties['removeFromLists'] ?? null;

        if (!empty($addTo)) {
            $this->leadModel->addToLists($contact, $addTo);
        }

        if (!empty($removeFrom)) {
            $this->leadModel->removeFromLists($contact, $removeFrom);
        }
    }

    public function onFormSubmitActionChangeTags(SubmissionEvent $event): void
    {
        if (false === $event->checkContext('lead.changetags')) {
            return;
        }

        if (!$contact = $this->contactTracker->getContact()) {
            return;
        }

        $properties = $event->getAction()->getProperties();
        $addTags    = $properties['add_tags'] ?: [];
        $removeTags = $properties['remove_tags'] ?: [];

        $this->leadModel->modifyTags($contact, $addTags, $removeTags);
    }

    public function onFormSubmitActionAddUtmTags(SubmissionEvent $event): void
    {
        if (false === $event->checkContext('lead.addutmtags')) {
            return;
        }

        if (!$contact = $this->contactTracker->getContact()) {
            return;
        }

        $queryReferer = $queryArray = [];

        parse_str($event->getRequest()->server->get('QUERY_STRING'), $queryArray);
        $refererURL       = $event->getRequest()->server->get('HTTP_REFERER');
        $refererParsedUrl = parse_url($refererURL);

        if (isset($refererParsedUrl['query'])) {
            parse_str($refererParsedUrl['query'], $queryReferer);
        }

        $utmValues = new UtmTag();
        $utmValues->setLead($contact);
        $utmValues->setQuery($event->getRequest()->query->all());
        $utmValues->setReferer($refererURL);
        $utmValues->setUrl($event->getRequest()->server->get('REQUEST_URI'));
        $utmValues->setDateAdded(new \DateTime());
        $utmValues->setRemoteHost($refererParsedUrl['host'] ?? null);
        $utmValues->setUserAgent($event->getRequest()->server->get('HTTP_USER_AGENT') ?? null);
        $utmValues->setUtmCampaign($queryArray['utm_campaign'] ?? $queryReferer['utm_campaign'] ?? null);
        $utmValues->setUtmContent($queryArray['utm_content'] ?? $queryReferer['utm_content'] ?? null);
        $utmValues->setUtmMedium($queryArray['utm_medium'] ?? $queryReferer['utm_medium'] ?? null);
        $utmValues->setUtmSource($queryArray['utm_source'] ?? $queryReferer['utm_source'] ?? null);
        $utmValues->setUtmTerm($queryArray['utm_term'] ?? $queryReferer['utm_term'] ?? null);

        if ($utmValues->hasUtmTags()) {
            $this->leadModel->getUtmTagRepository()->saveEntity($utmValues);
            $this->leadModel->setUtmTags($utmValues->getLead(), $utmValues);
        }
    }

    public function onFormSubmitActionScoreContactsCompanies(SubmissionEvent $event): void
    {
        if (false === $event->checkContext('lead.scorecontactscompanies')) {
            return;
        }

        if (!$contact = $this->contactTracker->getContact()) {
            return;
        }

        $properties = $event->getActionConfig();

        if (!empty($properties['score'])) {
            $this->leadModel->scoreContactsCompany($contact, $properties['score']);
        }
    }

    public function onFormSubmitActionRemoveFromDoNotContact(SubmissionEvent $event): void
    {
        if (false === $event->checkContext('lead.remove_do_not_contact')) {
            return;
        }

        if ($event->getLead()) {
            $this->doNotContact->removeDncForContact($event->getLead()->getId(), 'email');
        }
    }

    public function onLeadSegmentsChange(SubmissionEvent $event): void
    {
        $properties = $event->getActionConfig();

        $lead       = $this->contactTracker->getContact();
        $addTo      = $properties['addToLists'];
        $removeFrom = $properties['removeFromLists'];

        if (!empty($addTo)) {
            $this->leadModel->addToLists($lead, $addTo);
        }

        if (!empty($removeFrom)) {
            $this->leadModel->removeFromLists($lead, $removeFrom);
        }
    }
}
