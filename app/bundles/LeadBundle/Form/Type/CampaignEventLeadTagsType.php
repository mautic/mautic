<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<mixed>
 */
final class CampaignEventLeadTagsType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            static function (FormEvent $event): void {
                $data = $event->getData();

                if (
                    is_array($data)
                    && isset($data['properties']['tags'])
                    && empty($data['tags'])
                ) {
                    $data['tags'] = $data['properties']['tags'];
                    $event->setData($data);
                }
            }
        );

        $builder->add(
            'tags',
            TagType::class,
            [
                'add_transformer' => true,
                'by_reference'    => false,
                'attr'            => [
                    'data-placeholder'     => $this->translator->trans('mautic.lead.tags.select_or_create'),
                    'data-no-results-text' => $this->translator->trans('mautic.lead.tags.enter_to_create'),
                    'data-allow-add'       => 'true',
                    'onchange'             => 'Mautic.createLeadTag(this)',
                ],
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'campaignevent_lead_tags';
    }
}
