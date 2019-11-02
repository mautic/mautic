<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Form\Type;

use Mautic\UserBundle\Model\RoleModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RoleListType extends AbstractType
{
    /**
     * @var roleModel
     */
    private $roleModel;

    /**
     * @param RoleModel $roleModel
     */
    public function __construct(RoleModel $roleModel)
    {
        $this->roleModel = $roleModel;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
            $choices[$role->getId()] = $role->getName(true);
        }

        //sort by name
        ksort($choices);

        $resolver->setDefaults(
            [
                'choices'     => $choices,
                'expanded'    => false,
                'multiple'    => false,
                'required'    => false,
                'empty_value' => 'mautic.core.form.chooseone',
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
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
}
