<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\FormEvents;

/**
 * Class FormValidationSubsriber.
 */
class FormValidationSubsriber extends CommonSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_ON_BUILD                => ['onFormBuilder', 0],
            FormEvents::FORM_VALIDATION_TAB_ON_BUILD => ['onValidationBuilder', 0],
            FormEvents::ON_FORM_VALIDATE             => ['onFormValidate', 0],
        ];
    }

    /**
     * Add a simple email form.
     *
     * @param Events\FormBuilderEvent $event
     */
    public function onFormBuilder(Events\FormBuilderEvent $event)
    {
        $event->addValidator(
            'phone.validation',
            [
                'eventName' => FormEvents::ON_FORM_VALIDATE,
                'fieldType' => 'tel',
            ]
        );
    }

    /**
     * @param Events\ValidationEvent $event
     */
    public function onFormValidate(Events\ValidationEvent $event)
    {
        $field = $event->getField();
        if ($field->getType() === 'tel' && !empty($field->getValidation()['international'])) {
            if (empty($field->getValidation()['international_validationmsg'])) {
                $event->failedValidation($field->getValidation()['international_validationmsg']);
            } else {
                $event->failedValidation('mautic.form.submission.phone.invalid');
            }
        }
    }

    /**
     * @param Events\ValidationBuilderEvent $event
     */
    public function onValidationBuilder(Events\ValidationBuilderEvent $event)
    {
        if ($event->getFormField()['type'] == 'tel') {
            $event->setValidator(\Mautic\FormBundle\Form\Type\FormFieldTelType::class);
        }
    }
}
