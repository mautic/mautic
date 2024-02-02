<?php

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use Mautic\LeadBundle\Field\FieldList;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<array<mixed>>
 */
class ConfigType extends AbstractType
{
    public function __construct(
        private FieldList $fieldList
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $leadFields = $this->fieldList->getFieldList(false, false);

        $builder->add(
            'twitter_handle_field',
            ChoiceType::class,
            [
                'choices'           => array_flip($leadFields),
                'label'             => 'mautic.social.config.twitter.field.label',
                'required'          => false,
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => ['class' => 'form-control'],
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'social_config';
    }
}
