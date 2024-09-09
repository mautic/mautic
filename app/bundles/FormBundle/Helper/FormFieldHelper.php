<?php

namespace Mautic\FormBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractFormFieldHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FormFieldHelper extends AbstractFormFieldHelper
{
    private ?ValidatorInterface $validator;

    private array $types = [
        'captcha' => [
            'constraints' => [
                NotBlank::class => ['message' => 'mautic.form.submission.captcha.invalid'],

                EqualTo::class => ['message' => 'mautic.form.submission.captcha.invalid'],

                Blank::class => ['message' => 'mautic.form.submission.captcha.invalid'],
            ],
        ],
        'checkboxgrp' => [],
        'country'     => [],
        'date'        => [],
        'datetime'    => [],
        'email'       => [
            'filter'      => 'email',
            'constraints' => [
                Email::class => ['message' => 'mautic.form.submission.email.invalid'],
            ],
        ],
        'freetext'      => [],
        'freehtml'      => [],
        'hidden'        => [],
        'companyLookup' => [],
        'number'        => [
            'filter' => 'float',
        ],
        'pagebreak' => [],
        'password'  => [],
        'radiogrp'  => [],
        'select'    => [],
        'tel'       => [],
        'text'      => [],
        'textarea'  => [],
        'url'       => [
            'filter'      => 'url',
            'constraints' => [
                Url::class => ['message' => 'mautic.form.submission.url.invalid'],
            ],
        ],
        'file' => [],
    ];

    public function __construct(Translator $translator, ValidatorInterface $validator = null)
    {
        $this->translator = $translator;

        if (null === $validator) {
            $validator = $validator = Validation::createValidator();
        }
        $this->validator = $validator;

        parent::__construct();
    }

    /**
     * Set the translation key prefix.
     */
    public function setTranslationKeyPrefix(): void
    {
        $this->translationKeyPrefix = 'mautic.form.field.type.';
    }

