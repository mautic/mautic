<?php

namespace Mautic\PageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<array<mixed>>
 */
class CampaignEventPageHitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('pages', PageListType::class, [
            'label'      => 'mautic.page.campaign.event.form.pages',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.page.campaign.event.form.pages.descr',
            ],
        ]);

        $builder->add('url', TextType::class, [
            'label'      => 'mautic.page.campaign.event.form.url',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.page.campaign.event.form.url.descr',
            ],
        ]);

        $builder->add('referer', TextType::class, [
            'label'      => 'mautic.page.campaign.event.form.referer',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.page.campaign.event.form.referer.descr',
            ],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'campaignevent_pagehit';
    }
}
