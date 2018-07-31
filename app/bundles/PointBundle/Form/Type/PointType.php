<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\PointBundle\Entity\Point;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class PointType.
 */
class PointType extends AbstractType
{
    /**
     * @var \Mautic\CoreBundle\Security\Permissions\CorePermissions
     */
    private $security;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->translator = $factory->getTranslator();
        $this->security   = $factory->getSecurity();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('point', $options));

        $builder->add('name', 'text', [
            'label'      => 'mautic.core.name',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
        ]);

        $builder->add('description', 'textarea', [
            'label'      => 'mautic.core.description',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control editor'],
            'required'   => false,
        ]);

        $builder->add('type', 'choice', [
            'choices'     => $options['pointActions']['choices'],
            'empty_value' => '',
            'label'       => 'mautic.point.form.type',
            'label_attr'  => ['class' => 'control-label'],
            'attr'        => [
                'class'    => 'form-control',
                'onchange' => 'Mautic.getPointActionPropertiesForm(this.value);',
            ],
        ]);

        $builder->add('delta', 'number', [
            'label'      => 'mautic.point.action.delta',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.point.action.delta.help',
                ],
            'precision' => 0,
        ]);

        $type = (!empty($options['actionType'])) ? $options['actionType'] : $options['data']->getType();
        if ($type) {
            $formType = (!empty($options['pointActions']['actions'][$type]['formType'])) ?
                $options['pointActions']['actions'][$type]['formType'] : 'genericpoint_settings';
            $properties = ($options['data']) ? $options['data']->getProperties() : [];
            $builder->add('properties', $formType, [
                'label' => false,
                'data'  => $properties,
            ]);
        }

        if (!empty($options['data']) && $options['data'] instanceof Point) {
            $readonly = !$this->security->hasEntityAccess(
                'point:points:publishown',
                'point:points:publishother',
                $options['data']->getCreatedBy()
            );

            $data = $options['data']->isPublished(false);
        } elseif (!$this->security->isGranted('point:points:publishown')) {
            $readonly = true;
            $data     = false;
        } else {
            $readonly = false;
            $data     = true;
        }

        $builder->add('isPublished', 'yesno_button_group', [
            'read_only' => $readonly,
            'data'      => $data,
        ]);

        $builder->add(
            'repeatable',
            'yesno_button_group',
            [
                'label' => 'mautic.point.form.repeat',
                'data'  => $options['data']->getRepeatable() ?: false,
            ]
        );

        $builder->add('publishUp', 'datetime', [
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishup',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'       => 'form-control',
                'data-toggle' => 'datetime',
            ],
            'format'   => 'yyyy-MM-dd HH:mm',
            'required' => false,
        ]);

        $builder->add('publishDown', 'datetime', [
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishdown',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'       => 'form-control',
                'data-toggle' => 'datetime',
            ],
            'format'   => 'yyyy-MM-dd HH:mm',
            'required' => false,
        ]);

        //add category
        $builder->add('category', 'category', [
            'bundle' => 'point',
        ]);

        $builder->add('buttons', 'form_buttons');

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Mautic\PointBundle\Entity\Point',
        ]);

        $resolver->setRequired(['pointActions']);

        $resolver->setOptional(['actionType']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'point';
    }
}
