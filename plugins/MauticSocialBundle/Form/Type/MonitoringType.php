<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\LeadBundle\Form\Type\LeadListType;
use MauticPlugin\MauticSocialBundle\Model\MonitoringModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MonitoringType extends AbstractType
{
    /** @var MonitoringModel */
    private $monitoringModel;

    public function __construct(MonitoringModel $monitoringModel)
    {
        $this->monitoringModel = $monitoringModel;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));

        $builder->add('title', TextType::class, [
            'label'      => 'mautic.core.name',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
        ]);

        $builder->add('description', TextareaType::class, [
            'label'      => 'mautic.core.description',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control editor'],
            'required'   => false,
        ]);

        $builder->add('isPublished', YesNoButtonGroupType::class);

        $builder->add('publishUp', DateTimeType::class, [
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

        $builder->add('publishDown', DateTimeType::class, [
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

        $builder->add('networkType', ChoiceType::class, [
            'label'      => 'mautic.social.monitoring.type.list',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'    => 'form-control',
                'onchange' => 'Mautic.getNetworkFormAction(this)',
            ],
            'choices'           => array_flip((array) $options['networkTypes']), // passed from the controller
            'placeholder'       => 'mautic.core.form.chooseone',
        ]);

        // if we have a network type value add in the form
        if (!empty($options['networkType']) && array_key_exists($options['networkType'], $options['networkTypes'])) {
            // get the values from the entity function
            $properties = $options['data']->getProperties();

            $formType = $this->monitoringModel->getFormByType($options['networkType']);

            $builder->add('properties', $formType,
                [
                    'label' => false,
                    'data'  => $properties,
                ]
            );
        }

        $builder->add(
            'lists',
            LeadListType::class,
            [
                'label'      => 'mautic.lead.lead.events.addtolists',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'multiple' => true,
                'expanded' => false,
            ]
        );

        //add category
        $builder->add('category', CategoryListType::class, [
            'bundle' => 'plugin:mauticSocial',
        ]);

        $builder->add('buttons', FormButtonsType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
                'data_class' => 'MauticPlugin\MauticSocialBundle\Entity\Monitoring',
            ]);

        // allow network types to be sent through - list
        $resolver->setRequired(['networkTypes']);

        // allow the specific network type - single
        $resolver->setDefined(['networkType']);
    }

    public function getBlockPrefix()
    {
        return 'monitoring';
    }
}
