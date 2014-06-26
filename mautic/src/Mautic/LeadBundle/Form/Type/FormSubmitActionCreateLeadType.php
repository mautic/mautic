<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class FormSubmitActionCreateLeadType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class FormSubmitActionCreateLeadType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $default = (empty($options['data']['score'])) ? 0 : (int) $options['data']['score'];
        $builder->add('score', 'number', array(
            'label'      => 'mautic.lead.lead.submitaction.startingscore',
            'attr'       => array(
                'class' => 'form-control',
                'tooltip' => 'mautic.lead.lead.submitaction.startingscore.help'
            ),
            'label_attr' => array('class' => 'control-label'),
            'precision'  => 0,
            'data'       => $default,
            'required'   => false
        ));


        $builder->add('instructions', 'spacer', array(
            'text' => 'mautic.lead.lead.submitaction.createlead.help',
            'tag'  => 'h4'
        ));

        $builder->add('instructions2', 'spacer', array(
            'text' => 'mautic.lead.lead.submitaction.createlead.help2',
            'tag'  => 'span',
            'class' => 'text-warning'
        ));

        $builder->add('mappedFields', 'lead_submitaction_mappedfields', array(
            'label'  => false
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "lead_submitaction_createlead";
    }
}