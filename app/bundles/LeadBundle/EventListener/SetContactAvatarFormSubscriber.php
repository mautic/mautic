<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\Form\Type\FormFieldFileType;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Twig\Helper\AvatarHelper;

final readonly class SetContactAvatarFormSubscriber
{
    public function __construct(
        private AvatarHelper $avatarHelper,
        private FormUploader $uploader,
        private LeadModel $leadModel,
    ) {
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: SubmissionEvent::class,
        priority: 0
    )]
    public function onFormSubmit(SubmissionEvent $submissionEvent): void
    {
        $fields  = $submissionEvent->getForm()->getFields();
        $contact = $submissionEvent->getLead();
        $results = $submissionEvent->getResults();

        if (!$contact) {
            return;
        }

        /** @var Field $field */
        foreach ($fields as $field) {
            if ('file' === $field->getType()) {
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
                } catch (\Exception) {
                }
            }
        }
    }
}
