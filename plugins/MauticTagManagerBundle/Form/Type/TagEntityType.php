<?php

namespace MauticPlugin\MauticTagManagerBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use MauticPlugin\MauticTagManagerBundle\Entity\Tag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<Tag>
 */
class TagEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('buttons', FormButtonsType::class);
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));

        // We only allow to set tag field value if we are creating new tag.
        $tagReadOnly = !empty($options['data']) && $options['data']->getId() ? true : false;

        $builder->add(
            'tag',
            TextType::class,
            [
                'label'       => 'mautic.core.name',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control', 'readonly' => $tagReadOnly],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
            ]
        );

        $builder->add(
            'description',
            TextareaType::class,
            [
                'required'   => false,
                'label'      => 'mautic.core.description',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control editor', 'readonly' => $tagReadOnly],
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }
}
