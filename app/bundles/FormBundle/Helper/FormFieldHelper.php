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
use Symfony\Component\Validator\Validation;

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
     * @var array
     */
    private $types = array(
        'text' => array(
            'label' => 'mautic.form.field.type.text'
        ),
        'textarea' => array(
            'label' => 'mautic.form.field.type.textarea'
        ),
        'country'  => array(
            'label' => 'mautic.form.field.type.country'
        ),
        'button'  => array(
            'label' => 'mautic.form.field.type.button'
        ),
        'select' => array(
            'label' => 'mautic.form.field.type.select'
        ),
        'date' => array(
            'label' => 'mautic.form.field.type.date'
        ),
        'email' => array(
            'label'  => 'mautic.form.field.type.email',
            'filter' => 'email',
            'constraints' => array(
                '\Symfony\Component\Validator\Constraints\Email' =>
                    array('message' => 'mautic.form.submission.email.invalid')
            )
        ),
        'number' => array(
            'label'  => 'mautic.form.field.type.number',
            'filter' => 'float'
        ),
        'tel' => array(
            'label' => 'mautic.form.field.type.tel'
        ),
        'url' => array(
            'label'  => 'mautic.form.field.type.url',
            'filter' => 'url',
            'constraints' => array(
                '\Symfony\Component\Validator\Constraints\Url' =>
                    array('message' => 'mautic.form.submission.url.invalid')
            )
        ),
        'freetext' => array(
            'label' => 'mautic.form.field.type.freetext'
        ),
        'checkboxgrp' => array(
            'label' => 'mautic.form.field.type.checkboxgrp'
        ),
        'radiogrp' => array(
            'label' => 'mautic.form.field.type.radiogrp'
        ),
        'hidden' => array(
            'label' => 'mautic.form.field.type.hidden'
        ),
        'captcha' => array(
            'label' => 'mautic.form.field.type.captcha',
            'constraints' => array(
                '\Symfony\Component\Validator\Constraints\NotBlank' =>
                    array('message' => 'mautic.form.submission.captcha.invalid'),

                '\Symfony\Component\Validator\Constraints\EqualTo' =>
                    array('message' => 'mautic.form.submission.captcha.invalid')
            )
        )
    );

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param array $customFields
     *
     * @return array
     */
    public function getList($customFields = array())
    {
        $choices = array();

        foreach ($this->types as $v => $type) {
            $choices[$v] = $this->translator->trans($type['label']);
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
        $errors = array();
        if (isset($this->types[$type]['constraints'])) {
            $validator = Validation::createValidator();

            foreach ($this->types[$type]['constraints'] as $constraint => $opts) {
                //don't check empty values unless the constraint is NotBlank
                if (strpos($constraint, 'NotBlank') === false && empty($value))
                    continue;

                if ($type == 'captcha' && strpos($constraint, 'EqualTo') !== false) {
                    $props = $f->getProperties();
                    $opts['value'] = $props['captcha'];
                }

                $violations = $validator->validateValue($value, new $constraint($opts));

                if (count($violations)) {
                    foreach ($violations as $v) {
                        $errors[] = $this->translator->trans($v->getMessage(), $v->getMessageParameters(), 'validators');
                    }
                }
            }
        }

        return $errors;
    }
}
