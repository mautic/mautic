<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Entity\DoNotContact;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<mixed>
 */
class CampaignEventLeadDNCType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'channels',
            PreferenceChannelsType::class,
            [
                'label'       => 'mautic.lead.contact.channels',
                'multiple'    => true,
                'required'    => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ]
        );

        $builder->add(
            'reason',
            ChoiceType::class,
            [
                'choices'  => [
                    'mautic.lead.do.not.contact_bounced'      => DoNotContact::BOUNCED,
                    'mautic.lead.do.not.contact_unsubscribed' => DoNotContact::UNSUBSCRIBED,
                    'mautic.lead.do.not.contact_manual'       => DoNotContact::MANUAL,
                ],
                'label'      => 'mautic.lead.batch.dnc_reason',
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]);
    }
}
