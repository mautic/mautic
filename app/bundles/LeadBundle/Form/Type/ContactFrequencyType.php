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

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\FrequencyRule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class MergeType.
 */
class ContactFrequencyType extends AbstractType
{
    protected $coreParametersHelper;

    /**
     * ContactFrequencyType constructor.
     *
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $showContactCategories        = $this->coreParametersHelper->getParameter('show_contact_categories');
        $showContactSegments          = $this->coreParametersHelper->getParameter('show_contact_segments');

        if (isset($options['channels']) && $options['channels']) {
            $builder->add(
                'lead_channels',
                'lead_contact_channels',
                [
                    'channels' => $options['channels'],
                ]
            );
        }

        if (!$options['public_view']) {
            $builder->add(
                'lead_lists',
                'leadlist_choices',
                [
                    'label'      => 'mautic.lead.form.list',
                    'label_attr' => ['class' => 'control-label'],
                    'multiple'   => true,
                    'expanded'   => $options['public_view'],
                    'required'   => false,
                ]
            );
        } elseif ($showContactSegments) {
            $builder->add(
                'lead_lists',
                'leadlist_choices',
                [
                    'global_only' => true,
                    'label'       => 'mautic.lead.form.list',
                    'label_attr'  => ['class' => 'control-label'],
                    'multiple'    => true,
                    'expanded'    => $options['public_view'],
                    'required'    => false,
                ]
            );
        }

        if (!$options['public_view'] || $showContactCategories) {
            $builder->add(
                'global_categories',
                'leadcategory_choices',
                [
                    'label'      => 'mautic.lead.form.categories',
                    'label_attr' => ['class' => 'control-label'],
                    'multiple'   => true,
                    'expanded'   => $options['public_view'],
                    'required'   => false,
                ]
            );
        }

        $builder->add(
            'buttons',
            'form_buttons',
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

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['channels']);
        $resolver->setDefaults(
            [
                'public_view' => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'lead_contact_frequency_rules';
    }
}
