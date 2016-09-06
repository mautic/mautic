<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Helper;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class FormFieldHelper
 */
class FormFieldHelper
{

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ValidatorInterface|\Symfony\Component\Validator\ValidatorInterface
     */
    private $validator;

    /**
     * @var array
     */
    private $types = [
        'text'        => [],
        'textarea'    => [],
        'country'     => [],
        //'button'  => [],
        'select'      => [],
        'date'        => [],
        'email'       => [
            'filter'      => 'email',
            'constraints' => [
                '\Symfony\Component\Validator\Constraints\Email' =>
                    ['message' => 'mautic.form.submission.email.invalid'],
            ],
        ],
        'number'      => [
            'filter' => 'float',
        ],
        'tel'         => [],
        'url'         => [
            'filter'      => 'url',
            'constraints' => [
                '\Symfony\Component\Validator\Constraints\Url' =>
                    ['message' => 'mautic.form.submission.url.invalid'],
            ],
        ],
        'freetext'    => [],
        'checkboxgrp' => [],
        'radiogrp'    => [],
        'hidden'      => [],
        'captcha'     => [
            'constraints' => [
                '\Symfony\Component\Validator\Constraints\NotBlank' =>
                    ['message' => 'mautic.form.submission.captcha.invalid'],

                '\Symfony\Component\Validator\Constraints\EqualTo' =>
                    ['message' => 'mautic.form.submission.captcha.invalid'],
            ],
        ],
        'pagebreak'   => [],
    ];

    /**
     * FormFieldHelper constructor.
     *
     * @param TranslatorInterface $translator
     * @param ValidatorInterface  $validator
     */
    public function __construct(TranslatorInterface $translator, ValidatorInterface $validator = null)
    {
        $this->translator = $translator;

        if (null === $validator) {
            $validator = $validator = Validation::createValidator();
        }
        $this->validator = $validator;
    }

    /**
     * @param array $customFields
     *
     * @return array
     */
    public function getList($customFields = [])
    {
        $choices = [];

        foreach ($this->types as $v => $type) {
            $choices[$v] = $this->translator->transConditional("mautic.core.type.{$v}", "mautic.form.field.type.{$v}");
        }

        foreach ($customFields as $v => $f) {
            $choices[$v] = $this->translator->trans($f['label']);
        }

        natcasesort($choices);

        return $choices;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Get fields input filter
     *
     * @param $type
     *
     * @return string
     */
    public function getFieldFilter($type)
    {
        if (array_key_exists($type, $this->types)) {
            if (isset($this->types[$type]['filter'])) {
                return $this->types[$type]['filter'];
            }

            return 'clean';
        }

        return 'alphanum';
    }

    /**
     * @param      $type
     * @param      $value
     * @param null $f
     *
     * @return array
     */
    public function validateFieldValue($type, $value, $f = null)
    {
        $errors = [];
        if (isset($this->types[$type]['constraints'])) {
            foreach ($this->types[$type]['constraints'] as $constraint => $opts) {
                //don't check empty values unless the constraint is NotBlank
                if (strpos($constraint, 'NotBlank') === false && empty($value)) {
                    continue;
                }

                if ($type == 'captcha' && strpos($constraint, 'EqualTo') !== false) {
                    $props         = $f->getProperties();
                    $opts['value'] = $props['captcha'];
                }

                /** @var ConstraintViolationList $violations */
                $violations = $this->validator->validate($value, new $constraint($opts));

                if (count($violations)) {
                    /** @var ConstraintViolation $v */
                    foreach ($violations as $v) {
                        $transParameters = $v->getParameters();

                        if ($f !== null) {
                            $transParameters['%label%'] = "&quot;".$f->getLabel()."&quot;";
                        }

                        $errors[] = $this->translator->trans($v->getMessage(), $transParameters, 'validators');
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * @param $field
     * @param $value
     * @param $formName
     * @param $formHtml
     */
    public function populateField($field, $value, $formName, &$formHtml)
    {
        $alias = $field->getAlias();

        switch ($field->getType()) {
            case 'text':
            case 'email':
            case 'hidden':
                if (preg_match('/<input(.*?)id="mauticform_input_'.$formName.'_'.$alias.'"(.*?)value="(.*?)"(.*?)\/>/i', $formHtml, $match)) {
                    $replace  = '<input'.$match[1].'id="mauticform_input_'.$formName.'_'.$alias.'"'.$match[2].'value="'.urldecode($value).'"'
                        .$match[4].'/>';
                    $formHtml = str_replace($match[0], $replace, $formHtml);
                }
                break;
            case 'textarea':
                if (preg_match('/<textarea(.*?)id="mauticform_input_'.$formName.'_'.$alias.'"(.*?)>(.*?)<\/textarea>/i', $formHtml, $match)) {
                    $replace  = '<textarea'.$match[1].'id="mauticform_input_'.$formName.'_'.$alias.'"'.$match[2].'>'.urldecode($value).'</textarea>';
                    $formHtml = str_replace($match[0], $replace, $formHtml);
                }
                break;
            case 'checkboxgrp':
                if (!is_array($value)) {
                    $value = [$value];
                }
                foreach ($value as $val) {
                    $val = urldecode($val);
                    if (preg_match(
                        '/<input(.*?)id="mauticform_checkboxgrp_checkbox(.*?)"(.*?)value="'.$val.'"(.*?)\/>/i',
                        $formHtml,
                        $match
                    )) {
                        $replace  = '<input'.$match[1].'id="mauticform_checkboxgrp_checkbox'.$match[2].'"'.$match[3].'value="'.$val.'"'
                            .$match[4].' checked />';
                        $formHtml = str_replace($match[0], $replace, $formHtml);
                    }
                }
                break;
            case 'radiogrp':
                $value = urldecode($value);
                if (preg_match('/<input(.*?)id="mauticform_radiogrp_radio(.*?)"(.*?)value="'.$value.'"(.*?)\/>/i', $formHtml, $match)) {
                    $replace  = '<input'.$match[1].'id="mauticform_radiogrp_radio'.$match[2].'"'.$match[3].'value="'.$value.'"'.$match[4]
                        .' checked />';
                    $formHtml = str_replace($match[0], $replace, $formHtml);
                }
                break;
        }
    }
}
