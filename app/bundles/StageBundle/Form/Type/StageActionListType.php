<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class StageActionType.
 */
class StageActionListType extends AbstractType
{
    private $model;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->model = $factory->getModel('stage');
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        /** @var \Mautic\StageBundle\Model\StageModel $model */
        $model = $this->model;
        $resolver->setDefaults([
            'choices' => function (Options $options) use ($model) {
                $stages = $model->getUserStages();

                $choices = [];
                foreach ($stages as $s) {
                    $choices[$s['id']] = $s['name'];
                }

                return $choices;
            },
            'required' => false,
        ]);
    }

    /**
     * @return null|string|\Symfony\Component\Form\FormTypeInterface
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'stageaction_list';
    }
}
