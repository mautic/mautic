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
        'text' => array(),
        'textarea' => array(),
        'country'  => array(),
        //'button'  => array(),
        'select' => array(),
        'date' => array(),
        'email' => array(
            'filter' => 'email',
            'constraints' => array(
                '\Symfony\Component\Validator\Constraints\Email' =>
                    array('message' => 'mautic.form.submission.email.invalid')
            )
        ),
        'number' => array(
            'filter' => 'float'
        ),
        'tel' => array(),
        'url' => array(
            'filter' => 'url',
            'constraints' => array(
                '\Symfony\Component\Validator\Constraints\Url' =>
                    array('message' => 'mautic.form.submission.url.invalid')
            )
        ),
        'freetext' => array(),
        'checkboxgrp' => array(),
        'radiogrp' => array(),
        'hidden' => array(),
        'captcha' => array(
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
                        $transParameters = $v->getMessageParameters();

                        if ($f !== null) {
                            $transParameters['%label%'] = "&quot;" . $f->getLabel() . "&quot;";
                        }

                        $errors[] = $this->translator->trans($v->getMessage(), $transParameters, 'validators');
                    }
                }
            }
        }

        return $errors;
    }
}
