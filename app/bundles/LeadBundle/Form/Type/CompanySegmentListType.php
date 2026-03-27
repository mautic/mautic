<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Model\CompanySegmentModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class CompanySegmentListType extends AbstractType
{
    public function __construct(
        private CompanySegmentModel $companySegmentModel,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => function (Options $options): array {
                $segments = $this->companySegmentModel->getEntities();
                $choices  = [];
                foreach ($segments as $segment) {
                    $name = $segment->getName();
                    if (!empty($options['preference_center_only']) && !empty($segment->getPublicName())) {
                        $name = $segment->getPublicName();
                    }
                    $choices[$name.' ('.$segment->getId().')'] = $segment->getId();
                }
                ksort($choices);

                return $choices;
            },
            'global_only'            => false,
            'preference_center_only' => false,
            'required'               => false,
        ]);
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'companysegment_choices';
    }
}
