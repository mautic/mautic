<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Form\Type\Auth;

use Mautic\IntegrationsBundle\Form\Type\NotBlankIfPublishedConstraintTrait;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

trait BasicAuthKeysTrait
{
    use NotBlankIfPublishedConstraintTrait;

    /**
     * @param string $usernameLabel
     * @param string $passwordLabel
     */
    private function addKeyFields(FormBuilderInterface $builder, $usernameLabel = 'mautic.core.username', $passwordLabel = 'mautic.core.password'): void
    {
        $builder->add(
            'username',
            TextType::class,
            [
                'label'       => $usernameLabel,
                'label_attr'  => ['class' => 'control-label'],
                'required'    => true,
                'attr'        => [
                    'class' => 'form-control',
                ],
                'constraints' => [$this->getNotBlankConstraint()],
            ]
        );

        $builder->add(
            'password',
            PasswordType::class,
            [
                'label'       => $passwordLabel,
                'label_attr'  => ['class' => 'control-label'],
                'required'    => true,
                'attr'        => [
                    'class' => 'form-control',
                ],
                'constraints' => [$this->getNotBlankConstraint()],
            ]
        );
    }
}
