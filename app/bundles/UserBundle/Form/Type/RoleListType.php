<?php

namespace Mautic\UserBundle\Form\Type;

use Mautic\UserBundle\Model\RoleModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoleListType extends AbstractType
{
    /**
     * @var RoleModel
     */
    private $roleModel;

    public function __construct(RoleModel $roleModel)
    {
        $this->roleModel = $roleModel;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
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

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'role_list';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * @return array
     */
    private function getRoleChoices()
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

        //sort by name
        ksort($choices);

        return $choices;
    }
}