    /**
     * @param array $customFields
     *
     * @deprecated  to be removed in 3.0; use getChoiceList($customFields = []) instead
     *
     * @return array
     */
    public function getList($customFields = [])
    {
        return $this->getChoiceList($customFields);
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Get fields input filter.
     *
     * @return string
     */
    public function getFieldFilter($type)
    {
        if (array_key_exists($type, $this->types)) {
            return $this->types[$type]['filter'] ?? 'clean';
        }

        return 'alphanum';
    }

    /**
     * @param Field $f
     */
    public function validateFieldValue($type, $value, $f = null): array
    {
        $errors = [];
        if (isset($this->types[$type]['constraints'])) {
            foreach ($this->types[$type]['constraints'] as $constraint => $opts) {
                // don't check empty values unless the constraint is NotBlank
                if (NotBlank::class === $constraint && empty($value)) {
                    continue;
                }

                if ('captcha' == $type) {
                    $captcha = $f->getProperties()['captcha'];
                    if (empty($captcha) && Blank::class !== $constraint) {
                        // Used as a honeypot
                        $captcha = '';
                    } elseif (Blank::class === $constraint) {
                        continue;
                    }

                    if (EqualTo::class == $constraint) {
                        $opts['value'] = $captcha;
                    }
                }

                /** @var ConstraintViolationList $violations */
                $violations = $this->validator->validate($value, new $constraint($opts));

                if (count($violations)) {
                    /** @var ConstraintViolation $v */
                    foreach ($violations as $v) {
                        $transParameters = $v->getParameters();

                        if (null !== $f) {
                            $transParameters['%label%'] = '&quot;'.$f->getLabel().'&quot;';
                        }

                        $errors[] = $this->translator->trans($v->getMessage(), $transParameters, 'validators');
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Replaces strings the PHP date string parser understands with
     * the actual time in date, datetime and hidden fields.
     *
     * Hidden fields must end with `|date` to prevent replacing
     * strings by a date time representation by accident.
     */
    public function populateDateTimeValues(Form $form, string $formHtml): string
    {
        $formName       = $form->generateFormName();
        $fields         = $form->getFields();
        $autoFillFields = array_filter($fields->toArray(), fn ($field) => in_array($field->getType(), ['date', 'datetime', 'hidden']));

        foreach ($autoFillFields as $field) {
            $fieldType      = $field->getType();
            $dateTimeString = $field->getDefaultValue();

            switch ($fieldType) {
                case 'date':
                    $format = 'Y-m-d';
                    break;
                case 'datetime':
                case 'hidden':
                    $format = 'Y-m-d\TH:i';
                    break;

                default:
                    continue 2;
            }

            // prevent empty fields from getting parsed as now
            if (empty(trim($dateTimeString))) {
                continue;
            }

            if ('hidden' === $fieldType) {
                /* $pattern:
                        ^([^\|]+) => match any char, except pipe
                        \|date    => then look for `|date`
                */
                preg_match('/^([^\|]+)\|date$/', $dateTimeString, $matches);

                // prevent accidental parsing of hidden input values as date
                if (empty($matches)) {
                    continue;
                }

                $dateTimeString = $matches[1];
            }

            // Ensure we fail gracefully, which means the default value remains unchanged
            try {
                $value = (new \DateTime(trim($dateTimeString)))->format($format);
            } catch (\Exception) {
                continue;
            }

            $this->populateField($field, $value, $formName, $formHtml);
        }

        return $formHtml;
    }

    /**
     * Search and replace the HTML of the form field with the value.
     */
    public function populateField($field, $value, $formName, &$formHtml): void
    {
        $alias = $field->getAlias();

        switch ($field->getType()) {
            case 'text':
            case 'number':
            case 'email':
            case 'hidden':
            case 'tel':
            case 'url':
            case 'date':
            case 'datetime':
                if ('tel' === $field->getType()) {
                    $sanitizedValue = InputHelper::clean($value);
                } else {
                    $sanitizedValue = $this->sanitizeValue($value);
                }
                if (preg_match('/<input(.*?)value="(.*?)"(.*?)id="mauticform_input_'.$formName.'_'.$alias.'"(.*?)\/?>/i', $formHtml, $match)) {
                    $replace = '<input'.$match[1].'id="mauticform_input_'.$formName.'_'.$alias.'"'.$match[3].'value="'.$sanitizedValue.'"'
                        .$match[4].'/>';
                    $formHtml = str_replace($match[0], $replace, $formHtml);
                }
                break;
            case 'textarea':
                if (preg_match('/<textarea(.*?)id="mauticform_input_'.$formName.'_'.$alias.'"(.*?)>(.*?)<\/textarea>/i', $formHtml, $match)) {
                    $replace  = '<textarea'.$match[1].'id="mauticform_input_'.$formName.'_'.$alias.'"'.$match[2].'>'.$this->sanitizeValue($value).'</textarea>';
                    $formHtml = str_replace($match[0], $replace, $formHtml);
                }
                break;
            case 'checkboxgrp':
                $separator = urlencode('|');
                if (is_string($value) && strrpos($value, $separator) > 0) {
                    $value = explode($separator, $value);
                } elseif (!is_array($value)) {
                    $value = [$value];
                }

                foreach ($value as $val) {
                    $val = $this->sanitizeValue($val);
                    if (preg_match(
                        '/<input(.*?)id="mauticform_checkboxgrp_checkbox_'.$alias.'(.*?)"(.*?)value="'.$val.'"(.*?)\/?>/i',
                        $formHtml,
                        $match
                    )) {
                        $replace = '<input'.$match[1].'id="mauticform_checkboxgrp_checkbox_'.$alias.$match[2].'"'.$match[3].'value="'.$val.'"'
                            .$match[4].' checked />';
                        $formHtml = str_replace($match[0], $replace, $formHtml);
                    }
                }
                break;
            case 'radiogrp':
                $value = $this->sanitizeValue($value);
                if (preg_match('/<input(.*?)id="mauticform_radiogrp_radio_'.$alias.'(.*?)"(.*?)value="'.$value.'"(.*?)\/?>/i', $formHtml, $match)) {
                    $replace = '<input'.$match[1].'id="mauticform_radiogrp_radio_'.$alias.$match[2].'"'.$match[3].'value="'.$value.'"'.$match[4]
                        .' checked />';
                    $formHtml = str_replace($match[0], $replace, $formHtml);
                }
                break;
            case 'select':
            case 'country':
                $regex = '/<select\s*id="mauticform_input_'.$formName.'_'.$alias.'"(.*?)<\/select>/is';
                if (preg_match($regex, $formHtml, $match)) {
                    $origText = $match[0];
                    $replace  = str_replace(
                        '<option value="'.$this->sanitizeValue($value).'">',
                        '<option value="'.$this->sanitizeValue($value).'" selected="selected">',
                        $origText
                    );
                    $formHtml = str_replace($origText, $replace, $formHtml);
                }

                break;
        }
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function sanitizeValue($value)
    {
        $valueType = gettype($value);
        $value     = str_replace(['"', '>', '<'], ['&quot;', '&gt;', '&lt;'], strip_tags(rawurldecode($value)));
        // for boolean expect 0 or 1
        if ('boolean' === $valueType) {
            return (int) $value;
        }

        return $value;
    }
}
