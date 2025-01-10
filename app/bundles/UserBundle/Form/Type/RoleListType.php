<?php

namespace Mautic\UserBundle\Form\Type;

use Mautic\UserBundle\Model\RoleModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<mixed>>
 */
class RoleListType extends AbstractType
{
    public function __construct(
        private RoleModel $roleModel,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'choices'           => $this->getRoleChoices(),
                'expanded'          => false,
                'multiple'          => false,
                'required'          => false,
                'placeholder'       => 'mautic.core.form.chooseone',
            ]
        );
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    private function getRoleChoices(): array
    {
        $choices = [];
        $roles   = $this->roleModel->getRepository()->getEntities(
            [
                'filter' => [
                    'force' => [
                        [
                            'column' => 'r.isPublished',
                            'expr'   => 'eq',
                            'value'  => true,
                        ],
                    ],
                ],
            ]
        );

        foreach ($roles as $role) {
            $choices[$role->getName(true)] = $role->getId();
        }

        // sort by name
        ksort($choices);

        return $choices;
    }
}
