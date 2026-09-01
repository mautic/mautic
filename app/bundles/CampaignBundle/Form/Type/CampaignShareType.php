<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\Intl\Locales;
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
            'validation_groups' => static function (FormInterface $form): array {
                $publish = $form->has('publish') ? $form->get('publish') : null;
                if ($publish instanceof SubmitButton && $publish->isClicked()) {
                    return ['Default', 'publish'];
                }

                return ['Default'];
            },
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
            'vendorName',
            TextType::class,
            [
                'label'       => 'mautic.campaign.share.vendor_name',
                'required'    => true,
                'attr'        => [
                    'class'       => 'form-control',
                    'placeholder' => 'mautic.campaign.share.vendor_name.placeholder',
                ],
                'constraints' => [
                    new NotBlank(),
                    new Regex(pattern: '/^(?!mautic$)[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', message: 'mautic.campaign.share.vendor_name.invalid'),
                ],
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
                    new Regex(pattern: '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/', message: 'mautic.campaign.share.version.invalid'),
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

        // Carries the stash token of a banner uploaded in a submit that failed
        // validation, so the image survives the round-trip (file inputs don't).
        $builder->add('bannerImageStash', HiddenType::class, ['required' => false]);

        $builder->add(
            'headline',
            TextType::class,
            [
                'label'       => 'mautic.campaign.share.headline',
                'required'    => true,
                'attr'        => [
                    'class'               => 'form-control',
                    'maxlength'           => 60,
                    'data-share-headline' => true,
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 60),
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
                    new Length(min: 100),
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
                'required'    => false,
                'constraints' => [
                    new Count(min: 1, minMessage: 'mautic.campaign.share.works_with.min', groups: ['publish']),
                ],
            ]
        );

        $builder->add(
            'languages',
            ChoiceType::class,
            [
                'label'    => 'mautic.campaign.share.languages',
                'choices'  => array_flip(Locales::getNames()),
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

            // Same failed-validation stash mechanism as bannerImageStash.
            $builder->add('galleryImageStash'.$i, HiddenType::class, ['required' => false]);

            $builder->add(
                'galleryAlt'.$i,
                TextType::class,
                [
                    'label'    => 'mautic.campaign.share.gallery_alt',
                    'required' => false,
                    'attr'     => [
                        'class'       => 'form-control',
                        'placeholder' => 'mautic.campaign.share.gallery_alt.placeholder',
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
                // Temporarily disabled until the marketplace supports paid packages.
                'disabled' => true,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => '0.00',
                ],
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
}
