<?php

namespace Mautic\StageBundle\Form\Type;

use Mautic\StageBundle\Model\StageModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<mixed>>
 */
class StageActionListType extends AbstractType
{
    public function __construct(
        private StageModel $model
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => function (Options $options): array {
                $stages = $this->model->getUserStages();

                $choices = [];
                foreach ($stages as $s) {
                    $choices[$s['name']] = $s['id'];
                }

                return $choices;
            },
            'required'          => false,
            ]);
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix()
    {
        return 'stageaction_list';
    }
}
