<?php

namespace Mautic\StageBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class StageActionType.
 */
class StageActionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $masks           = [];
        $formTypeOptions = [
            'label' => false,
        ];
        if (!empty($options['formTypeOptions'])) {
            $formTypeOptions = array_merge($formTypeOptions, $options['formTypeOptions']);
        }
        $builder->add('properties', $options['formType'], $formTypeOptions);

        if (isset($options['settings']['formTypeCleanMasks'])) {
            $masks['properties'] = $options['settings']['formTypeCleanMasks'];
        }

        $builder->addEventSubscriber(new CleanFormSubscriber($masks));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'formType'        => GenericStageActionType::class,
            'formTypeOptions' => [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'stageaction';
    }
}
