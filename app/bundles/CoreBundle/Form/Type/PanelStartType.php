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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class PanelStartType
 */
class PanelStartType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('dataParent', 'bodyId'));

        $resolver->setDefaults(array(
            'attr'       => array('class' => 'panel-default'),
            'headerAttr' => array(),
            'bodyAttr'   => array(),
            'mapped'     => false
        ));

        $resolver->setOptional(array('headerAttr', 'bodyAttr'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'panel_start';
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $customVars = array('dataParent', 'bodyId', 'headerAttr', 'bodyAttr');

        foreach ($customVars as $v) {
            if (array_key_exists($v, $options)) {
                // set an 'headerAttr' variable that will be available when rendering this field
                $view->vars[$v] = $options[$v];
            }
        }
    }
}
