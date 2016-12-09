<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FormFieldSelectType.
 */
class FormFieldSelectType extends AbstractType
{
    use SortableListTrait;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['field_type'] == 'select') {
            $this->addSortableList($builder, $options);
        }

        $builder->add(
            'empty_value',
            'text',
            [
                'label'      => 'mautic.form.field.form.emptyvalue',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );

        if (!empty($options['parentData'])) {
            $default = (empty($options['parentData']['properties']['multiple'])) ? false : true;
        } else {
            $default = false;
        }
        $builder->add(
            'multiple',
            'yesno_button_group',
            [
                'label' => 'mautic.form.field.form.multiple',
                'data'  => $default,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'field_type' => 'select',
                'parentData' => [],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'formfield_select';
    }
}
