<?php

namespace Mautic\CoreBundle\Form\Type;

use Mautic\FormBundle\Entity\FormRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GatedVideoType extends SlotType
{
    public function __construct(
        private FormRepository $formRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'url',
            UrlType::class,
            [
                'label'      => 'Video URL',
                'label_attr' => ['class' => 'control-label'],
                'required'   => true,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'gatedvideo-url',
                ],
            ]
        );

        $builder->add(
            'gatetime',
            TextType::class,
            [
                'label'      => 'Gate Time',
                'label_attr' => ['class' => 'control-label'],
                'required'   => true,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'gatedvideo-gatetime',
                ],
            ]
        );

        $builder->add(
            'formid',
            ChoiceType::class,
            [
                'label'      => 'Form',
                'label_attr' => ['class' => 'control-label'],
                'required'   => true,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'gatedvideo-formid',
                ],
                'placeholder' => 'Select your form',
                'choices'     => $this->getFormChoices(),
            ]
        );

        $builder->add(
            'width',
            TextType::class,
            [
                'label'      => 'Width',
                'label_attr' => ['class' => 'control-label'],
                'required'   => true,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'gatedvideo-width',
                ],
            ]
        );

        $builder->add(
            'height',
            TextType::class,
            [
                'label'      => 'Height',
                'label_attr' => ['class' => 'control-label'],
                'required'   => true,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'gatedvideo-height',
                ],
            ]
        );

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'width'  => 640,
                'height' => 320,
            ]
        );
    }

    private function getFormChoices(): array
    {
        $formList    = $this->formRepository->getSimpleList();
        $formChoices = [];

        foreach ($formList as $formItem) {
            $formChoices["{$formItem['label']} (ID {$formItem['value']})"] = $formItem['value'];
        }

        return $formChoices;
    }
}
