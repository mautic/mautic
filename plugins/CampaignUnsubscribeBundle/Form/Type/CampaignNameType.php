<?php

/*
 * @copyright   2017 Partout D.N.A. All rights reserved
 * @author      Partout D.N.A.
 *
 * @link        https://partout.nl
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CampaignUnsubscribeBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class CampaignNameType
 * @package MauticPlugin\CampaignUnsubscribeBundle\Form\Type
 */
class CampaignNameType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());

        $builder->add(
            'campaign',
            'campaign_list',
            [
                'label'      => 'mautic.campaign.campaign',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => true,
                'multiple' => false,
            ]
        );

        $builder->add(
            'name',
            'text',
            [
                'label'      => 'mautic.core.label',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => true,
            ]
        );

        $builder->add('buttons', 'form_buttons');

        $builder->add(
            'inForm',
            'hidden',
            [
                'mapped' => false,
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
        $resolver->setDefaults(
            [
                'data_class'         => 'MauticPlugin\CampaignUnsubscribeBundle\Entity\CampaignName',
                'show_bundle_select' => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'campaign_name';
    }
}
