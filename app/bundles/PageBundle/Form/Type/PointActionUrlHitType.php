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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;

/**
 * Class PointActionUrlHitType
 *
 * @package Mautic\PageBundle\Form\Type
 */
class PointActionUrlHitType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('page_url', 'text', array(
            'label'       => 'mautic.page.point.action.form.page.url',
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array(
                'class'         => 'form-control',
                'tooltip'       => 'mautic.page.point.action.form.page.url.descr',
                'placeholder'   => 'http://'
            )
        ));

        $builder->add('first_time', 'button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'label'       => 'mautic.page.point.action.form.first.time.only',
            'expanded'    => true,
            'empty_value' => false,
            'required'    => false,
            'attr'        => array(
                'tooltip' => 'mautic.page.point.action.form.first.time.only.descr'
            )
        ));

        $builder->add('page_hits', 'integer', array(
            'label'       => 'mautic.page.point.action.form.page.hits',
            'label_attr'  => array('class' => 'control-label'),
            'required'    => false,
            'attr'        => array(
                'class'         => 'form-control',
                'tooltip'       => 'mautic.page.point.action.form.page.hits.descr'
            )
        ));

        $builder->add('accumulative_time', 'time', array(
            'label'         => 'mautic.page.point.action.form.accumulative.time',
            'input'         => 'timestamp',
            'widget'        => 'choice',
            'required'      => false,
            'with_seconds'  => true,
            'label_attr'    => array('class' => 'control-label'),
            'attr'          => array(
                'class'         => 'form-control',
                'tooltip'       => 'mautic.page.point.action.form.accumulative.time.descr'
            )
        ));

        $builder->add('returns_within', 'time', array(
            'label'         => 'mautic.page.point.action.form.returns.within',
            'input'         => 'timestamp',
            'widget'        => 'choice',
            'required'      => false,
            'with_seconds'  => true,
            'label_attr'    => array('class' => 'control-label'),
            'attr'          => array(
                'class'         => 'form-control',
                'tooltip'       => 'mautic.page.point.action.form.returns.within.descr'
            )
        ));

        $builder->add('returns_after', 'time', array(
            'label'         => 'mautic.page.point.action.form.returns.after',
            'input'         => 'timestamp',
            'widget'        => 'choice',
            'required'      => false,
            'with_seconds'  => true,
            'label_attr'    => array('class' => 'control-label'),
            'attr'          => array(
                'class'         => 'form-control',
                'tooltip'       => 'mautic.page.point.action.form.returns.after.descr'
            )
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pointaction_urlhit';
    }
}
