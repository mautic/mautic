<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;

trait ToBcBccFieldsTrait
{
    /**
     * @param FormBuilderInterface $builder
     */
    protected function addToBcBccFields(FormBuilderInterface $builder)
    {
        $builder->add(
            'to',
            'text',
            [
                'label'      => 'mautic.core.send.email.to',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'placeholder' => 'mautic.core.optional',
                    'tooltip'     => 'mautic.core.send.email.to.multiple.addresses',
                ],
                'required'    => false,
                'constraints' => new Email(
                    [
                        'message' => 'mautic.core.email.required',
                    ]
                ),
            ]
        );

        $builder->add(
            'cc',
            'text',
            [
                'label'      => 'mautic.core.send.email.cc',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'placeholder' => 'mautic.core.optional',
                    'tooltip'     => 'mautic.core.send.email.to.multiple.addresses',
                ],
                'required'    => false,
                'constraints' => new Email(
                    [
                        'message' => 'mautic.core.email.required',
                    ]
                ),
            ]
        );

        $builder->add(
            'bcc',
            'text',
            [
                'label'      => 'mautic.core.send.email.bcc',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'placeholder' => 'mautic.core.optional',
                    'tooltip'     => 'mautic.core.send.email.to.multiple.addresses',
                ],
                'required'    => false,
                'constraints' => new Email(
                    [
                        'message' => 'mautic.core.email.required',
                    ]
                ),
            ]
        );
    }
}
