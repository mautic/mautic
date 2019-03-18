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

        $builder->add('condition', 'choice', [
            'choices' => [
                'first' => 'First email',
                'each'  => 'Each email',
            ],
            'label'       => 'mautic.email.open.options',
            'empty_value' => false,
            'attr'        => [
                'class'   => 'form-control',
            ],
            'required' => false,
        ]);
    }

    public function getName()
    {
        return 'point_action_emailopen_list';
    }
}
