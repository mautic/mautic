<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PointActionEmailSendType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'emails',
            EmailListType::class,
            [
                'label'=> 'mautic.email.open.limittoemails',
                'attr' => [
                    'tooltip'=> 'mautic.email.open.limittoemails_descr',
                ],
            ]
        );

        $builder->add(
            'categories',
            'category',
            [
                'label'           => 'mautic.email.open.limittocategories',
                'bundle'          => 'email',
                'multiple'        => true,
                'empty_value'     => true,
                'with_create_new' => false,
                'return_entity'   => false,
                'attr'            => [
                    'tooltip'=> 'mautic.email.open.limittocategories_descr',
                ],
            ]
        );
    }
}
