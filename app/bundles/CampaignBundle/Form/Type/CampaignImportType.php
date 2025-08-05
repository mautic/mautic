<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
final class CampaignImportType extends AbstractType
{
    /**
     * Build the form fields for importing campaign data.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array<string, mixed> $options The form options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'campaignFile',
            FileType::class,
            [
                'required' => true,
                'mapped'   => false,
                'attr'     => [
                    'class'  => 'form-control',
                    'accept' => '.zip',
                ],
            ]
        );

        $builder->add(
            'start',
            SubmitType::class,
            [
                'label' => 'mautic.campaign.campaign.import.upload.button',
                'attr'  => [
                    'class' => 'btn btn-tertiary btn-sm',
                    'icon'  => 'ri-import-line',
                ],
            ]
        );
    }
}
