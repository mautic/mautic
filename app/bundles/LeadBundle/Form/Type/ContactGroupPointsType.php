<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\PointBundle\Entity\Group;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactGroupPointsType extends AbstractType
{
    private const SCORE_FIELD_PREFIX = 'score_group_';

    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public static function getFieldKey(int $groupId): string
    {
        return self::SCORE_FIELD_PREFIX.$groupId;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $pointGroups  = $options['point_groups'] ?? [];

        /** @var Group $group */
        foreach ($pointGroups as $group) {
            $key = self::getFieldKey($group->getId());
            $builder->add(
                $key,
                IntegerType::class,
                [
                    'label'      => $group->getName(),
                    'attr'       => [
                        'class'       => 'form-control',
                        'placeholder' => $this->translator->trans('mautic.point.form.score_not_set'),
                    ],
                    'label_attr' => ['class' => 'control-label'],
                    'required'   => false,
                ]
            );
        }

        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'apply_text'     => false,
                'save_text'      => 'mautic.core.form.save',
                'cancel_onclick' => 'javascript:void(0);',
                'cancel_attr'    => [
                    'data-dismiss' => 'modal',
                ],
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['point_groups']);
    }
}
