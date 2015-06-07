<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Form\Type;

use Mautic\CoreBundle\Form\DataTransformer\SecondsConversionTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

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

        $default = (isset($options['data']) && isset($options['data']['first_time'])) ? $options['data']['first_time'] : false;
        $builder->add('first_time', 'yesno_button_group', array(
            'label'       => 'mautic.page.point.action.form.first.time.only',
            'attr'        => array(
                'tooltip' => 'mautic.page.point.action.form.first.time.only.descr'
            ),
            'data'        => $default
        ));

        $builder->add('page_hits', 'integer', array(
            'label'       => 'mautic.page.hits',
            'label_attr'  => array('class' => 'control-label'),
            'required'    => false,
            'attr'        => array(
                'class'         => 'form-control',
                'tooltip'       => 'mautic.page.point.action.form.page.hits.descr'
            )
        ));

        $formModifier = function (FormInterface $form, $data) use ($builder) {

            $unit = (isset($data['accumulative_time_unit'])) ? $data['accumulative_time_unit'] : 'H';
            $form->add('accumulative_time_unit', 'hidden', array(
                'data' => $unit
            ));

            $secondsTransformer = new SecondsConversionTransformer($unit);
            $form->add(
                $builder->create('accumulative_time', 'text', array(
                    'label'         => 'mautic.page.point.action.form.accumulative.time',
                    'required'      => false,
                    'label_attr'    => array('class' => 'control-label'),
                    'attr'          => array(
                        'class'         => 'form-control',
                        'tooltip'       => 'mautic.page.point.action.form.accumulative.time.descr'
                    ),
                    'auto_initialize' => false
                ))
                    ->addViewTransformer($secondsTransformer)
                    ->getForm()
            );

            $unit = (isset($data['returns_within_unit'])) ? $data['returns_within_unit'] : 'H';
            $secondsTransformer = new SecondsConversionTransformer($unit);
            $form->add('returns_within_unit', 'hidden', array(
                'data' => $unit
            ));

            $form->add(
                $builder->create('returns_within', 'text', array(
                    'label'         => 'mautic.page.point.action.form.returns.within',
                    'required'      => false,
                    'label_attr'    => array('class' => 'control-label'),
                    'attr'          => array(
                        'class'         => 'form-control',
                        'tooltip'       => 'mautic.page.point.action.form.returns.within.descr'
                    ),
                    'auto_initialize' => false
                ))
                    ->addViewTransformer($secondsTransformer)
                    ->getForm()
            );

            $unit = (isset($data['returns_after_unit'])) ? $data['returns_after_unit'] : 'H';
            $secondsTransformer = new SecondsConversionTransformer($unit);
            $form->add('returns_after_unit', 'hidden', array(
                'data' => $unit
            ));
            $form->add(
                $builder->create('returns_after', 'text', array(
                    'label'         => 'mautic.page.point.action.form.returns.after',
                    'required'      => false,
                    'label_attr'    => array('class' => 'control-label'),
                    'attr'          => array(
                        'class'         => 'form-control',
                        'tooltip'       => 'mautic.page.point.action.form.returns.after.descr'
                    ),
                    'auto_initialize' => false
                ))
                    ->addViewTransformer($secondsTransformer)
                    ->getForm()
            );
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $formModifier($event->getForm(), $data);
            }
        );

        $builder->addEventListener(FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $data    = $event->getData();
                $formModifier($event->getForm(), $data);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pointaction_urlhit';
    }
}
