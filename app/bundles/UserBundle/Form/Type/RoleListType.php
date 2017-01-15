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
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class RoleListType.
 */
class RoleListType extends AbstractType
{
    /**
     * @var array
     */
    private $choices = [];

    /**
     * RoleListType constructor.
     *
     * @param RoleModel $model
     */
    public function __construct(RoleModel $model)
    {
        $choices = $model->getRepository()->getEntities(
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
        return 'choice';
    }
}
