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

class PointActionEmailOpenType extends PointActionType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add(
            'triggerMode',
            ButtonGroupType::class,
            [
                'choices'     => [
                    'mautic.email.open.execute.first'           => '',
                    'mautic.email.open.execute.each'            => 'internalId',
                    ],
                'expanded'    => true,
                'multiple'    => false,
                'label_attr'  => ['class' => 'control-label'],
                'label'       => 'mautic.email.open.execute',
                'placeholder' => false,
                'required'    => false,
                'attr'        => [
                    'data-show-on' => '{"point_repeatable_0":"checked"}',
                ],
            ]
        );
    }
}
