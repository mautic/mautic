<?php

namespace Mautic\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class UploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'file',
            FileType::class,
            [
                'label' => 'mautic.lead.import.file',
                'attr'  => [
                    'accept' => '.json',
                    'class'  => 'form-control',
                ],
            ]
        );

        $builder->add(
            'start',
            SubmitType::class,
            [
                'attr' => [
                    'class'   => 'btn btn-primary',
                    'icon'    => 'ri-upload-line',
                    'onclick' => "mQuery(this).prop('disabled', true); mQuery('form[name=\'dashboard_upload\']').submit();",
                ],
                'label' => 'mautic.lead.import.upload',
            ]);
        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'dashboard_upload';
    }
}
