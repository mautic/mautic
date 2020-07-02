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

use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Symfony\Component\Form\FormBuilderInterface;

class PointActionEmailOpenType extends EmailOpenType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

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

        $builder->add(
            'triggerMode',
            ButtonGroupType::class,
            [
                'choices'     => [
                        ''           => 'mautic.email.open.execute.first',
                        'internalId' => 'mautic.email.open.execute.each',
                    ],
                'expanded'    => true,
                'multiple'    => false,
                'label_attr'  => ['class' => 'control-label'],
                'label'       => 'mautic.email.open.execute',
                'empty_value' => false,
                'required'    => false,
                'attr'        => [
                    'data-show-on' => '{"point_repeatable_0":"checked"}',
                ],
            ]
        );
    }
}
