<?php

namespace Mautic\StageBundle\Form\Type;

use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Model\StageModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StageListType extends AbstractType
{
    /**
     * @var Stage[]
     */
    private array $choices = [];

    public function __construct(StageModel $model)
    {
        $choices = $model->getRepository()->getEntities([
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

        /** @var Stage $choice */
        foreach ($choices as $choice) {
            $this->choices[$choice->getName()] = $choice->getId();
        }

        // sort by language
        ksort($this->choices);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices'           => $this->choices,
            'expanded'          => false,
            'multiple'          => true,
            'required'          => false,
            'placeholder'       => 'mautic.core.form.chooseone',
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
