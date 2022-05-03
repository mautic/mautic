<?php

namespace Mautic\StageBundle\Form\Type;

use Mautic\StageBundle\Model\StageModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class StageActionType.
 */
class StageActionListType extends AbstractType
{
    private $model;

    public function __construct(StageModel $model)
    {
        $this->model = $model;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => function (Options $options) {
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
     * @return string|\Symfony\Component\Form\FormTypeInterface|null
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'stageaction_list';
    }
}
