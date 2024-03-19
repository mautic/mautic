<?php

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class FormFieldTextType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $editor = ($options['editor']) ? ' editor editor-advanced' : '';

        $builder->add('text', TextareaType::class, [
            'label'      => 'mautic.form.field.type.freetext',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'.$editor],
            'required'   => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'editor' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'formfield_text';
    }
}
