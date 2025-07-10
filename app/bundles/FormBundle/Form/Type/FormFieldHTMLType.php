<?php

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class FormFieldHTMLType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('text', TextareaType::class, [
            'label'      => 'mautic.form.field.type.freehtml',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control', 'style' => 'min-height:150px'],
            'required'   => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'editor' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'formfield_html';
    }
}
