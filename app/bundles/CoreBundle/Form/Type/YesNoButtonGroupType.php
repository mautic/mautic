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
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class YesNoButtonGroupType
 */
class YesNoButtonGroupType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'button_group';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'yesno_button_group';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'expanded'    => true,
            'multiple'    => false,
            'label_attr'  => array('class' => 'control-label'),
            'label'       => 'mautic.core.form.published',
            'empty_value' => false,
            'required'    => false
        ));
    }
}
