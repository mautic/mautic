<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Form\Type;

use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class AssetType.
 */
class AssetType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $themes;

    /**
     * @var AssetModel
     */
    private $assetModel;

    /**
     * AssetType constructor.
     *
     * @param TranslatorInterface $translator
     * @param ThemeHelper         $themeHelper
     * @param AssetModel          $assetModel
     */
    public function __construct(TranslatorInterface $translator, ThemeHelper $themeHelper, AssetModel $assetModel)
    {
        $this->translator = $translator;
        $this->themes     = $themeHelper->getInstalledThemes('asset');
        $this->assetModel = $assetModel;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('asset.asset', $options));

        $builder->add('storageLocation', 'button_group', [
            'label'   => 'mautic.asset.asset.form.storageLocation',
            'choices' => [
                'mautic.asset.asset.form.storageLocation.local'  => 'local',
                'mautic.asset.asset.form.storageLocation.remote' => 'remote',
            ],
            'choices_as_values' => true,
            'attr'              => [
                'onchange' => 'Mautic.changeAssetStorageLocation();',
            ],
        ]);

        $maxUploadSize = $this->assetModel->getMaxUploadSize('', true);
        $builder->add('tempName', 'hidden', [
            'label'      => $this->translator->trans('mautic.asset.asset.form.file.upload', ['%max%' => $maxUploadSize]),
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
        ]);

        $builder->add('originalFileName', 'hidden', [
            'required' => false,
        ]);
        $builder->add(
            'disallow',
            'yesno_button_group',
            [
                'label' => 'mautic.asset.asset.form.disallow.crawlers',
                'attr'  => [
                    'tooltip'      => 'mautic.asset.asset.form.disallow.crawlers.descr',
                    'data-show-on' => '{"asset_storageLocation_0":"checked"}',
                ],
                'data'=> empty($options['data']->getDisallow()) ? false : true,
            ]
        );

        $builder->add('remotePath', 'text', [
            'label'      => 'mautic.asset.asset.form.remotePath',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
        ]);

        $builder->add('title', 'text', [
            'label'      => 'mautic.core.title',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
        ]);

        $builder->add('alias', 'text', [
            'label'      => 'mautic.core.alias',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.asset.asset.help.alias',
            ],
            'required' => false,
        ]);

        $builder->add('description', 'textarea', [
            'label'      => 'mautic.core.description',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control editor'],
            'required'   => false,
        ]);

        $builder->add('category', 'category', [
            'bundle' => 'asset',
        ]);

        $builder->add('language', 'locale', [
            'label'      => 'mautic.core.language',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.asset.asset.form.language.help',
            ],
            'required' => false,
        ]);

        $builder->add('isPublished', 'yesno_button_group');

        $builder->add('publishUp', 'datetime', [
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishup',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'       => 'form-control',
                'data-toggle' => 'datetime',
            ],
            'format'   => 'yyyy-MM-dd HH:mm',
            'required' => false,
        ]);

        $builder->add('publishDown', 'datetime', [
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishdown',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'       => 'form-control',
                'data-toggle' => 'datetime',
            ],
            'format'   => 'yyyy-MM-dd HH:mm',
            'required' => false,
        ]);

        $builder->add('tempId', 'hidden', [
            'required' => false,
        ]);

        $builder->add('buttons', 'form_buttons', []);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['data_class' => 'Mautic\AssetBundle\Entity\Asset']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'asset';
    }
}
