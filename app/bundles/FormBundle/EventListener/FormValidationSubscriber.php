<?php

namespace Mautic\FormBundle\EventListener;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\Form\Type\FormFieldCheckboxGroupType;
use Mautic\FormBundle\Form\Type\FormFieldEmailType;
use Mautic\FormBundle\Form\Type\FormFieldTelType;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Helper\PhoneCountryValidationHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class FormValidationSubscriber implements EventSubscriberInterface
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

        if (!empty($this->coreParametersHelper->get('do_not_submit_emails'))
            || !empty($this->coreParametersHelper->get('blocked_free_email_providers'))
        ) {
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

        if ('email' !== $field->getType()) {
            return;
        }

        if (!empty($field->getValidation()['donotsubmit'])) {
            // Check the domains using shell wildcard patterns
            $donotSubmitFilter  = fn ($doNotSubmitArray): bool => fnmatch($doNotSubmitArray, $value, FNM_CASEFOLD);
            $notNotSubmitEmails = $this->coreParametersHelper->get('do_not_submit_emails');
            if (array_filter($notNotSubmitEmails, $donotSubmitFilter)) {
                $validationMsg = $field->getValidation()['donotsubmit_validationmsg'] ?? $this->translator->trans('mautic.form.submission.email.donotsubmit.invalid', [], 'validators');
                $event->failedValidation($validationMsg);
            }
        }

        if (!empty($field->getValidation()['blockfreeemail'])) {
            $blockedProviders = $this->coreParametersHelper->get('blocked_free_email_providers') ?? [];
            $domain           = strtolower(substr(strrchr($value, '@'), 1));
            if ($domain && in_array($domain, $blockedProviders, true)) {
                $validationMsg = $field->getValidation()['blockfreeemail_validationmsg'] ?? $this->translator->trans('mautic.form.submission.email.freeproviders.invalid', [], 'validators');
                $event->failedValidation($validationMsg);
            }
        }
    }

    private function fieldTelValidation(Events\ValidationEvent $event): void
    {
        $field = $event->getField();

        if ('tel' !== $field->getType()) {
            return;
        }

        $validation = $field->getValidation();

        if ($this->validatePhoneCountry($event, $validation)) {
            return;
        }

        $this->validateInternationalPhone($event, $validation);
    }

    /**
     * @param array<string, mixed> $validation
     */
    private function validatePhoneCountry(Events\ValidationEvent $event, array $validation): bool
    {
        if (empty($validation['country'])) {
            return false;
        }

        $countryCode = (string) $validation['country'];

        // Unknown/legacy value (e.g. a display name saved before this stored the ISO code):
        // skip validation rather than crash or reject every submission.
        if (!Countries::exists($countryCode)) {
            return false;
        }

        if (PhoneCountryValidationHelper::isValidForCountry((string) $event->getValue(), $countryCode)) {
            return false;
        }

        $message = $validation['country_validationmsg'] ?? $this->translator->trans('mautic.form.submission.phone.invalid_country', ['%country%' => Countries::getName($countryCode)], 'validators');
        $event->failedValidation($message);

        return true;
    }

    /**
     * @param array<string, mixed> $validation
     */
    private function validateInternationalPhone(Events\ValidationEvent $event, array $validation): void
    {
        if (empty($validation['international'])) {
            return;
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $phoneUtil->parse((string) $event->getValue(), PhoneNumberUtil::UNKNOWN_REGION);
        } catch (NumberParseException) {
            if (!empty($validation['international_validationmsg'])) {
                $event->failedValidation($validation['international_validationmsg']);

                return;
            }

            $event->failedValidation($this->translator->trans('mautic.form.submission.phone.invalid', [], 'validators'));
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
