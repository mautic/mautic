<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class PointActionFormSubmitType
 *
 * @package Mautic\AssetBundle\Form\Type
 */
class PointActionAssetDownloadType extends AbstractType
{

    private $choices = array();

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $viewOther = $factory->getSecurity()->isGranted('asset:assets:viewother');
        $choices = $factory->getModel('asset')->getRepository()
            ->getAssetList('', 0, 0, $viewOther);
        foreach ($choices as $asset) {
            $this->choices[$asset['language']][$asset['id']] = $asset['id'] . ':' . $asset['title'];
        }

        //sort by language
        ksort($this->choices);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $default = (empty($options['data']['delta'])) ? 0 : (int) $options['data']['delta'];
        $builder->add('delta', 'number', array(
            'label'      => 'mautic.point.action.delta',
            'label_attr' => array('class' => 'control-label'),
            'attr'       =>
                array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.point.action.delta.help'
                ),
            'precision'  => 0,
            'data'       => $default
        ));

        $builder->add('assets', 'choice', array(
            'choices'       => $this->choices,
            'expanded'      => false,
            'multiple'      => true,
            'label'         => 'mautic.asset.point.action.assets',
            'label_attr'    => array('class' => 'control-label'),
            'empty_value'   => false,
            'required'      => false,
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.asset.point.action.assets.descr'
            )
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "pointaction_assetdownload";
    }
}