<?php

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class FormFieldPageBreakType.
 */
class FormFieldPageBreakType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * FormFieldPageBreakType constructor.
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
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
