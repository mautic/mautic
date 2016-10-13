<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class VariantType.
 */
class VariantType extends AbstractType
{
    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('weight', 'integer', [
            'label'      => 'mautic.core.ab_test.form.traffic_weight',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.core.ab_test.form.traffic_weight.help',
            ],
            'constraints' => [
                new NotBlank(
                    ['message' => 'mautic.page.variant.weight.notblank']
                ),
            ],
        ]);

        $abTestWinnerCriteria = $this->factory->getModel('page.page')->getBuilderComponents(null, 'abTestWinnerCriteria');

        if (!empty($abTestWinnerCriteria)) {
            $criteria = $abTestWinnerCriteria['criteria'];
            $choices  = $abTestWinnerCriteria['choices'];

            $builder->add('winnerCriteria', 'choice', [
                'label'      => 'mautic.core.ab_test.form.winner',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'onchange' => 'Mautic.getAbTestWinnerForm(\'page\', \'page\', this);',
                ],
                'expanded'    => false,
                'multiple'    => false,
                'choices'     => $choices,
                'empty_value' => 'mautic.core.form.chooseone',
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.core.ab_test.winner_criteria.not_blank']
                    ),
                ],
            ]);

            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options, $criteria) {
                $form = $event->getForm();
                $data = $event->getData();
                if (isset($data['winnerCriteria'])) {
                    if (!empty($criteria[$data['winnerCriteria']]['formType'])) {
                        $formTypeOptions = [
                            'required' => false,
                            'label'    => false,
                        ];
                        if (!empty($criteria[$data]['formTypeOptions'])) {
                            $formTypeOptions = array_merge($formTypeOptions, $criteria[$data]['formTypeOptions']);
                        }
                        $form->add('properties', $criteria[$data]['formType'], $formTypeOptions);
                    }
                }
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pagevariant';
    }
}
