<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class StageActionFormSubmitType
 *
 * @package Mautic\AssetBundle\Form\Type
 */
class StageActionAssetDownloadType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('assets', 'asset_list', array(
            'expanded'      => false,
            'multiple'      => true,
            'label'         => 'mautic.asset.stage.action.assets',
            'label_attr'    => array('class' => 'control-label'),
            'empty_value'   => false,
            'required'      => false,
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.asset.stage.action.assets.descr'
            )
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "stageaction_assetdownload";
    }
}