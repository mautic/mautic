<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Form\Type;

use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserListType extends AbstractType
{
    /**
     * @var UserModel
     */
    private $userModel;

    /**
     * @param UserModel $userModel
     */
    public function __construct(UserModel $userModel)
    {
        $this->userModel = $userModel;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [];
        $users   = $this->userModel->getRepository()->getEntities(
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
            $choices[$user->getId()] = $user->getName(true);
        }

        //sort by language
        ksort($choices);

        $resolver->setDefaults(
            [
                'choices'     => $choices,
                'expanded'    => false,
                'multiple'    => true,
                'required'    => false,
                'empty_value' => 'mautic.core.form.chooseone',
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'user_list';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
