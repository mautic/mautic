<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FormFieldSelectType
 */
class FormFieldSelectType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['field_type'] == 'select') {
            $builder->add('list', 'sortablelist', array());
        }

        $builder->add('empty_value', 'text', array(
            'label'      => 'mautic.form.field.form.emptyvalue',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        if (!empty($options['parentData'])) {
            $default = (empty($options['parentData']['properties']['multiple'])) ? false : true;
        } else {
            $default = false;
        }
        $builder->add('multiple', 'yesno_button_group', array(
            'label' => 'mautic.form.field.form.multiple',
            'data'  => $default
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'field_type' => 'select',
            'parentData' => array()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "formfield_select";
    }
}
