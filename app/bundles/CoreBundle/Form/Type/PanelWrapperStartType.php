<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

/**
 * Class PanelWrapperStartType
 */
class PanelWrapperStartType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $attr = function(Options $options, $value){
            return array(
                'class' => (isset($value['class'])) ? $value['class'] : '',
                'id'    => (isset($value['id'])) ? $value['id'] : ''
            );
        };

        $resolver->setDefaults(array(
            'attr'   => array(),
            'mapped' => false
        ));

        $resolver->setNormalizers(array('attr' => $attr));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'panel_wrapper_start';
    }
}
