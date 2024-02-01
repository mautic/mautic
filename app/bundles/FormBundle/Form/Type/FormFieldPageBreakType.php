<?php

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<mixed>
 */
class FormFieldPageBreakType extends AbstractType
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'next_page_label',
            TextType::class,
            [
                'label'      => 'mautic.form.field.form.property_pagebreak_nextpage_label',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required'    => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.core.value.required']
                    ),
                ],
                'empty_data' => $this->translator->trans('mautic.core.continue'),
            ]
        );

        $builder->add(
            'prev_page_label',
            TextType::class,
            [
                'label'      => 'mautic.form.field.form.property_pagebreak_prevpage_label',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'placeholder' => 'mautic.form.field.form.property_pagebreak_prevpage_placeholder',
                ],
                'required' => false,
            ]
        );
    }
}
