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

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class TriggerEventType.
 */
class TriggerEventType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $masks = ['description' => 'html'];

        $builder->add('name', 'text', [
            'label'      => 'mautic.core.name',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
        ]);

        $builder->add('description', 'textarea', [
            'label'      => 'mautic.core.description',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control editor'],
            'required'   => false,
        ]);

        if (!empty($options['settings']['formType'])) {
            $properties = (!empty($options['data']['properties'])) ? $options['data']['properties'] : null;

            $formTypeOptions = [
                'label' => false,
                'data'  => $properties,
            ];
            if (!empty($options['settings']['formTypeOptions'])) {
                $formTypeOptions = array_merge($formTypeOptions, $options['settings']['formTypeOptions']);
            }

            if (isset($options['settings']['formTypeCleanMasks'])) {
                $masks['properties'] = $options['settings']['formTypeCleanMasks'];
            }

            $builder->add('properties', $options['settings']['formType'], $formTypeOptions);
        }

        $builder->add('type', 'hidden');

        $update = !empty($properties);
        if (!empty($update)) {
            $btnValue = 'mautic.core.form.update';
            $btnIcon  = 'fa fa-pencil';
        } else {
            $btnValue = 'mautic.core.form.add';
            $btnIcon  = 'fa fa-plus';
        }

        $builder->add('buttons', 'form_buttons', [
            'save_text'       => $btnValue,
            'save_icon'       => $btnIcon,
            'apply_text'      => false,
            'container_class' => 'bottom-form-buttons',
        ]);

        $builder->add('triggerId', 'hidden', [
            'mapped' => false,
        ]);

        $builder->addEventSubscriber(new CleanFormSubscriber($masks));

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
            'settings' => false,
        ]);

        $resolver->setRequired(['settings']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pointtriggerevent';
    }
}
