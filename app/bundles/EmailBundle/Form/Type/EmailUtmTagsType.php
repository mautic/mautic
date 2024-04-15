<?php

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Validator\Constraints\Length;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class EmailUtmTagsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'utmSource',
            TextType::class,
            [
                'label'      => 'mautic.email.campaign_source',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'utmMedium',
            TextType::class,
            [
                'label'      => 'mautic.email.campaign_medium',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'utmCampaign',
            TextType::class,
            [
                'label'      => 'mautic.email.campaign_name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'utmContent',
            TextType::class,
            [
            'label'      => 'mautic.email.campaign_content',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class' => 'form-control',
            ],
            'required'          => false,
            'constraints'       => new Length(['max' => ClassMetadataBuilder::MAX_VARCHAR_INDEXED_LENGTH]),
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'utm_tags';
    }
}
