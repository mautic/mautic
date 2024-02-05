<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class LeadListType extends AbstractType
{
    public function __construct(
        private ListModel $segmentModel
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => function (Options $options): array {
                $lists = (empty($options['global_only'])) ? $this->segmentModel->getUserLists() : $this->segmentModel->getGlobalLists();
                $lists = (empty($options['preference_center_only'])) ? $lists : $this->segmentModel->getPreferenceCenterLists();

                $choices = [];
                foreach ($lists as $l) {
                    if (empty($options['preference_center_only'])) {
                        $choices[$l['name']] = $l['id'];
                    } else {
                        $choices[empty($l['publicName']) ? $l['name'] : $l['publicName']] = $l['id'];
                    }
                }

                return $choices;
            },
            'global_only'            => false,
            'preference_center_only' => false,
            'required'               => false,
        ]);
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'leadlist_choices';
    }
}
