<?php

namespace Mautic\StageBundle\Form\Type;

use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Model\StageModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Stage>
 */
class StageListType extends AbstractType
{
    /**
     * @var array<string,int>
     */
    private array $choices = [];

    public function __construct(private StageModel $stageModel)
    {
        $this->stageModel = $stageModel;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices'           => $this->getStageChoices(),
            'expanded'          => false,
            'multiple'          => true,
            'required'          => false,
            'placeholder'       => 'mautic.core.form.chooseone',
        ]);
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    /**
     * @return array<string,int>
     */
    private function getStageChoices(): array
    {
        if ($this->choices) {
            return $this->choices;
        }

        $stages = $this->stageModel->getRepository()->getEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => 's.isPublished',
                        'expr'   => 'eq',
                        'value'  => true,
                    ],
                ],
            ],
        ]);

        /** @var Stage $stage */
        foreach ($stages as $stage) {
            $this->choices[$stage->getName()] = $stage->getId();
        }

        // sort by language
        ksort($this->choices);

        return $this->choices;
    }
}
