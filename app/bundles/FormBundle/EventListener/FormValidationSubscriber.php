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

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Mautic\CoreBundle\Form\Type\TelType;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FormValidationSubscriber implements EventSubscriberInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_ON_BUILD                => ['onFormBuilder', 0],
            FormEvents::ON_FORM_VALIDATE             => ['onFormValidate', 0],
        ];
    }

    /**
     * Add a simple email form.
     */
    public function onFormBuilder(Events\FormBuilderEvent $event)
    {
        $event->addValidator(
            'phone.validation',
            [
                'eventName' => FormEvents::ON_FORM_VALIDATE,
                'fieldType' => TelType::class,
                'formType'  => \Mautic\FormBundle\Form\Type\FormFieldTelType::class,
            ]
        );
    }

    /**
     * Custom validation.
     */
    public function onFormValidate(Events\ValidationEvent $event)
    {
        $field = $event->getField();
        $value = $event->getValue();
        if (!empty($value) && 'tel' === $field->getType() && !empty($field->getValidation()['international'])) {
            $phoneUtil = PhoneNumberUtil::getInstance();
            try {
                $phoneUtil->parse($value, PhoneNumberUtil::UNKNOWN_REGION);
            } catch (NumberParseException $e) {
                if (!empty($field->getValidation()['international_validationmsg'])) {
                    $event->failedValidation($field->getValidation()['international_validationmsg']);
                } else {
                    $event->failedValidation($this->translator->trans('mautic.form.submission.phone.invalid', [], 'validators'));
                }
            }
        }
    }
}
