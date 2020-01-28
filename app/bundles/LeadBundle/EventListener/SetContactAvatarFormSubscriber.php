<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\Form\Type\FormFieldFileType;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Templating\Helper\AvatarHelper;

class SetContactAvatarFormSubscriber extends CommonSubscriber
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

    /**
     * SetContactAvatarFormSubscriber constructor.
     *
     * @param AvatarHelper $avatarHelper
     * @param FormUploader $uploader
     * @param LeadModel    $leadModel
     */
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
