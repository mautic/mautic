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
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class UserListType.
 */
class UserListType extends AbstractType
{
    private $choices = [];

    /**
     * UserListType constructor.
     *
     * @param UserModel $model
     */
    public function __construct(UserModel $model)
    {
        $choices = $model->getRepository()->getEntities(
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

        foreach ($choices as $choice) {
            $this->choices[$choice->getId()] = $choice->getName(true);
        }

        //sort by language
        ksort($this->choices);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'choices'     => $this->choices,
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
    public function getName()
    {
        return 'user_list';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
    }
}
