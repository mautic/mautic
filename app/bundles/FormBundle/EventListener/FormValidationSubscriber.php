<?php

namespace Mautic\FormBundle\EventListener;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\Form\Type\FormFieldCheckboxGroupType;
use Mautic\FormBundle\Form\Type\FormFieldEmailType;
use Mautic\FormBundle\Form\Type\FormFieldTelType;
use Mautic\FormBundle\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormValidationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::FORM_ON_BUILD    => ['onFormBuilder', 0],
            FormEvents::ON_FORM_VALIDATE => ['onFormValidate', 0],
        ];
    }

    /**
     * Add a simple email form.
     */
    public function onFormBuilder(Events\FormBuilderEvent $event): void
    {
        $event->addValidator(
            'phone.validation',
            [
                'eventName' => FormEvents::ON_FORM_VALIDATE,
                'fieldType' => 'tel',
                'formType'  => FormFieldTelType::class,
            ]
        );

        if (!empty($this->coreParametersHelper->get('do_not_submit_emails'))) {
            $event->addValidator(
                'email.validation',
                [
                    'eventName' => FormEvents::ON_FORM_VALIDATE,
                    'fieldType' => 'email',
                    'formType'  => FormFieldEmailType::class,
                ]
            );
        }

        $event->addValidator(
            'checkboxgrp.validation',
            [
                'eventName' => FormEvents::ON_FORM_VALIDATE,
                'fieldType' => 'checkboxgrp',
                'formType'  => FormFieldCheckboxGroupType::class,
            ]
        );
    }

    /**
     * Custom validation.
     */
    public function onFormValidate(Events\ValidationEvent $event): void
    {
        $value = $event->getValue();

        if (!empty($value)) {
            $this->fieldTelValidation($event);
            $this->fieldEmailValidation($event);
        }

        $this->fieldCheckboxGroupValidation($event);
    }

    private function fieldEmailValidation(Events\ValidationEvent $event): void
    {
        $field = $event->getField();
        $value = $event->getValue();
        if ('email' === $field->getType() && !empty($field->getValidation()['donotsubmit'])) {
            // Check the domains using shell wildcard patterns
            $donotSubmitFilter  = fn ($doNotSubmitArray): bool => fnmatch($doNotSubmitArray, $value, FNM_CASEFOLD);
            $notNotSubmitEmails = $this->coreParametersHelper->get('do_not_submit_emails');
            if (array_filter($notNotSubmitEmails, $donotSubmitFilter)) {
                $event->failedValidation(ArrayHelper::getValue('donotsubmit_validationmsg', $field->getValidation()));
            }
        }
    }

    private function fieldTelValidation(Events\ValidationEvent $event): void
    {
        $field = $event->getField();
        $value = $event->getValue();

        if ('tel' === $field->getType() && !empty($field->getValidation()['international'])) {
            $phoneUtil = PhoneNumberUtil::getInstance();
            try {
                $phoneUtil->parse($value, PhoneNumberUtil::UNKNOWN_REGION);
            } catch (NumberParseException) {
                if (!empty($field->getValidation()['international_validationmsg'])) {
                    $event->failedValidation($field->getValidation()['international_validationmsg']);
                } else {
                    $event->failedValidation($this->translator->trans('mautic.form.submission.phone.invalid', [], 'validators'));
                }
            }
        }
    }

    private function fieldCheckboxGroupValidation(Events\ValidationEvent $event): void
    {
        $field = $event->getField();
        if ('checkboxgrp' !== $field->getType()) {
            return;
        }

        $value       = $event->getValue();
        $selectedCnt = 0;

        if (!is_array($value)) {
            $value = [$value];
        }

        foreach ($value as $v) {
            if ('' !== $v && null !== $v) {
                ++$selectedCnt;
            }
        }

        $validation = $field->getValidation();

        if (!empty($validation['minimum']) && $selectedCnt < (int) $validation['minimum']) {
            $message = !empty($validation['min_message'])
                ? $validation['min_message']
                : $this->translator->trans(
                    'mautic.form.submission.checkboxgrp.minimum',
                    ['%min%' => (int) $validation['minimum']],
                    'validators'
                );

            $event->failedValidation($message);

            return;
        }

        if (!empty($validation['maximum']) && $selectedCnt > (int) $validation['maximum']) {
            $message = !empty($validation['max_message'])
                ? $validation['max_message']
                : $this->translator->trans(
                    'mautic.form.submission.checkboxgrp.maximum',
                    ['%max%' => (int) $validation['maximum']],
                    'validators'
                );

            $event->failedValidation($message);
        }
    }
}
