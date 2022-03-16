<?php

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FormFieldTextType.
 */
class FormFieldTextType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $editor = ($options['editor']) ? ' editor editor-advanced' : '';

        $builder->add('text', TextareaType::class, [
            'label'      => 'mautic.form.field.type.freetext',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'.$editor],
            'required'   => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'editor' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'formfield_text';
    }
}
