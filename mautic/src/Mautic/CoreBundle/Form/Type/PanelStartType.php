<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

/**
 * Class PanelStartType
 *
 * @package Mautic\CoreBundle\Form\Type
 */
class PanelStartType extends AbstractType
{
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $attr = function(Options $options, $value){
            return array(
                'data-parent' => (isset($value['data-parent'])) ? $value['data-parent'] : '',
                'class'       => (isset($value['class']))       ? $value['class'] : ' panel-default',
                'id'          => (isset($value['id']))          ? $value['id'] : ''
            );
        };

        $resolver->setDefaults(array(
            'attr'   => array(),
            'mapped' => false
        ));

        $resolver->setNormalizers(array('attr' => $attr));
    }

    /**
     * @return string
     */
    public function getName() {
        return "panel_start";
    }
}