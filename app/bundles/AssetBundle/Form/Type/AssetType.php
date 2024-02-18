<?php

namespace Mautic\AssetBundle\Form\Type;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\PublishDownDateType;
use Mautic\CoreBundle\Form\Type\PublishUpDateType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<Asset>
 */
class AssetType extends AbstractType
{
    public function __construct(
        private TranslatorInterface $translator,
        private AssetModel $assetModel
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('asset.asset', $options));

        $builder->add('storageLocation', ButtonGroupType::class, [
            'label'   => 'mautic.asset.asset.form.storageLocation',
            'choices' => [
                'mautic.asset.asset.form.storageLocation.local'  => 'local',
                'mautic.asset.asset.form.storageLocation.remote' => 'remote',
            ],
            'attr'              => [
                'onchange' => 'Mautic.changeAssetStorageLocation();',
            ],
        ]);

        $maxUploadSize = $this->assetModel->getMaxUploadSize('', true);
        $builder->add(
            'tempName',
            HiddenType::class,
            [
                'label'      => $this->translator->trans('mautic.asset.asset.form.file.upload', ['%max%' => $maxUploadSize]),
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
            ]
        );

        $builder->add(
            'originalFileName',
            HiddenType::class,
            [
                'required' => false,
            ]
        );
        $builder->add(
            'disallow',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.asset.asset.form.disallow.crawlers',
                'attr'  => [
                    'tooltip'      => 'mautic.asset.asset.form.disallow.crawlers.descr',
                    'data-show-on' => '{"asset_storageLocation_0":"checked"}',
                ],
                'data'=> empty($options['data']->getDisallow()) ? false : true,
            ]
        );

        $builder->add(
            'remotePath',
            TextType::class,
            [
                'label'      => 'mautic.asset.asset.form.remotePath',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );

        $builder->add(
            'title',
            TextType::class,
            [
                'label'      => 'mautic.core.title',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'alias',
            TextType::class,
            [
                'label'      => 'mautic.core.alias',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.asset.asset.help.alias',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'description',
            TextareaType::class,
            [
                'label'      => 'mautic.core.description',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control editor'],
                'required'   => false,
            ]
        );

        $builder->add(
            'category',
            CategoryListType::class,
            [
                'bundle' => 'asset',
            ]
        );

        $builder->add('language', LocaleType::class, [
            'label'      => 'mautic.core.language',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.asset.asset.form.language.help',
            ],
            'required'    => true,
            'constraints' => [
                new NotBlank(
                    [
                        'message' => 'mautic.core.value.required',
                    ]
                ),
            ],
        ]);

        $builder->add('isPublished', YesNoButtonGroupType::class);
        $builder->add('publishUp', PublishUpDateType::class);
        $builder->add('publishDown', PublishDownDateType::class);

        $builder->add(
            'tempId',
            HiddenType::class,
            [
                'required' => false,
            ]
        );

        $builder->add('buttons', FormButtonsType::class, []);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Asset::class]);
    }
}
