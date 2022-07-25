<?php

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class CampaignEventFormSubmitType.
 */
class CampaignEventFormSubmitType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('forms', FormListType::class, [
            'label'      => 'mautic.form.campaign.event.forms',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.form.campaign.event.forms_descr',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'campaignevent_formsubmit';
    }
}
