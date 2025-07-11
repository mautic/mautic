<?php

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class FormFieldGroupType extends AbstractType
{
    use SortableListTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'labelAttributes',
            TextType::class,
            [
                'label'      => 'mautic.form.field.group.labelattr',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'     => 'form-control',
                    'tooltip'   => 'mautic.form.field.help.group.labelattr',
                    'maxlength' => '191',
                ],
                'required' => false,
            ]
        );

        if (isset($options['data']['optionlist'])) {
            $data = $options['data']['optionlist'];
        } elseif (isset($options['data']['list'])) {
            // BC support
            $data = ['list' => $options['data']['list']];
        } else {
            $data = [];
        }

        $this->addSortableList($builder, $options, 'optionlist', $data);
    }

    public function getBlockPrefix(): string
    {
        return 'formfield_group';
    }
}
