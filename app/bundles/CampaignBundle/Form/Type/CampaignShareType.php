<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @extends AbstractType<mixed>
 */
final class CampaignShareType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addMetadataFields($builder);
        $this->addCompatibilityFields($builder, $options);
        $this->addGalleryFields($builder);
        $this->addPricingFields($builder);
        $this->addSubmitButtons($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mautic_versions' => [
                '5.0' => '5.0',
                '6.0' => '6.0',
                '7.0' => '7.0',
            ],
        ]);

        $resolver->setAllowedTypes('mautic_versions', 'array');
    }

    private function addMetadataFields(FormBuilderInterface $builder): void
    {
        $builder->add(
            'title',
            TextType::class,
            [
                'label'       => 'mautic.campaign.share.title',
                'required'    => true,
                'attr'        => ['class' => 'form-control'],
                'constraints' => [new NotBlank()],
            ]
        );

        $builder->add(
            'version',
            TextType::class,
            [
                'label'       => 'mautic.campaign.share.version',
                'required'    => true,
                'data'        => '1.0.0',
                'attr'        => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(),
                    new Regex([
                        'pattern' => '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/',
                        'message' => 'mautic.campaign.share.version.invalid',
                    ]),
                ],
            ]
        );

        $builder->add(
            'bannerImage',
            ImageType::class,
            [
                'label' => 'mautic.campaign.share.banner_image',
            ]
        );

        $builder->add(
            'headline',
            TextType::class,
            [
                'label'       => 'mautic.campaign.share.headline',
                'required'    => true,
                'attr'        => [
                    'class'     => 'form-control',
                    'maxlength' => 60,
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 60]),
                ],
            ]
        );

        $builder->add(
            'description',
            TextareaType::class,
            [
                'label'       => 'mautic.campaign.share.description',
                'required'    => true,
                'attr'        => [
                    'class' => 'form-control',
                    'rows'  => 8,
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 100]),
                ],
            ]
        );

        $builder->add(
            'keywords',
            TextType::class,
            [
                'label'    => 'mautic.campaign.share.keywords',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'mautic.campaign.share.keywords.placeholder',
                ],
            ]
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    private function addCompatibilityFields(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'worksWithVersions',
            ChoiceType::class,
            [
                'label'       => 'mautic.campaign.share.works_with',
                'choices'     => $options['mautic_versions'],
                'expanded'    => true,
                'multiple'    => true,
                'required'    => true,
                'constraints' => [
                    new Count(['min' => 1, 'minMessage' => 'mautic.campaign.share.works_with.min']),
                ],
            ]
        );

        $builder->add(
            'languages',
            ChoiceType::class,
            [
                'label'    => 'mautic.campaign.share.languages',
                'choices'  => $this->getLanguageChoices(),
                'expanded' => false,
                'multiple' => true,
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ]
        );
    }

    private function addGalleryFields(FormBuilderInterface $builder): void
    {
        for ($i = 1; $i <= 8; ++$i) {
            $builder->add(
                'galleryImage'.$i,
                ImageType::class,
                [
                    'label' => 'mautic.campaign.share.gallery_image',
                ]
            );

            $builder->add(
                'galleryAlt'.$i,
                TextType::class,
                [
                    'label'    => 'mautic.campaign.share.gallery_alt',
                    'required' => false,
                    'attr'     => [
                        'class'       => 'form-control',
                        'placeholder' => 'Image description for accessibility',
                    ],
                ]
            );
        }
    }

    private function addPricingFields(FormBuilderInterface $builder): void
    {
        $builder->add(
            'price',
            MoneyType::class,
            [
                'label'    => 'mautic.campaign.share.price',
                'required' => false,
                'currency' => 'EUR',
                'attr'     => ['class' => 'form-control'],
            ]
        );
    }

    private function addSubmitButtons(FormBuilderInterface $builder): void
    {
        $builder->add(
            'publish',
            SubmitType::class,
            [
                'label' => 'mautic.campaign.share.publish',
                'attr'  => [
                    'class' => 'btn btn-primary',
                    'icon'  => 'ri-share-line',
                ],
            ]
        );

        $builder->add(
            'download',
            SubmitType::class,
            [
                'label' => 'mautic.campaign.share.download',
                'attr'  => [
                    'class' => 'btn btn-tertiary',
                    'icon'  => 'ri-download-line',
                ],
            ]
        );
    }

    /**
     * @return array<string, string>
     */
    private function getLanguageChoices(): array
    {
        return [
            'English'    => 'en',
            'Spanish'    => 'es',
            'French'     => 'fr',
            'German'     => 'de',
            'Portuguese' => 'pt',
            'Italian'    => 'it',
            'Dutch'      => 'nl',
            'Russian'    => 'ru',
            'Chinese'    => 'zh',
            'Japanese'   => 'ja',
            'Korean'     => 'ko',
            'Arabic'     => 'ar',
            'Turkish'    => 'tr',
            'Polish'     => 'pl',
            'Czech'      => 'cs',
            'Hungarian'  => 'hu',
            'Romanian'   => 'ro',
            'Swedish'    => 'sv',
            'Norwegian'  => 'no',
            'Danish'     => 'da',
            'Finnish'    => 'fi',
            'Hebrew'     => 'he',
            'Hindi'      => 'hi',
            'Thai'       => 'th',
            'Vietnamese' => 'vi',
            'Indonesian' => 'id',
            'Malay'      => 'ms',
        ];
    }
}
