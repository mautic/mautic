<?php

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<mixed>
 */
class ModifyLeadTagsType extends AbstractType
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'add_tags',
            TagType::class,
            [
                'label' => 'mautic.lead.tags.add',
                'attr'  => [
                    'data-placeholder'     => $this->translator->trans('mautic.lead.tags.select_or_create'),
                    'data-no-results-text' => $this->translator->trans('mautic.lead.tags.enter_to_create'),
                    'data-allow-add'       => 'true',
                    'onchange'             => 'Mautic.createLeadTag(this)',
                ],
                'data'            => $options['data']['add_tags'] ?? null,
                'add_transformer' => true,
            ]
        );

        $builder->add(
            'remove_tags',
            TagType::class,
            [
                'label' => 'mautic.lead.tags.remove',
                'attr'  => [
                    'data-placeholder'     => $this->translator->trans('mautic.lead.tags.select_or_create'),
                    'data-no-results-text' => $this->translator->trans('mautic.lead.tags.enter_to_create'),
                    'data-allow-add'       => 'true',
                    'onchange'             => 'Mautic.createLeadTag(this)',
                ],
                'data'            => $options['data']['remove_tags'] ?? null,
                'add_transformer' => true,
            ]
        );
    }
}
