<?php

namespace Mautic\CampaignBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class CampaignImportType extends AbstractType
{
    /**
     * Build the form fields for importing campaign data.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The form options
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

    /**
     * Configure options for the form.
     *
     * @param OptionsResolver $resolver The resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }

    /**
     * Get the block prefix for the form.
     */
    public function getBlockPrefix(): string
    {
        return 'campaign_import';
    }
}
