<?php

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class FormFieldSelectType extends AbstractType
{
    use SortableListTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ('select' === $options['field_type']) {
            $this->addSortableList($builder, $options);
        }

        $builder->add(
            'placeholder',
            TextType::class,
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
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.form.field.form.multiple',
                'data'  => $default,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'field_type' => 'select',
                'parentData' => [],
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'formfield_select';
    }
}
