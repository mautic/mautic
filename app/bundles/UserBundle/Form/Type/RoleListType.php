<?php

namespace Mautic\UserBundle\Form\Type;

use Mautic\UserBundle\Entity\RoleRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<mixed>>
 */
final class RoleListType extends AbstractType
{
    public function __construct(
        private readonly RoleRepository $roleRepository,
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

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    private function getRoleChoices(): array
    {
        $choices = [];
        $roles   = $this->roleRepository->getEntities(
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

        foreach ($roles as $result) {
            $role                          = is_array($result) ? $result[0] : $result;
            $choices[$role->getName(true)] = $role->getId();
        }

        // sort by name
        ksort($choices);

        return $choices;
    }
}
