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
 * Class CampaignEventAssetDownloadType
 *
 * @package Mautic\AssetBundle\Form\Type
 */
class CampaignEventAssetDownloadType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('assets', 'asset_list', array(
            'label'         => 'mautic.asset.campaign.event.assets',
            'label_attr'    => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.asset.campaign.event.assets.descr'
            )
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "campaignevent_assetdownload";
    }
}