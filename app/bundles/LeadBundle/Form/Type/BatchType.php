<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BatchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'add',
            ChoiceType::class,
            [
                'label'             => 'mautic.lead.batch.add_to',
                'multiple'          => true,
                'choices'           => $options['items'],
                'required'          => false,
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'remove',
            ChoiceType::class,
            [
                'label'             => 'mautic.lead.batch.remove_from',
                'multiple'          => true,
                'choices'           => $options['items'],
                'required'          => false,
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => ['class' => 'form-control'],
            ]
        );

        $builder->add('ids', HiddenType::class);

        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'apply_text'     => false,
                'save_text'      => 'mautic.core.form.save',
                'cancel_onclick' => 'javascript:void(0);',
                'cancel_attr'    => [
                    'data-dismiss' => 'modal',
                ],
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'items',
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'lead_batch';
    }
}
