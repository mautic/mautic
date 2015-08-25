<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
 * Class PointType
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
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('description' => 'html')));
        $builder->addEventSubscriber(new FormExitSubscriber('point', $options));

        $builder->add('name', 'text', array(
            'label'      => 'mautic.core.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('description', 'textarea', array(
            'label'      => 'mautic.core.description',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control editor'),
            'required'   => false
        ));

        $builder->add('type', 'choice', array(
            'choices' => $options['pointActions']['choices'],
            'empty_value' => '',
            'label'       => 'mautic.point.form.type',
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array(
                'class' => 'form-control',
                'onchange' => 'Mautic.getPointActionPropertiesForm(this.value);'
            ),
        ));

        $builder->add('delta', 'number', array(
            'label'      => 'mautic.point.action.delta',
            'label_attr' => array('class' => 'control-label'),
            'attr'       =>
                array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.point.action.delta.help'
                ),
            'precision'  => 0
        ));

        $type = (!empty($options['actionType'])) ? $options['actionType'] : $options['data']->getType();
        if ($type) {
            $formType   =  (!empty($options['pointActions']['actions'][$type]['formType'])) ?
                $options['pointActions']['actions'][$type]['formType'] : 'genericpoint_settings';
            $properties = ($options['data']) ? $options['data']->getProperties() : array();
            $builder->add('properties', $formType, array(
                'label' => false,
                'data'  => $properties
            ));
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

        $builder->add('isPublished', 'yesno_button_group', array(
            'read_only'     => $readonly,
            'data'          => $data
        ));

        $builder->add('publishUp', 'datetime', array(
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishup',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control',
                'data-toggle' => 'datetime'
            ),
            'format'  => 'yyyy-MM-dd HH:mm',
            'required'   => false
        ));

        $builder->add('publishDown', 'datetime', array(
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishdown',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control',
                'data-toggle' => 'datetime'
            ),
            'format'  => 'yyyy-MM-dd HH:mm',
            'required'   => false
        ));

        //add category
        $builder->add('category', 'category', array(
            'bundle' => 'point'
        ));

        $builder->add('buttons', 'form_buttons');

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\PointBundle\Entity\Point',
        ));

        $resolver->setRequired(array('pointActions'));

        $resolver->setOptional(array('actionType'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "point";
    }
}
