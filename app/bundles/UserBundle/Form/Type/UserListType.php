<?php

namespace Mautic\UserBundle\Form\Type;

use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<mixed>>
 */
class UserListType extends AbstractType
{
    /**
     * @var array<string,int>
     */
    private array $choices = [];

    public function __construct(
        private UserModel $userModel,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'choices'           => $this->getUserChoices(),
                'expanded'          => false,
                'multiple'          => true,
                'required'          => false,
                'placeholder'       => 'mautic.core.form.chooseone',
            ]
        );
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    /**
     * @return array<string,int>
     */
    private function getUserChoices(): array
    {
        if ($this->choices) {
            return $this->choices;
        }

        $users = $this->userModel->getRepository()->getEntities(
            [
                'filter' => [
                    'force' => [
                        [
                            'column' => 'u.isPublished',
                            'expr'   => 'eq',
                            'value'  => true,
                        ],
                    ],
                ],
            ]
        );

        foreach ($users as $user) {
            $this->choices[$user->getName(true).' ('.$user->getId().')'] = $user->getId();
        }

        // sort by user name
        ksort($this->choices);

        return $this->choices;
    }
}
