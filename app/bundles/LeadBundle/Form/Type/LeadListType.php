<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
final class LeadListType extends AbstractType
{
    public function __construct(
        private readonly ListModel $segmentModel,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => function (Options $options): array {
                if ($options['preference_center_only']) {
                    $lists = $this->segmentModel->getPreferenceCenterLists($options['preferred_locale']);
                } else {
                    $lists = $options['global_only'] ? $this->segmentModel->getGlobalLists() : $this->segmentModel->getUserLists();
                }

                $choices = [];
                foreach ($lists as $l) {
                    if (empty($options['preference_center_only'])) {
                        $choices[$l['name'].' ('.$l['id'].')'] = $l['id'];
                    } else {
                        $choices[empty($l['publicName']) ? $l['name'].' ('.$l['id'].')' : $l['publicName'].' ('.$l['id'].')'] = $l['id'];
                    }
                }

                return $choices;
            },
            'global_only'            => false,
            'preference_center_only' => false,
            'preferred_locale'       => null,
            'required'               => false,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'leadlist_choices';
    }
}
