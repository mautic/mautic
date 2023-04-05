<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;

trait PublishDateTrait
{
    private function addPublishDateFields(FormBuilderInterface $builder, bool $readOnly = false): void
    {
        $this->addPublishUpField($builder, $readOnly);
        $this->addPublishDownField($builder, $readOnly);
    }

    private function addPublishUpField(FormBuilderInterface $builder, bool $readOnly = false): void
    {
        $builder->add(
            'publishUp',
            DateTimeType::class,
            [
                'widget'     => 'single_text',
                'label'      => 'mautic.core.form.publishup',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime',
                    'readonly'    => $readOnly,
                ],
                'format'   => 'yyyy-MM-dd HH:mm',
                'html5'    => false,
                'required' => false,
            ]
        );
    }

    private function addPublishDownField(FormBuilderInterface $builder, bool $readOnly = false): void
    {
        $builder->add(
            'publishDown',
            DateTimeType::class,
            [
                'widget'     => 'single_text',
                'label'      => 'mautic.core.form.publishdown',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime',
                    'readonly'    => $readOnly,
                ],
                'format'   => 'yyyy-MM-dd HH:mm',
                'html5'    => false,
                'required' => false,
            ]
        );
    }
}
