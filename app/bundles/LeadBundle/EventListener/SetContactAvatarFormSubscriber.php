<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\Form\Type\FormFieldFileType;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Templating\Helper\AvatarHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SetContactAvatarFormSubscriber implements EventSubscriberInterface
{
    /**
     * @var AvatarHelper
     */
    private $avatarHelper;

    /**
     * @var FormUploader
     */
    private $uploader;

    /**
     * @var LeadModel
     */
    private $leadModel;

    public function __construct(AvatarHelper $avatarHelper, FormUploader $uploader, LeadModel $leadModel)
    {
        $this->avatarHelper = $avatarHelper;
        $this->uploader     = $uploader;
        $this->leadModel    = $leadModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_ON_SUBMIT => ['onFormSubmit', 0],
        ];
    }

    public function onFormSubmit(SubmissionEvent $submissionEvent)
    {
        $fields  = $submissionEvent->getForm()->getFields();
        $contact = $submissionEvent->getLead();
        $results = $submissionEvent->getResults();

        if (!$contact) {
            return;
        }

        /** @var Field $field */
        foreach ($fields as $field) {
            switch ($field->getType()) {
                case 'file':
                    $properties = $field->getProperties();
                    if (empty($properties[FormFieldFileType::PROPERTY_PREFERED_PROFILE_IMAGE])) {
                        break;
                    }
                    if (empty($results[$field->getAlias()])) {
                        break;
                    }
                    try {
                        $filePath = $this->uploader->getCompleteFilePath($field, $results[$field->getAlias()]);
                        $this->avatarHelper->createAvatarFromFile($contact, $filePath);
                        $contact->setPreferredProfileImage('custom');
                        $this->leadModel->saveEntity($contact);

                        return;
                    } catch (\Exception $exception) {
                    }

                    break;
            }
        }
    }
}
