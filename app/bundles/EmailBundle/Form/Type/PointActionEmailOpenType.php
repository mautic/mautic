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

use Symfony\Component\Form\FormBuilderInterface;

class PointActionEmailOpenType extends EmailOpenType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add(
            'execute_each',
            'yesno_button_group',
            [
                'label'      => 'mautic.email.open.each',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.email.open.each.tooltip',
                    'data-show-on' => '{"point_repeatable_0":"checked"}',
                ],
                'data'       => isset($options['execute_each']) ? $options['execute_each'] : false,
                'required'   => false,
            ]
        );
    }

    public function getName()
    {
        return 'point_action_emailopen_list';
    }
}
