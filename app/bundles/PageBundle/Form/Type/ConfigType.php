<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ConfigType
 *
 * @package Mautic\PageBundle\Form\Type
 */
class ConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('cat_in_page_url', 'button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'label'       => 'mautic.page.config.form.cat.in.url',
            'label_attr'  => array('class' => 'control-label'),
            'expanded'    => true,
            'empty_value' => false,
            'data'        => (bool) $options['data']['cat_in_page_url'],
            'required' => false
        ));

        $builder->add('google_analytics', 'text', array(
            'label'      => 'mautic.page.config.form.google.analytics',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required' => false
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pageconfig';
    }
}