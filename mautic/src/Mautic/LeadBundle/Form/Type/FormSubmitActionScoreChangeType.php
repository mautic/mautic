<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class FormSubmitActionScoreChangeType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class FormSubmitActionScoreChangeType extends AbstractType
{
    private $factory;

    /**
     * @param MauticFactory       $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->factory    = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('operator', 'choice', array(
            'label'      => 'mautic.lead.lead.submitaction.operator',
            'attr'       => array('class' => 'form-control'),
            'label_attr' => array('class' => 'control-label'),
            'choices' => array(
                'plus'   => 'mautic.lead.lead.submitaction.operator_plus',
                'minus'  => 'mautic.lead.lead.submitaction.operator_minus',
                'times'  => 'mautic.lead.lead.submitaction.operator_times',
                'divide' => 'mautic.lead.lead.submitaction.operator_divide'
            )
        ));

        $default = (empty($options['data']['score'])) ? 0 : (int) $options['data']['score'];
        $builder->add('score', 'number', array(
            'label'      => 'mautic.lead.lead.submitaction.score',
            'attr'       => array('class' => 'form-control'),
            'label_attr' => array('class' => 'control-label'),
            'precision'  => 0,
            'data'       => $default
        ));


        //get a list of fields
        $fields  = $this->factory->getModel('lead.field')->getEntities();
        $choices = array();
        foreach ($fields as $field) {
            $choices[$field->getId()] = $field->getLabel();
        }

        $builder->add('leadField', 'choice', array(
            'choices'    => $choices,
            'label'      => 'mautic.lead.lead.submitaction.leadfield',
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.lead.lead.submitaction.leadfield.help'
            ),
            'label_attr' => array('class' => 'control-label'),
            'multiple'    => false,
            'expanded'    => false,
            'empty_value' => 'mautic.core.form.chooseone',
            'constraints' => array(
                new NotBlank(array('message' => 'mautic.lead.submitaction.leadfield.notblank'))
            )
        ));
        unset($choices);

        $fields = $this->factory->getModel('form.field')->getSessionFields();
        $choices = array();
        foreach ($fields as $k => $f) {
            //only show fields with ids
            if (strpos($k, 'new') !== false)
                continue;
            //ignore some types of fields
            if (in_array($f['type'], array('button', 'freetext', 'captcha')))
                continue;
            $choices[$k] = $f['label'];
        }

        $builder->add('formField', 'choice', array(
            'choices'     => $choices,
            'label'       => 'mautic.lead.lead.submitaction.formfield',
            'attr'        => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.lead.lead.submitaction.formfield.help'
            ),
            'label_attr'  => array('class' => 'control-label'),
            'multiple'    => false,
            'expanded'    => false,
            'empty_value' => 'mautic.core.form.chooseone',
            'constraints' => array(
                new NotBlank(array('message' => 'mautic.lead.submitaction.formfield.notblank'))
            )
        ));
        unset($choices);

        $builder->add('matchMode', 'choice', array(
            'choices' => array(
                'strict' => 'mautic.lead.lead.submitaction.matchmode_strict',
                'newest' => 'mautic.lead.lead.submitaction.matchmode_newest',
                'oldest' => 'mautic.lead.lead.submitaction.matchmode_oldest',
                'all'    => 'mautic.lead.lead.submitaction.matchmode_all'
            ),
            'label'       => 'mautic.lead.lead.submitaction.matchmode',
            'attr'        => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.lead.lead.submitaction.matchmode.help'
            ),
            'label_attr'  => array('class' => 'control-label'),
            'multiple'    => false,
            'expanded'    => false,
            'empty_value' => false,
            'required'    => false
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "lead_submitaction_scorechange";
    }
}