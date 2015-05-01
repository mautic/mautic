<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class AssetType
 *
 * @package Mautic\AssetBundle\Form\Type
 */
class AssetType extends AbstractType
{

    private $translator;
    private $themes;
    private $assetModel;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->translator = $factory->getTranslator();
        $this->themes     = $factory->getInstalledThemes('asset');
        $this->assetModel = $factory->getModel('asset');
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('description' => 'html')));
        $builder->addEventSubscriber(new FormExitSubscriber('asset.asset', $options));

        $builder->add('storageLocation', 'button_group', array(
            'label' => 'mautic.asset.asset.form.storageLocation',
            'choice_list' => new ChoiceList(
                array('local', 'remote'),
                array('mautic.asset.asset.form.storageLocation.local', 'mautic.asset.asset.form.storageLocation.remote')
            ),
            'attr' => array(
                'onchange' => 'Mautic.changeAssetStorageLocation();'
            )
        ));

        $maxUploadSize = $this->assetModel->getMaxUploadSize();
        $builder->add('tempName', 'hidden', array(
            'label'      => $this->translator->trans('mautic.asset.asset.form.file.upload', array('%max%' => $maxUploadSize)),
            'label_attr' => array('class' => 'control-label'),
            'required'   => false
        ));

        $builder->add('originalFileName', 'hidden', array(
            'required'   => false
        ));

        $builder->add('remotePath', 'text', array(
            'label'      => 'mautic.asset.asset.form.remotePath',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        $builder->add('title', 'text', array(
            'label'      => 'mautic.core.title',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('alias', 'text', array(
            'label'      => 'mautic.core.alias',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.asset.asset.help.alias',
            ),
            'required'   => false
        ));

        $builder->add('description', 'textarea', array(
            'label'      => 'mautic.core.description',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control editor'),
            'required'   => false
        ));

        $builder->add('category', 'category', array(
            'bundle' => 'asset'
        ));

        $builder->add('language', 'locale', array(
            'label'      => 'mautic.core.language',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.asset.asset.form.language.help',
            ),
            'required'   => false
        ));

        $builder->add('isPublished', 'yesno_button_group');

        $builder->add('publishUp', 'datetime', array(
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishup',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'       => 'form-control',
                'data-toggle' => 'datetime'
            ),
            'format'     => 'yyyy-MM-dd HH:mm',
            'required'   => false
        ));

        $builder->add('publishDown', 'datetime', array(
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishdown',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'       => 'form-control',
                'data-toggle' => 'datetime'
            ),
            'format'     => 'yyyy-MM-dd HH:mm',
            'required'   => false
        ));

        $builder->add('tempId', 'hidden', array(
            'required'   => false
        ));

        $builder->add('buttons', 'form_buttons', array());

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\AssetBundle\Entity\Asset'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "asset";
    }
}
