<?php
/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      WebMecanik
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticWebinarBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Class WebikeoCampaignWebinarsType.
 */
class WebikeoCampaignWebinarsType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $webinars = $options['integrationObject']->getWebinars();

        $builder->add(
            'webinar',
            ChoiceType::class,
            [
                'choices' => $webinars,
                'attr'    => [
                    'class' => 'form-control', ],
                'label'    => 'mautic.plugin.integration.webinar',
                'required' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['integrationObject']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Webikeo_campaignevent_webinars';
    }
}
