<?php

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SlotChannelFrequencyType extends SlotType
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'label-text',
            TextType::class,
            [
                'label'      => 'mautic.lead.field.label',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'label-text',
                ],
                'data'       => $this->translator->trans('mautic.lead.contact.me.label'),
            ]
        );

        $builder->add(
            'label-text1',
            TextType::class,
            [
                'label'      => 'mautic.page.form.frequency.label1',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'label-text1',
                ],
                'data'       => $this->translator->trans('mautic.lead.list.frequency.number'),
            ]
        );

        $builder->add(
            'label-text2',
            TextType::class,
            [
                'label'      => 'mautic.page.form.frequency.label2',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'label-text2',
                ],
                'data'       => $this->translator->trans('mautic.lead.list.frequency.times'),
            ]
        );

        $builder->add(
            'label-text3',
            TextType::class,
            [
                'label'      => 'mautic.page.form.pause.label1',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'label-text3',
                ],
                'data'       => $this->translator->trans('mautic.lead.frequency.dates.label'),
            ]
        );

        $builder->add(
            'label-text4',
            TextType::class,
            [
                'label'      => 'mautic.page.form.pause.label2',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'label-text4',
                ],
                'data'       => $this->translator->trans('mautic.lead.frequency.contact.end.date'),
            ]
        );

        parent::buildForm($builder, $options);
    }

    /**
     * @return mixed
     */
    public function getBlockPrefix()
    {
        return 'slot_channelfrequency';
    }
}
