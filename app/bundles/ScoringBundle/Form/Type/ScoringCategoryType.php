<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ScoringBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\AbstractFormStandardType;
use Mautic\ScoringBundle\Entity\ScoringCategory;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ScoringCategoryType.
 */
class ScoringCategoryType extends AbstractFormStandardType
{
    /**
     * ScoringCategoryType constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('scoring.scoringCategory', $options));

        $builder->add('name', 'text', [
            'label'      => 'mautic.core.name',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
        ]);

        $data = false;
        if (!empty($options['data']) && $options['data'] instanceof ScoringCategory) {
            $data = $options['data']->isPublished(false);
        }
        $builder->add('isPublished', 'yesno_button_group', [
            'read_only' => false,
            'data'      => $data,
        ]);

        $builder->add('orderIndex', 'number', [
            'label'      => 'mautic.scoring.scoringCategory.action.orderIndex',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                    'class' => 'form-control',
                ],
            'precision' => 0,
        ]);

        $builder->add('updateGlobalScore', 'yesno_button_group', [
            'label'      => 'mautic.scoring.scoringCategory.action.updateGlobalScore',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                    'onchange'  => 'Mautic.onChangeUpdateGlobalScore(this)',
                    'tooltip' => 'mautic.scoring.scoringCategory.action.updateGlobalScore.tooltip',
                ],
        ]);
        
        $isHidden = true;
        if (!empty($options['data']) && $options['data'] instanceof ScoringCategory) {
            $isHidden = !($options['data']->getUpdateGlobalScore());
        }
        $builder->add('globalScoreModifier', 'number', [
            'label'      => 'mautic.scoring.scoringCategory.action.globalScoreModifier',
            'label_attr' => ['class' => 'control-label'.($isHidden? ' hide':'')],
            'attr'       => [
                    'class'   => 'form-control'.($isHidden? ' hide':''),
                    'tooltip' => 'mautic.scoring.scoringCategory.action.globalScoreModifier.tooltip',
                ],
            'precision' => 2,
        ]);

        $builder->add('buttons', 'form_buttons');

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => ScoringCategory::class,
            ]
        );
    }
}
