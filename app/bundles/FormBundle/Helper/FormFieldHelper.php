<?php

namespace Mautic\FormBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractFormFieldHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\Field;
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
    private ?\Symfony\Component\Validator\Validator\ValidatorInterface $validator;

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
        'freetext' => [],
        'freehtml' => [],
        'hidden'   => [],
        'number'   => [
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

    public function populateField($field, $value, $formName, &$formHtml): void
    {
        $alias = $field->getAlias();

        switch ($field->getType()) {
            case 'text':
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
        $value     = str_replace(['"', '>', '<'], ['&quot;', '&gt;', '&lt;'], strip_tags(urldecode($value)));
        // for boolean expect 0 or 1
        if ('boolean' === $valueType) {
            return (int) $value;
        }

        return $value;
    }
}
