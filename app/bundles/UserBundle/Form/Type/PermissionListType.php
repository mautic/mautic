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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PermissionListType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['bundle', 'level']);

        $resolver->setDefaults([
            'multiple'          => true,
            'expanded'          => true,
            'label_attr'        => ['class' => 'control-label'],
            'attr'              => function (Options $options) {
                return [
                    'data-permission' => $options['bundle'].':'.$options['level'],
                    'onchange'        => 'Mautic.onPermissionChange(this, \''.$options['bundle'].'\')',
                ];
            },
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'permissionlist';
    }
}
